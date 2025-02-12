<?php

namespace Wsmallnews\Support\Features;

use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\AggregateMoneyFormatter;
use Money\Formatter\BitcoinMoneyFormatter;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Formatter\IntlLocalizedDecimalFormatter;
use Money\Formatter\IntlMoneyFormatter;
use NumberFormatter;

/**
 * 这是个残次品，可删除
 */
class Currency
{
    protected $locale = Number::defaultLocale();

    protected $currency = Number::defaultCurrency();

    protected $defaultFormat = 'decimal';

    protected $formaters = [];

    /**
     * 1、直接 获取的方法、
     *
     *
     * 2、特定金额的操作方法
     */

    /**
     * 获取当前环境货币符号 （需要提前设置好 Illuminate\Support\Number 的地区和货币类型）
     *
     * @return string
     */
    public function symbol()
    {
        $formatCurrency = Number::currency(0);

        $symbol = Str::replaceMatches(
            pattern: '/(?<=\W)\d+\.?\d*/u',
            replace: '',
            subject: $formatCurrency
        );

        return $symbol;
    }

    public function format() {}

    public static function init()
    {
        $fiver = new Money(500, new Currency('USD'));
    }

    public function formaters($format = '')
    {
        $format = $format ?: $this->defaultFormat;

        return $this->formaters[$format] ?? ($this->formaters[$format] = $this->getFormater($format));
    }

    public function getFormater($format)
    {
        // if ($format == 'decimal') {
        //     return new DecimalMoneyFormatter(new ISOCurrencies());
        // }

        // $numberFormatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
        // $intlFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

        // $intlFormatter = new DecimalMoneyFormatter(new ISOCurrencies());
    }

    // $formater = function ($locale = null) {
    //     $locale = $locale ?? config('app.locale');      // 地区
    //     $currency = 'EUR';                              // 货币符号

    //     $numberFormatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
    //     $intlFormatter = new IntlLocalizedDecimalFormatter($numberFormatter, new ISOCurrencies());

    //     $intlFormatter = new DecimalMoneyFormatter(new ISOCurrencies());

    //     $bitcoinFormatter = new BitcoinMoneyFormatter(7, new BitcoinCurrencies());

    //     $moneyFormatter = new AggregateMoneyFormatter([
    //         $currency => $intlFormatter,
    //         'XBT' => $bitcoinFormatter,
    //     ]);

    //     return $moneyFormatter;
    // };

}
