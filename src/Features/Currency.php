<?php

namespace Wsmallnews\Support\Features;

use Cknow\Money\Money as CknowMoney;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Money\Money as MoneyMoney;
use Wsmallnews\Support\Exceptions\SupportException;

/**
 * 这是个残次品，可删除
 */
class Currency
{
    public $filamentFormState;
    public $filamentFormSymbol;

    public function __construct()
    {
        $this->filamentFormState = function (?Model $record, $state): float {
            // 这里 state 被转数组了
            if (is_array($state)) {
                return $record->cost_price->formatByDecimal();
            }

            return $state;
        };

        $this->filamentFormSymbol = function (?Model $record, $state): string {
            return $record->cost_price instanceof CknowMoney ? $this->getSymbol($record->cost_price) : $this->getSymbol();
        };
    }

    /**
     * 加法运算
     *
     * @param  CknowMoney|int|string|float  ...$moneys
     * @return CknowMoney
     */
    public function add(...$moneys)
    {
        $moneys = $this->parseMoney($moneys);

        $first = Arr::first($moneys);
        $remain = Arr::take($moneys, -(count($moneys) - 1));

        return $first->add(...$remain);
    }

    /**
     * 减法运算
     *
     * @param  CknowMoney|int|string|float  ...$moneys
     * @return CknowMoney
     */
    public function subtract(...$moneys)
    {
        $moneys = $this->parseMoney($moneys);

        $first = Arr::first($moneys);
        $remain = Arr::take($moneys, -(count($moneys) - 1));

        return $first->subtract(...$remain);
    }

    /**
     * 乘法运算
     *
     * @param  CknowMoney|int|string|float  $money
     * @param  int|string|float  $multiplier
     * @return CknowMoney
     */
    public function multiply($money, $multiplier)
    {
        $money = $this->parseMoney($money);

        return $money->multiply($multiplier);
    }

    /**
     * 除法运算
     *
     * @param  CknowMoney|int|string|float  $money
     * @param  int|string|float  $divisor
     * @return CknowMoney
     */
    public function divide($money, $divisor)
    {
        $money = $this->parseMoney($money);

        return $money->divide($divisor);
    }

    /**
     * 是否等于 0
     *
     * @param  CknowMoney|int|string|float  $money
     * @return bool
     */
    public function isZero($money)
    {
        $money = $this->parseMoney($money);

        return $money->isZero();
    }

    /**
     * 是否大于 0
     *
     * @param  CknowMoney|int|string|float  $money
     * @return bool
     */
    public function isPositive($money)
    {
        $money = $this->parseMoney($money);

        return $money->isPositive();
    }

    /**
     * 是否小于 0
     *
     * @param  CknowMoney|int|string|float  $money
     * @return bool
     */
    public function isNegative($money)
    {
        $money = $this->parseMoney($money);

        return $money->isNegative();
    }

    /**
     * 格式化操作金额
     *
     * @param  CknowMoney|int|string|float|array  $originalMoneys
     * @return CknowMoney|array
     */
    public function parseMoney($originalMoneys)
    {
        $moneys = Arr::wrap($originalMoneys);

        $moneys = Arr::map($moneys, function ($money) {
            return is_scalar($money) ? CknowMoney::parseByDecimal($money, $this->getCurrency()) : $money;
        });

        return Arr::accessible($originalMoneys) ? $moneys : Arr::first($moneys);
    }

    public function getSymbol($money = null)
    {
        // 当前货币
        $currency = ($money instanceof CknowMoney || $money instanceof MoneyMoney) ? $money->getCurrency() : $this->getCurrency();

        $formatCurrency = Number::currency(0, $currency);

        $symbol = Str::replaceMatches(
            pattern: '/(?<=\W)\d+\.?\d*/u',
            replace: '',
            subject: $formatCurrency
        );

        return $symbol;
    }

    public function format($originalMoneys, $type = 'decimal')
    {
        $method = 'formatBy' . Str::studly($type);
        if (! method_exists($this, $method)) {
            throw new SupportException('method ' . $method . ' is not found');
        }

        return $this->$method($originalMoneys);
    }

    /**
     * 格式化操作金额
     *
     * @param  CknowMoney|int|string|float|array  $originalMoneys
     */
    public function formatByDecimal($originalMoneys): array | string
    {
        $moneys = Arr::wrap($originalMoneys);

        $moneys = Arr::map($moneys, function ($money) {
            return $money instanceof CknowMoney ? $money->formatByDecimal() : number_format((float) $money, 2, '.', '');
        });

        return Arr::accessible($originalMoneys) ? $moneys : Arr::first($moneys);
    }

    public function getLocale()
    {
        return Number::defaultLocale();
    }

    public function getCurrency()
    {
        return Number::defaultCurrency();
    }
}
