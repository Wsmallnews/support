<?php

namespace Wsmallnews\Support\Features\Sms\Message;

use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Message as EasySmsMessage;
use Wsmallnews\Support\Exceptions\SupportException;
use Wsmallnews\Support\Models\SmsLog;

/**
 * 短信验证码消息类
 */
class CodeMessage extends EasySmsMessage
{
    /**
     * 发送类型
     */
    protected $event;

    protected PhoneNumberInterface $mobile;

    protected $code;

    protected $expire = 300;

    protected $maxTimes = 3;

    protected $contentTemplate = '您的短信验证码为：{code}, {minutes}分钟有效，请勿告诉他人';


    /**
     * 初始化
     *
     * @param PhoneNumberInterface $mobile
     * @param string $event
     * @return $this
     */
    public function init(PhoneNumberInterface $mobile, $event)
    {
        // 触发事件
        $this->event = $event;

        $this->mobile = $mobile;

        $this->makeCode();

        return $this;
    }


    /**
     * 获取数据，包含验证码
     *
     * @param GatewayInterface $gateway
     * @return void
     */
    public function getData(?GatewayInterface $gateway = null)
    {
        $data = parent::getData($gateway);
        if (!$data) {
            $data = [
                'code' => $this->code ?: $this->makeCode()
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
        if (!$content) {
            $gatewayConfig = $gateway->getConfig();

            $content = $this->contentTemplate;
            $content = str_replace('{code}', $this->code, $content);
            $content = str_replace('{minutes}', (int)($this->expire / 60), $content);

            // 部分发送渠道 content 上追加 短信签名
            if ($content && in_array($gateway->getName(), ['smsbao'])) {
                if ('【' != mb_substr((string)$content, 0, 1) && !empty($gatewayConfig['sign_name'])) {
                    $content = '【' . $gatewayConfig['sign_name'] . '】' . $content;
                }
            }
        }

        return $content;
    }


    /**
     * 获取模板
     *
     * @param GatewayInterface $gateway
     * @return string
     */
    public function getTemplate(?GatewayInterface $gateway = null)
    {
        $template = parent::getTemplate($gateway);

        if (!$template) {
            $gatewayConfig = $gateway->getConfig();
            $templates = array_column($gatewayConfig['template'], null, 'event');
            $template = isset($templates[$this->event]) && $templates[$this->event] ? ($templates[$this->event]['value'] ?? null) : null;
        }

        return $template;
    }



    /**
     * 生成随机验证码
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
        $smsLog = $smsLog ?: new SmsLog();

        $smsLog->idd_code = $this->mobile->getIDDCode();
        $smsLog->mobile = $this->mobile->getNumber();
        $smsLog->event = $this->event;
        $smsLog->code = $this->code;
        $smsLog->times = 0;
        $smsLog->ip_address = request()->ip();
        $smsLog->created_at = \Carbon\Carbon::now();
        $smsLog->save();

        return $this->code;
    }


    /**
     * 验证短信验证码
     *
     * @param string $code
     * @param string $exception
     * @return boolean
     */
    public function check(string $code, bool $exception = true): bool
    {
        $smsLog = SmsLog::where('event', $this->event)
            ->where('mobile', $this->mobile->getNumber())
            ->where('idd_code', $this->mobile->getIDDCode())
            ->first();

        if (!$smsLog) {
            if ($exception) throw new SupportException('验证码不正确');
            return false;
        }

        if ($smsLog->created_at < \Carbon\Carbon::now()->subSeconds($this->expire) || $smsLog->times >= $this->maxTimes) {
            $smsLog->delete();
            if ($exception) throw new SupportException('验证码不正确');
            return false;
        }

        if ($code != $smsLog->code) {
            $smsLog->times = $smsLog->times + 1;
            $smsLog->save();
            if ($exception) throw new SupportException('验证码不正确');
            return false;
        }

        // 验证成功，删除验证码
        $smsLog->delete();

        return true;
    }
}
