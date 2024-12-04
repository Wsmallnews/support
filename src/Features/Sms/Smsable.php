<?php

namespace Wsmallnews\Support\Features\Sms;

use Closure;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Overtrue\EasySms\PhoneNumber;
use Wsmallnews\Support\Features\Sms\Message\CodeMessage;
use Wsmallnews\Support\Exceptions\SupportException;

trait Smsable
{
    public static function registerEasySms($config)
    {
        echo "aaa";exit;
        app()->singleton(EasySms::class, function () use ($config) {
            return new EasySms($config);
        });
    }


    public static function extendEasySms($gateway, Closure $callback)
    {
        $easySms = app(EasySms::class);

        // 注册
        $easySms->extend($gateway, function ($gatewayConfig) use ($callback) {
            // $gatewayConfig 来自配置文件里的 `gateways.mygateway`
            return $callback($gatewayConfig);
        });
    }


    public static function sendSmsCode(string | array $mobile, string $event, array $gateways = [])
    {
        $easySms = app(EasySms::class);

        try {
            // 支持国际短信
            $mobile = is_array($mobile) ? $mobile : [$mobile, 86];
            $mobileInstance = new PhoneNumber($mobile[0], $mobile[1] ?? 86);

            $result = $easySms->send($mobileInstance, (new CodeMessage)->init($mobileInstance, $event), $gateways);
        } catch (NoGatewayAvailableException $e) {
            // 记录发送结果日志
            exception_log($e, 'smsNoGatewayExceptionError', json_encode($e->getLastException()->getMessage(), JSON_UNESCAPED_UNICODE));
            // 抛出异常
            throw new SupportException($e->getLastException()->getMessage());
        } catch (\Exception $e) {
            // 记录错误结果日志
            exception_log($e, 'smsEasysmsError');
            // 抛出异常
            throw new SupportException('短信发送失败');
        }

        return $result;
    }



    public static function checkSmsCode(string | array $mobile, string $event, string $code, bool $exception = true)
    {
        // 支持国际短信
        $mobile = is_array($mobile) ? $mobile : [$mobile, 86];
        $mobileInstance = new PhoneNumber($mobile[0], $mobile[1] ?? 86);

        return (new CodeMessage)->init($mobileInstance, $event)->check($code, $exception);
    }
}
