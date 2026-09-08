<?php

namespace Wsmallnews\Support\Features\Concerns;

use Illuminate\Support\Collection;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 模块域名过滤（SitemapRegistry / FeedRegistry 共用）：模块经 config(模块, ['domain' => ...])
 * 声明绑定域名后，与当前请求域名不匹配的模块不参与输出（未声明域名的模块始终参与）。
 * 支持路由式域名模式（{tenant:slug}.example.com，占位符按「非点号任意段」匹配）。
 *
 * 使用方需提供 $configs 属性（[模块名 => 模块选项数组]，域名声明在其 domain 键）
 * 与 domainFilterKey()（各自的 domain_filter 配置在 sn-support 下的点号参数名）。
 *
 * @property Collection<string, array<string, mixed>> $configs
 */
trait MatchesDomain
{
    /**
     * 域名过滤开关的配置参数名（sn-support 下的点号路径，默认开启）。
     */
    abstract protected function domainFilterKey(): string;

    /**
     * 模块是否参与当前请求的输出：域名过滤关闭、模块未声明域名、或域名与当前请求匹配时参与。
     */
    protected function moduleMatchesDomain(string $module): bool
    {
        if (SupportUtils::getConfig($this->domainFilterKey(), true) !== true) {
            return true;
        }

        $domain = $this->configs->get($module, [])['domain'] ?? null;

        return blank($domain) || $this->hostMatches($domain, request()->getHost());
    }

    /**
     * 域名模式匹配：精确匹配，或路由式域名模式（{tenant:slug}.example.com，占位符按「非点号任意段」匹配）。
     */
    protected function hostMatches(string $pattern, string $host): bool
    {
        if (strcasecmp($pattern, $host) === 0) {
            return true;
        }

        if (! str_contains($pattern, '{')) {
            return false;
        }

        $regex = str_replace("\x01", '[^.]+', preg_quote(preg_replace('/\{[^}]+\}/', "\x01", $pattern), '/'));

        return (bool) preg_match('/^' . $regex . '$/i', $host);
    }
}
