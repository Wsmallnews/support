<?php

namespace Wsmallnews\Support\Features\Sms\Message;

use Carbon\Carbon;
use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Message as EasySmsMessage;
use Wsmallnews\Support\Exceptions\SupportException;
use Wsmallnews\Support\Models\SmsLog;

/** SMS verification code message. */
class CodeMessage extends EasySmsMessage
{
    /** Delivery type. */
    protected $event;

    protected PhoneNumberInterface $mobile;

    protected $code;

    protected $expire = 300;

    protected $maxTimes = 3;

    protected $contentTemplate;

    public function __construct()
    {
        $this->contentTemplate = __('sn-support::support.sms.content_template');
    }

    /**
     * Initialise.
     *
     * @param  string  $event
     * @return $this
     */
    public function init(PhoneNumberInterface $mobile, $event)
    {
        // Fire the event
        $this->event = $event;

        $this->mobile = $mobile;

        $this->makeCode();

        return $this;
    }

    /**
     * Payload including the code.
     *
     * @return void
     */
    public function getData(?GatewayInterface $gateway = null)
    {
        $data = parent::getData($gateway);
        if (! $data) {
            $data = [
                'code' => $this->code ?: $this->makeCode(),
            ];
        }

        return $data;
    }

    /**
     * Return message content.
     *
     * @return string
     */
    public function getContent(?GatewayInterface $gateway = null)
    {
        $content = parent::getContent($gateway);
        if (! $content) {
            $gatewayConfig = $gateway->getConfig();

            $content = $this->contentTemplate;
            $content = str_replace('{code}', $this->code, $content);
            $content = str_replace('{minutes}', (int) ($this->expire / 60), $content);

            // Some gateways append the signature to the content
            if ($content && in_array($gateway->getName(), ['smsbao'])) {
                if (mb_substr((string) $content, 0, 1) != '【' && ! empty($gatewayConfig['sign_name'])) {
                    $content = '【' . $gatewayConfig['sign_name'] . '】' . $content;
                }
            }
        }

        return $content;
    }

    /**
     * Get the template.
     *
     * @return string
     */
    public function getTemplate(?GatewayInterface $gateway = null)
    {
        $template = parent::getTemplate($gateway);

        if (! $template) {
            $gatewayConfig = $gateway->getConfig();
            $templates = array_column($gatewayConfig['template'], null, 'event');
            $template = isset($templates[$this->event]) && $templates[$this->event] ? ($templates[$this->event]['value'] ?? null) : null;
        }

        return $template;
    }

    /**
     * Generate a random code.
     *
     * @return string
     */
    private function makeCode()
    {
        $this->code = mt_rand(1000, 9999);

        $smsLog = SmsLog::where('event', $this->event)
            ->where('mobile', $this->mobile->getNumber())
            ->where('idd_code', $this->mobile->getIDDCode())
            ->first();
        $smsLog = $smsLog ?: new SmsLog;

        $smsLog->idd_code = $this->mobile->getIDDCode();
        $smsLog->mobile = $this->mobile->getNumber();
        $smsLog->event = $this->event;
        $smsLog->code = $this->code;
        $smsLog->times = 0;
        $smsLog->ip_address = request()->ip();
        $smsLog->created_at = Carbon::now();
        $smsLog->save();

        return $this->code;
    }

    /**
     * Verify a submitted code.
     *
     * @param  string  $exception
     */
    public function check(string $code, bool $exception = true): bool
    {
        $smsLog = SmsLog::where('event', $this->event)
            ->where('mobile', $this->mobile->getNumber())
            ->where('idd_code', $this->mobile->getIDDCode())
            ->first();

        if (! $smsLog) {
            if ($exception) {
                throw new SupportException(__('sn-support::support.sms.code_incorrect'));
            }

            return false;
        }

        if ($smsLog->created_at < Carbon::now()->subSeconds($this->expire) || $smsLog->times >= $this->maxTimes) {
            $smsLog->delete();
            if ($exception) {
                throw new SupportException(__('sn-support::support.sms.code_incorrect'));
            }

            return false;
        }

        if ($code != $smsLog->code) {
            $smsLog->times = $smsLog->times + 1;
            $smsLog->save();
            if ($exception) {
                throw new SupportException(__('sn-support::support.sms.code_incorrect'));
            }

            return false;
        }

        // Verified: drop the code
        $smsLog->delete();

        return true;
    }
}
