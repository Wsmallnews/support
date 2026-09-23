<?php

namespace Wsmallnews\Support\Casts;

use Cknow\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Money\Currency;

/**
 * 金额字段 cast：数据库存「最小货币单位的整数」（分），模型属性读出为 Cknow\Money\Money。
 *
 * 币种绑定（单据表推荐）：
 *   protected $casts = ['pay_fee' => MoneyCast::class.':currency'];
 * 参数为币种所在「列名」时（非 ISO 4217 代码），读取行内 currency 列构造 Money，
 * 列缺失/为空回落站点默认币种（config('app.currency')）；写入时会同步回填 currency 列。
 * 不带参数则始终使用站点默认币种（商品/变体等无行内币种的表）。
 *
 * 写入契约（与 cknow Money::parse 一致）：
 *   - Money 对象（Cknow\Money\Money 或 Money\Money）：原样取最小单位；
 *   - int/float/numeric string：视为十进制主单位（元），如传 100 存 10000 分——
 *     严禁把「分」以标量形式传入，请先经 sn_money()->fromMinor() 构造 Money。
 */
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
     * @param  Model  $model
     * @param  mixed  $value
     * @return Money|null
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
     * @param  Model  $model
     * @param  mixed  $value
     * @return array
     *
     * @throws InvalidArgumentException
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
     * @return Currency
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
