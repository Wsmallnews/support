<?php

namespace Wsmallnews\Support\Features\Sitemap;

use Carbon\CarbonInterface;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

/**
 * 通用 sitemap/robots 注册表：各扩展包在 ServiceProvider::packageBooted() 中注册 URL 来源
 * 与 robots 规则，站点路由（/sitemap.xml、/robots.txt，由 support 提供）聚合输出，
 * 支持多模块实例（模块名 = 插件 ID，互不污染）。
 *
 * 注册姿势（与 SearchRegistry 同构：先 config 声明模块选项，再 registers 注册来源，
 * robots 注册禁爬规则）：
 *
 *     Sitemap::config('sn-cms', [
 *         // 模块绑定的域名（取自模块路由配置 routes.domain；null/未声明 = 不限域名，始终参与）
 *         'domain' => 'cms.smallnews.top',
 *     ])->registers('sn-cms', [
 *         [
 *             'key' => 'posts',                       // 来源标识（仅作排查用）
 *             'urls' => fn (): iterable => Post::snScope(...)->published()->get()
 *                 ->map(fn ($post) => [
 *                     'loc' => route('posts.show', $post),   // 绝对 URL
 *                     'lastmod' => $post->updated_at,        // 可选，Carbon 或日期字符串
 *                 ]),
 *         ],
 *     ])->robots('sn-cms', [
 *         'disallow' => ['cms/search'],               // 模块自有的禁爬路径（用模块自己的路由配置拼接）
 *     ]);
 *
 * 域名过滤（config sn-support.sitemap.domain_filter，默认开启）：模块声明了 domain 且与
 * 当前请求域名不匹配时，该模块的 sitemap 来源与 robots 规则都不参与输出——cms.smallnews.top
 * 与 shop.smallnews.top 各自只输出自己模块的内容。支持路由式域名模式（{tenant:slug}.example.com，
 * 占位符按「非点号任意段」匹配）。关闭过滤则聚合全部模块。
 *
 * robots 输出：Filament 后台面板路径自动发现（面板路径可配置且可有多个，无需注册）+
 * 各模块注册的禁爬路径 + Sitemap: 行（始终指向当前域名的 sitemap.xml）。
 *
 * 缓存：sitemap 渲染结果整份缓存（config sn-support.sitemap.cache_ttl，秒；null/0 关闭），
 * 缓存键自动携带当前租户、请求域名与模块集合；内容变更后可调 flush() 主动清空。
 */
class SitemapRegistry
{
    /**
     * 模块选项：[moduleName => array]（已声明的键；未声明的键走全局兜底）。
     *
     * @var Collection<string, array<string, mixed>>
     */
    protected Collection $configs;

    /**
     * 已注册的来源：[moduleName => Collection<int, array{key: string, urls: Closure}>]
     *
     * @var Collection<string, Collection<int, array<string, mixed>>>
     */
    protected Collection $modules;

    /**
     * 已注册的 robots 规则：[moduleName => array{disallow?: array<int, string>}]
     *
     * @var Collection<string, array<string, mixed>>
     */
    protected Collection $robotsRules;

    public function __construct()
    {
        $this->configs = collect();
        $this->modules = collect();
        $this->robotsRules = collect();
    }

    /*
     * ---------- 注册 ----------
     */

    /**
     * 声明模块选项（增量合并，同名键后声明覆盖），可链式调用。
     */
    public function config(string $module, array $config): static
    {
        $this->configs->put($module, array_merge($this->configs->get($module, []), $config));

        return $this;
    }

    /**
     * 注册一个模块的 URL 来源（可链式调用）。
     */
    public function register(string $module, array $source): static
    {
        if (blank($source['key'] ?? null) || ! ($source['urls'] ?? null) instanceof Closure) {
            throw new \InvalidArgumentException(sprintf('Sitemap 来源必须包含 key 与 urls 闭包（模块 %s）', $module));
        }

        $this->modules->put($module, ($this->modules->get($module, collect()))->push($source));

        return $this;
    }

    /**
     * 批量注册一个模块的 URL 来源。
     */
    public function registers(string $module, array $sources): static
    {
        foreach ($sources as $source) {
            $this->register($module, $source);
        }

        return $this;
    }

    /**
     * 注册一个模块的 robots 规则（disallow 为禁爬路径列表，用模块自己的路由配置拼接）。
     */
    public function robots(string $module, array $rules): static
    {
        $this->robotsRules->put($module, array_merge($this->robotsRules->get($module, []), $rules));

        return $this;
    }

    /**
     * 移除一个模块的全部来源与规则。
     */
    public function forget(string $module): static
    {
        $this->configs->forget($module);
        $this->modules->forget($module);
        $this->robotsRules->forget($module);

        return $this;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getSources(string $module): Collection
    {
        return $this->modules->get($module, collect());
    }

    /**
     * 已注册来源的模块名列表。
     *
     * @return array<int, string>
     */
    public function getModules(): array
    {
        return $this->modules->keys()->values()->all();
    }

    /*
     * ---------- 渲染 ----------
     */

    /**
     * 聚合匹配当前域名的模块来源，渲染 sitemap XML（结果整份缓存）。
     */
    public function render(): HtmlString
    {
        $ttl = config('sn-support.sitemap.cache_ttl', 3600);
        $xml = ($ttl === null || $ttl <= 0)
            ? $this->compile()
            : Cache::remember($this->cacheKey(), $ttl, fn (): string => $this->compile());

        return new HtmlString($xml);
    }

    /**
     * 渲染 robots.txt：面板路径自动发现 + 模块注册的禁爬路径 + Sitemap 行。
     */
    public function renderRobots(): string
    {
        $lines = ['User-agent: *'];

        // Filament 后台面板路径自动发现（路径可配置且可有多个，无需各模块注册）
        foreach (Filament::getPanels() as $panel) {
            if (filled($panel->getPath())) {
                $lines[] = 'Disallow: /' . trim($panel->getPath(), '/');
            }
        }

        // 各模块注册的禁爬路径（同样走域名过滤）
        foreach ($this->robotsRules as $module => $rules) {
            if (! $this->moduleMatchesDomain($module)) {
                continue;
            }

            foreach ($rules['disallow'] ?? [] as $path) {
                if (filled($path)) {
                    $lines[] = 'Disallow: /' . trim($path, '/');
                }
            }
        }

        $lines[] = '';
        $lines[] = 'Sitemap: ' . route('sn-support.sitemap');

        return implode("\n", array_values(array_unique($lines))) . "\n";
    }

    /**
     * 清空 sitemap 缓存（内容变更后主动刷新用）。
     */
    public function flush(): static
    {
        Cache::forget($this->cacheKey());

        return $this;
    }

    /*
     * ---------- 内部 ----------
     */

    /**
     * 缓存键：携带租户、请求域名与模块集合，避免跨租户/跨域名/跨模块串缓存。
     */
    protected function cacheKey(): string
    {
        $tenant = function_exists('has_tenancy') && has_tenancy() ? (current_tenant()?->id ?? 'none') : 'global';
        $host = request()->getHost();

        return 'sn-support:sitemap:' . $tenant . ':' . $host . ':' . md5(implode(',', $this->getModules()));
    }

    /**
     * 聚合匹配当前域名的模块来源并拼接 XML（此时尚未缓存，逐来源执行注册的闭包）。
     */
    protected function compile(): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($this->modules as $module => $sources) {
            if (! $this->moduleMatchesDomain($module)) {
                continue;
            }

            foreach ($sources as $source) {
                foreach (($source['urls'])() as $url) {
                    $loc = e($url['loc'] ?? '');
                    if (blank($loc)) {
                        continue;
                    }

                    $lastmod = $this->formatLastmod($url['lastmod'] ?? null);
                    $lines[] = '    <url><loc>' . $loc . '</loc>' . ($lastmod ? '<lastmod>' . $lastmod . '</lastmod>' : '') . '</url>';
                }
            }
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }

    /**
     * 模块是否参与当前请求的输出：域名过滤关闭、模块未声明域名、或域名与当前请求匹配时参与。
     */
    protected function moduleMatchesDomain(string $module): bool
    {
        if (config('sn-support.sitemap.domain_filter', true) !== true) {
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

    /**
     * lastmod 归一化为 W3C 日期（YYYY-MM-DD），无法解析时丢弃该字段。
     */
    protected function formatLastmod(mixed $lastmod): ?string
    {
        if (blank($lastmod)) {
            return null;
        }

        if ($lastmod instanceof CarbonInterface) {
            return $lastmod->toDateString();
        }

        try {
            return \Illuminate\Support\Carbon::parse($lastmod)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
