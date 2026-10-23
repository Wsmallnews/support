<?php

namespace Wsmallnews\Support\Features\Sms;

use Closure;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Overtrue\EasySms\PhoneNumber;
use Wsmallnews\Support\Exceptions\SupportException;
use Wsmallnews\Support\Features\Sms\Message\CodeMessage;

trait Smsable
{
    public static function registerEasySms($config)
    {
        app()->singleton(EasySms::class, function () use ($config) {
            return new EasySms($config);
        });
    }

    public static function extendEasySms($gateway, Closure $callback)
    {
        $easySms = app(EasySms::class);

        // Register
        $easySms->extend($gateway, function ($gatewayConfig) use ($callback) {
            // $gatewayConfig comes from `gateways.mygateway` in the config
            return $callback($gatewayConfig);
        });
    }

    public static function sendSmsCode(string | array $mobile, string $event, array $gateways = [])
    {
        $easySms = app(EasySms::class);

        try {
            // International SMS
            $mobile = is_array($mobile) ? $mobile : [$mobile, 86];
            $mobileInstance = new PhoneNumber($mobile[0], $mobile[1] ?? 86);

            $result = $easySms->send($mobileInstance, (new CodeMessage)->init($mobileInstance, $event), $gateways);
        } catch (NoGatewayAvailableException $e) {
            // Log the result
            exception_log($e, 'smsNoGatewayExceptionError', json_encode($e->getLastException()->getMessage(), JSON_UNESCAPED_UNICODE));

            // Rethrow
            throw new SupportException($e->getLastException()->getMessage());
        } catch (\Exception $e) {
            // Log the failure
            exception_log($e, 'smsEasysmsError');

            // Rethrow
            throw new SupportException(__('sn-support::support.sms.send_failed'));
        }

        return $result;
    }

    public static function checkSmsCode(string | array $mobile, string $event, string $code, bool $exception = true)
    {
        // International SMS
        $mobile = is_array($mobile) ? $mobile : [$mobile, 86];
        $mobileInstance = new PhoneNumber($mobile[0], $mobile[1] ?? 86);

        return (new CodeMessage)->init($mobileInstance, $event)->check($code, $exception);
    }
}
