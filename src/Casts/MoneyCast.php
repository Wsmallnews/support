<?php

namespace Wsmallnews\Support\Casts;

use Cknow\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class MoneyCast implements CastsAttributes 
{
    /**
     * The currency code or the model attribute holding the currency code.
     *
     * @var string|null
     */
    protected $currency;


    /**
     * Instantiate the class.
     *
     * @param  ?string  $currency
     */
    public function __construct(?string $currency = null)
    {
        $this->currency = $currency;
    }

    /**
     * Get formatter.
     *
     * @return string|float|int
     */
    protected function getFormatter(Money $money)
    {
        return (int) $money->getAmount();
    }

    /**
     * Transform the attribute from the underlying model values.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  mixed  $value
     * @return \Cknow\Money\Money|null
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        return Money::parse($value, $this->getCurrency($attributes), false);
    }

    /**
     * Transform the attribute to its underlying model values.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  mixed  $value
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return [$key => $value];
        }

        try {
            $money = Money::parse($value, $this->getCurrency($attributes), true);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                sprintf('Invalid data provided for %s::$%s', get_class($model), $key)
            );
        }

        $amount = $this->getFormatter($money);

        if ($this->currency && ! Money::isValidCurrency($this->currency)) {
            return [$key => $amount, $this->currency => $money->getCurrency()->getCode()];
        }

        return [$key => $amount];
    }

    /**
     * Get currency.
     *
     * @return \Money\Currency
     */
    protected function getCurrency(array $attributes)
    {
        $defaultCode = Money::getDefaultCurrency();

        if ($this->currency === null) {
            return Money::parseCurrency($defaultCode);
        }

        $currency = Money::parseCurrency($this->currency);
        $currencies = Money::getCurrencies();

        if ($currencies->contains($currency)) {
            return $currency;
        }

        $code = $attributes[$this->currency] ?? $defaultCode;

        return Money::parseCurrency($code);
    }
}