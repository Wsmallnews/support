<?php

namespace Wsmallnews\Support\Features\Money;

use Cknow\Money\Money as CknowMoney;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Money\Currency;
use Money\Money as MoneyMoney;
use Wsmallnews\Support\Exceptions\SupportException;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Single entry point for money handling: store minor units, never floats.
 * See the Money section of the README.
 */
class MoneyManager
{
    /** Default currency (ISO 4217): app.currency -> sn-support.currency -> CNY. */
    public function defaultCurrency(): string
    {
        $appCurrency = config('app.currency');
        if (is_string($appCurrency) && $appCurrency !== '') {
            return $appCurrency;
        }

        $supportCurrency = SupportUtils::getConfig('currency');
        if (is_string($supportCurrency) && $supportCurrency !== '') {
            return $supportCurrency;
        }

        return 'CNY';
    }

    /**
     * Currency carried by the value: embedded for Money objects, the passed string for scalars.
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     */
    public function currencyOf($value = null, ?string $fallback = null): ?string
    {
        if ($value instanceof CknowMoney) {
            return $value->getCurrency()->getCode();
        }

        if ($value instanceof MoneyMoney) {
            return $value->getCurrency()->getCode();
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return $fallback;
    }

    /**
     * Build a Money value: int = minor units, string/float = decimal major units.
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     */
    public function money($value, ?string $currency = null): ?CknowMoney
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CknowMoney) {
            return $value;
        }

        if ($value instanceof MoneyMoney) {
            return CknowMoney::fromMoney($value);
        }

        $currency = $currency ?: $this->defaultCurrency();

        if (is_int($value)) {
            return new CknowMoney($value, $this->parseCurrency($currency));
        }

        // Strings and floats are decimal major units
        return CknowMoney::parseByDecimal((string) $value, $this->parseCurrency($currency));
    }

    /** Build from minor units. */
    public function fromMinor(int | string $minor, ?string $currency = null): CknowMoney
    {
        return new CknowMoney((int) $minor, $this->parseCurrency($currency ?: $this->defaultCurrency()));
    }

    /** Build from decimal major units. */
    public function fromDecimal(string | float $decimal, ?string $currency = null): CknowMoney
    {
        return CknowMoney::parseByDecimal((string) $decimal, $this->parseCurrency($currency ?: $this->defaultCurrency()));
    }

    /**
     * Convert to an integer amount in minor units.
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     */
    public function minor($value, ?string $currency = null): int
    {
        $money = $this->money($value, $currency);

        return $money === null ? 0 : (int) $money->getAmount();
    }

    /**
     * Convert to a decimal string such as "12.34"; the scale follows the currency.
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     */
    public function decimal($value, ?string $currency = null): string
    {
        $money = $this->money($value, $currency);

        return $money === null ? '0' : (string) $money->formatByDecimal();
    }

    /**
     * Sum values; currencies must match, null counts as zero.
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  ...$values
     */
    public function add(...$values): CknowMoney
    {
        $moneys = $this->normalizeSeries($values);

        $first = array_shift($moneys);

        return $first->add(...$moneys);
    }

    /**
     * Subtract, the first argument is the minuend.
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  ...$values
     */
    public function subtract(...$values): CknowMoney
    {
        $moneys = $this->normalizeSeries($values);

        $first = array_shift($moneys);

        return $first->subtract(...$moneys);
    }

    /**
     * Multiply by an integer factor only, so no precision is lost.
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     */
    public function multiply($value, int | string $factor): CknowMoney
    {
        $factor = (int) $factor;

        if ($factor < 0) {
            throw new SupportException('Money multiply factor must be a non-negative integer.');
        }

        return $this->money($value)->multiply($factor);
    }

    /**
     * Sum a map of values.
     *
     * @param  array<int|string, CknowMoney|MoneyMoney|int|string|float|null>  $values
     */
    public function sum(array $values, ?string $currency = null): CknowMoney
    {
        return $this->add(...array_values($values));
    }

    /**
     * Split by ratios with remainder distribution, so the total is preserved exactly.
     * E.g. allocate(10000, [1, 1, 1]) => [3334, 3333, 3333].
     *
     * @param  CknowMoney|MoneyMoney|int|string|float  $value
     * @param  array<int, int>  $ratios
     * @return array<int, CknowMoney>
     */
    public function allocate($value, array $ratios, ?string $currency = null): array
    {
        $money = $this->money($value, $currency);

        if (array_filter($ratios, fn ($ratio) => $ratio < 0) || array_sum($ratios) === 0) {
            throw new SupportException('Money allocate ratios must be non-negative and not all zero.');
        }

        return array_map(
            fn (MoneyMoney $allocated) => CknowMoney::fromMoney($allocated),
            $money->getMoney()->allocate($ratios)
        );
    }

    /**
     * Whether the value is negative.
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     */
    public function isNegative($value): bool
    {
        return $this->money($value)->isNegative();
    }

    /**
     * Whether the value is zero.
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     */
    public function isZero($value): bool
    {
        return $this->money($value)->isZero();
    }

    /**
     * Whether the value is greater than zero.
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     */
    public function isPositive($value): bool
    {
        return $this->money($value)->isPositive();
    }

    /**
     * Format for display with symbol, e.g. ￥1,234.56. The only display formatter callers need.
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     */
    public function format($value, ?string $currency = null, ?string $locale = null): string
    {
        $money = $this->money($value, $currency);

        return Number::currency($money->formatByDecimal(), $money->getCurrency()->getCode(), $locale);
    }

    /**
     * Symbol and amount separately, both resolved from the same currency and locale.
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     * @return array{symbol: string, amount: string}
     */
    public function formatParts($value, ?string $currency = null, ?string $locale = null): array
    {
        $money = $this->money($value, $currency);

        return [
            'symbol' => $this->symbol($money->getCurrency()->getCode(), $locale),
            'amount' => Number::format($money->formatByDecimal(), precision: 2, locale: $locale),
        ];
    }

    /** Currency symbol, defaulting to the site currency. */
    public function symbol(?string $currency = null, ?string $locale = null): string
    {
        $currency ??= $this->defaultCurrency();
        $locale ??= config('app.locale');

        $formatted = Number::currency(0, $currency, $locale);

        // Strip the numeric part, keep the symbol
        $symbol = Str::replaceMatches(
            pattern: '/(?<=\W)\d+\.?\d*/u',
            replace: '',
            subject: $formatted
        );

        return trim($symbol);
    }

    /**
     * Normalise a JSON money map to integer minor units.
     *
     * @param  array<string, int|string|float>  $fields
     * @return array<string, int>
     */
    public function jsonFields(array $fields, ?string $currency = null): array
    {
        return array_map(
            fn ($value) => $this->minor($value, $currency),
            $fields
        );
    }

    /**
     * Sum a JSON money map in minor units.
     *
     * @param  array<string, int|string|float>  $fields
     */
    public function jsonSum(array $fields, ?string $currency = null): int
    {
        return array_sum($this->jsonFields($fields, $currency));
    }

    /**
     * Format a JSON money map for display (key => "￥1,234.56").
     *
     * @param  array<string, int|string|float>  $fields
     * @return array<string, string>
     */
    public function jsonFormat(array $fields, ?string $currency = null, ?string $locale = null): array
    {
        $currency ??= $this->defaultCurrency();

        return array_map(
            fn ($value) => $this->format($this->money($value, $currency), $currency, $locale),
            $fields
        );
    }

    /** Resolve a currency instance. */
    protected function parseCurrency(string $currency): Currency
    {
        return CknowMoney::parseCurrency($currency);
    }

    /**
     * Normalise a value list to Money and reject mixed currencies.
     *
     * @param  array<int, CknowMoney|MoneyMoney|int|string|float|null>  $values
     * @return array<int, CknowMoney>
     */
    protected function normalizeSeries(array $values): array
    {
        $moneys = array_map(
            fn ($value) => $this->money($value ?? 0),
            array_values($values)
        );

        $currencies = array_map(
            fn (CknowMoney $money) => $money->getCurrency()->getCode(),
            $moneys
        );

        if (count(array_unique($currencies)) > 1) {
            throw new SupportException('Money operations require identical currencies, got: ' . implode(', ', $currencies));
        }

        return $moneys;
    }
}
