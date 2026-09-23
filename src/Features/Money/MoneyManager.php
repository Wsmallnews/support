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
 * 货币服务（全生态金额处理的唯一规范入口）。
 *
 * 货币规范：
 * 1. 存储：数据库一律存「最小货币单位的整数」（分/cent），列类型 unsignedBigInteger；
 *    JSON 金额明细（amount_fields/discount_fields 等）同样存整数分，键 => 金额，
 *    不存小数、不嵌套币种对象，参与运算的口径与列完全一致。
 * 2. 币种：单据级快照。sn_orders/sn_pay_records/sn_pay_refunds 必有 currency 列(char 3, ISO 4217)，
 *    创建单据时固化；sn_products.currency 可空（空 = 站点默认币种），变体价格随 SPU 币种；
 *    订单项/明细不存币种，随单据。跨币种必须先换算成单一交易币种再落单。
 * 3. 输入约定：int = 最小单位（分）；string/float = 十进制主单位（元，来自表单）；
 *    Money 对象原样透传。禁止把浮点运算结果直接传入（浮点精度污染），请用本服务的运算方法。
 * 4. 运算：全部经 Money 值对象（bcmath 整数运算）；分摊（优惠拆单等）一律用 allocate() 余数分配，
 *    禁止手工除法后四舍五入（会丢分）。
 * 5. 展示：符号/千分位/小数位是纯展示层概念，禁止入库。统一走 format()/symbol()
 *    （底层 Laravel Number + intl，按 locale + currency 输出）。
 * 6. 站点默认币种解析链：config('app.currency')（可选覆盖）→ sn-support.currency → CNY，
 *    由 defaultCurrency() 统一解析，SupportServiceProvider 启动时同步给 Laravel Number 与 cknow/money。
 */
class MoneyManager
{
    /**
     * 站点默认币种（ISO 4217）。
     * 优先级：消费应用 config('app.currency')（cknow/money 遵循的社区约定，可选）
     * → sn-support.currency（包默认，消费应用无需任何配置）
     * → 'CNY' 兜底。
     */
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
     * 取值自带币种（Money 取内嵌币种，标量取指定/默认币种）。
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
     * 构造 Money（输入约定见类注释）。
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

        // string/float 一律视为十进制主单位（元）
        return CknowMoney::parseByDecimal((string) $value, $this->parseCurrency($currency));
    }

    /**
     * 从最小单位（分）构造。
     */
    public function fromMinor(int | string $minor, ?string $currency = null): CknowMoney
    {
        return new CknowMoney((int) $minor, $this->parseCurrency($currency ?: $this->defaultCurrency()));
    }

    /**
     * 从十进制主单位（元）构造。
     */
    public function fromDecimal(string | float $decimal, ?string $currency = null): CknowMoney
    {
        return CknowMoney::parseByDecimal((string) $decimal, $this->parseCurrency($currency ?: $this->defaultCurrency()));
    }

    /**
     * 转为最小单位（分）整数。
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     */
    public function minor($value, ?string $currency = null): int
    {
        $money = $this->money($value, $currency);

        return $money === null ? 0 : (int) $money->getAmount();
    }

    /**
     * 转为十进制主单位（元）字符串，如 "12.34"（小数位随币种）。
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     */
    public function decimal($value, ?string $currency = null): string
    {
        $money = $this->money($value, $currency);

        return $money === null ? '0' : (string) $money->formatByDecimal();
    }

    /**
     * 加法（参数币种必须一致，空值视为 0 元）。
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
     * 减法（第一个参数为被减数）。
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
     * 乘法（数量/倍数，倍数仅接受整数或整数字符串，避免浮点乘法丢精度）。
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
     * 求和。
     *
     * @param  array<int|string, CknowMoney|MoneyMoney|int|string|float|null>  $values
     */
    public function sum(array $values, ?string $currency = null): CknowMoney
    {
        return $this->add(...array_values($values));
    }

    /**
     * 按比例分摊金额（余数分配法：除不尽的分逐个补给前面的份额，总额不丢分）。
     * 例：100 元优惠摊 3 单 -> allocate(10000, [1, 1, 1]) => [3334, 3333, 3333]。
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
     * 是否为负数。
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     */
    public function isNegative($value): bool
    {
        return $this->money($value)->isNegative();
    }

    /**
     * 是否为零。
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     */
    public function isZero($value): bool
    {
        return $this->money($value)->isZero();
    }

    /**
     * 是否大于零。
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     */
    public function isPositive($value): bool
    {
        return $this->money($value)->isPositive();
    }

    /**
     * 格式化为带符号的展示金额（展示层唯一入口），如 ￥1,234.56 / $1,234.56。
     *
     * @param  CknowMoney|MoneyMoney|int|string|float|null  $value
     */
    public function format($value, ?string $currency = null, ?string $locale = null): string
    {
        $money = $this->money($value, $currency);

        return Number::currency($money->formatByDecimal(), $money->getCurrency()->getCode(), $locale);
    }

    /**
     * 货币符号（如 ￥ / $），币种缺省取站点默认。
     */
    public function symbol(?string $currency = null, ?string $locale = null): string
    {
        $currency ??= $this->defaultCurrency();
        $locale ??= config('app.locale');

        $formatted = Number::currency(0, $currency, $locale);

        // 去掉数字部分，只留符号（含前后缀空白）
        $symbol = Str::replaceMatches(
            pattern: '/(?<=\W)\d+\.?\d*/u',
            replace: '',
            subject: $formatted
        );

        return trim($symbol);
    }

    /**
     * JSON 金额明细清洗：所有值统一为整数分（int 进 int 出，string 十进制会按元转换）。
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
     * JSON 金额明细求和（整数分）。
     *
     * @param  array<string, int|string|float>  $fields
     */
    public function jsonSum(array $fields, ?string $currency = null): int
    {
        return array_sum($this->jsonFields($fields, $currency));
    }

    /**
     * JSON 金额明细格式化为展示字符串（键 => "￥1,234.56"）。
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

    /**
     * 解析币种。
     */
    protected function parseCurrency(string $currency): Currency
    {
        return CknowMoney::parseCurrency($currency);
    }

    /**
     * 归一化一批输入：空值补零、统一为 CknowMoney，并校验币种一致。
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
