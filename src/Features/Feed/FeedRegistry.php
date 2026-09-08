<?php

namespace Wsmallnews\Support\Features\Feed;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Features\Concerns\MatchesDomain;
use Wsmallnews\Support\Http\Controllers\FeedController;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 通用 RSS feed 注册表：各扩展包在 ServiceProvider::packageBooted() 中注册具名内容流，
 * 站点路由（/feed 与 /feed/{name}，由 support 提供）聚合输出，支持多模块实例（模块名 = 插件 ID）。
 *
 * 与 sitemap 的语义差异：sitemap 是站点级清单（全部模块 URL 合并成一份），feed 是内容更新流
 * （各模块注册自己的具名流，读者按流订阅），因此这里按 name 注册多个流而非聚合为一份：
 *
 *     Feed::config('sn-cms', [
 *         // 模块绑定的域名（取自模块路由配置 routes.domain；null/未声明 = 不限域名，始终参与）
 *         'domain' => 'cms.smallnews.top',
 *     ])->register('sn-cms', 'posts', [
 *         'title' => fn () => $siteName.' - 最新文章',     // 频道标题（渲染期解析）
 *         'label' => fn () => __('最新文章'),              // 短标签（footer 链接文字用）
 *         'description' => fn () => $seoDescription,       // 频道描述（可选）
 *         'link' => fn () => $siteUrl,                     // 频道站点地址（可选）
 *         'limit' => fn () => 50,                          // 本流输出条数上限（渲染期解析）
 *         'items' => fn (): iterable => Post::snScope(...)->published()->get()
 *             ->map(fn ($post) => [
 *                 'title' => $post->title,
 *                 'url' => route('posts.show', $post),     // 绝对 URL
 *                 'description' => $post->description,     // 可选
 *                 'updated_at' => $post->published_at,     // 可选，排序与 pubDate 用
 *             ]),
 *     ]);
 *
 * 端点：根端点 /feed 输出聚合流（合并全部可见模块流，按 updated_at 倒序，总量受
 * config sn-support.feeds.limit 限制）、/feed/{name} 输出具名流；各模块另在自己的
 * 路由前缀组内调用 routes(模块) 一行注册模块端点（如 /cms/feed、/cms/feed/posts，
 * 渲染逻辑复用 support 的 FeedController）——模块端点只输出该模块的流，实现路径前缀
 * 部署（多模块共用域名）下的内容隔离。feed name 全局唯一（URL-facing），
 * 重名注册直接抛异常——注册期暴露好过运行期串流。
 *
 * 域名过滤（与 sitemap 共用 MatchesDomain，开关 config sn-support.feeds.domain_filter）：
 * 模块声明了 domain 且与当前请求域名不匹配时，该模块的流不参与任何输出（独立域名部署
 * 下根端点天然只含本域模块的流）。
 *
 * 频道元数据：模块聚合端点（/cms/feed）的 title/description/link 从 config(模块, ...) 声明
 * 解析（支持闭包），未声明时回退 app.name / null / 首页；具名流从注册项自身解析。
 *
 * 缓存：渲染结果按流整份缓存（config sn-support.feeds.cache_ttl，秒；null/0 关闭），
 * 缓存键自动携带当前租户、请求域名、模块与流标识；内容变更后可调 flush() 主动清空。
 */
class FeedRegistry
{
    use MatchesDomain;

    /**
     * 模块选项：[moduleName => array]（域名声明等）。
     *
     * @var Collection<string, array<string, mixed>>
     */
    protected Collection $configs;

    /**
     * 已注册的流：[feedName => array{module: string, title?: mixed, label?: mixed, ...}]。
     *
     * @var Collection<string, array<string, mixed>>
     */
    protected Collection $feeds;

    /**
     * 各模块具名端点的完整路由名（routes() 注册时捕获，Laravel 已在注册期把
     * 路由组的 name 前缀拼进路由名，如 sn-cms.feed.show），feedUrl() 生成链接用。
     *
     * @var array<string, string>
     */
    protected array $moduleRouteNames = [];

    public function __construct()
    {
        $this->configs = collect();
        $this->feeds = collect();
    }

    /*
     * ---------- 注册 ----------
     */

    /**
     * 声明模块选项（增量合并，同名键后声明覆盖），可链式调用。
     *
     * 支持的键：domain（绑定域名）、title / description / link（模块聚合端点的
     * 频道元数据，支持闭包渲染期解析）。
     */
    public function config(string $module, array $config): static
    {
        $this->configs->put($module, array_merge($this->configs->get($module, []), $config));

        return $this;
    }

    /**
     * 注册一个具名流（可链式调用）。name 是 URL 的一段，全局唯一。
     *
     * @param  array{title?: Closure|string, label?: Closure|string, description?: Closure|string|null, link?: Closure|string, limit?: Closure|int, items: Closure}  $feed
     */
    public function register(string $module, string $name, array $feed): static
    {
        if (! preg_match('/^[a-z0-9\-]+$/', $name)) {
            throw new \InvalidArgumentException(sprintf('Feed name 只允许小写字母、数字与连字符（收到 %s）', $name));
        }

        if (! ($feed['items'] ?? null) instanceof Closure) {
            throw new \InvalidArgumentException(sprintf('Feed 必须包含 items 闭包（%s.%s）', $module, $name));
        }

        if ($this->feeds->has($name)) {
            $owner = $this->feeds->get($name)['module'] ?? '?';

            throw new \InvalidArgumentException(sprintf('Feed name %s 已被模块 %s 注册（feed 名全局唯一）', $name, $owner));
        }

        $feed['module'] = $module;
        $this->feeds->put($name, $feed);

        return $this;
    }

    /**
     * 移除一个具名流。
     */
    public function forget(string $name): static
    {
        $this->feeds->forget($name);

        return $this;
    }

    /*
     * ---------- 模块端点路由 ----------
     */

    /**
     * 在模块自己的路由组（前缀/域名/中间件）内注册 feed 模块端点路由：
     * {组内}/feed 输出本模块聚合流、{组内}/feed/{name} 输出本模块具名流（仅输出
     * 本模块流，路径前缀部署下多模块共用域名时的内容隔离）。
     *
     * 路径段取 config sn-support.feeds.uri（与根端点一致）；路由名 feed / feed.show
     * 自动带上所在路由组的 name 前缀（如 sn-cms.feed）。渲染逻辑全在 support 的
     * FeedController，模块无需自建控制器。
     *
     * 必须在模块前缀组内调用（继承前缀与中间件），且注册位置需早于
     * navigation/{slug} 等动态段路由，否则静态段会被动态段吃掉。
     */
    public function routes(string $module): void
    {
        $uri = trim((string) SupportUtils::getConfig('feeds.uri', 'feed'), '/');

        Route::get($uri, [FeedController::class, 'moduleIndex'])
            ->defaults('feed_module', $module)
            ->name('feed');

        $show = Route::get($uri . '/{name}', [FeedController::class, 'moduleShow'])
            ->defaults('feed_module', $module)
            ->where('name', '[a-z0-9\-]+')
            ->name('feed.show');

        // 记下完整路由名（含路由组 name 前缀），feedUrl() 与 autodiscoveryTags() 生成链接用
        $this->moduleRouteNames[$module] = (string) $show->getName();
    }

    /*
     * ---------- 读取 ----------
     */

    /**
     * 当前请求可见的全部流（根端点 / footer 的整站视角用）：端点关闭时恒为空集合，
     * 否则按域名过滤。key = feed name。
     *
     * @return Collection<string, array<string, mixed>>
     */
    public function available(): Collection
    {
        if (SupportUtils::getConfig('feeds.enabled', true) !== true) {
            return collect();
        }

        return $this->feeds->filter(fn (array $feed): bool => $this->moduleMatchesDomain($feed['module']));
    }

    /**
     * 某模块当前可见的全部流（模块端点 / footer 的模块视角用）：路径前缀部署下
     * 多模块共用域名，footer 与模块端点只展示本模块的流。
     *
     * @return Collection<string, array<string, mixed>>
     */
    public function moduleFeeds(string $module): Collection
    {
        if (SupportUtils::getConfig('feeds.enabled', true) !== true) {
            return collect();
        }

        return $this->feeds->filter(fn (array $feed): bool => $feed['module'] === $module && $this->moduleMatchesDomain($module));
    }

    /**
     * 流的订阅地址：模块已注册端点路由时走模块端点（如 /cms/feed/posts，隔离形态），
     * 否则回退根端点（如 /feed/posts）。经 sn_route 生成（多租户时自动追加租户参数）。
     */
    public function feedUrl(string $name): string
    {
        $feed = $this->feeds->get($name);

        $routeName = ($feed && isset($this->moduleRouteNames[$feed['module']]))
            ? $this->moduleRouteNames[$feed['module']]
            : 'sn-support.feed.show';

        return sn_route($routeName, ['name' => $name]);
    }

    /**
     * 某模块的 RSS autodiscovery <link> 标签（layout <head> 中输出，供浏览器/阅读器
     * 自动发现订阅地址），逐流一条；模块视角（仅本模块的流）。
     */
    public function autodiscoveryTags(string $module): HtmlString
    {
        $lines = [];

        foreach ($this->moduleFeeds($module) as $name => $feed) {
            $lines[] = sprintf(
                '<link rel="alternate" type="application/rss+xml" title="%s" href="%s" />',
                e((string) value($feed['title'] ?? null ?: config('app.name'))),
                e($this->feedUrl($name)),
            );
        }

        return new HtmlString(implode("\n", $lines));
    }

    /**
     * 按名取一条可见流（不存在或域名不匹配时返回 null；传入 $module 时还要求流属于该模块）。
     *
     * @return array<string, mixed>|null
     */
    public function getFeed(string $name, ?string $module = null): ?array
    {
        $feed = $this->feeds->get($name);

        if (! $feed || ($module !== null && $feed['module'] !== $module)) {
            return null;
        }

        return $this->moduleMatchesDomain($feed['module']) ? $feed : null;
    }

    /*
     * ---------- 渲染 ----------
     */

    /**
     * 渲染 RSS 2.0：name 为 null 输出聚合流（合并全部可见流，按 updated_at 倒序），
     * 否则输出单个具名流（结果按流整份缓存）。
     */
    public function render(?string $name = null): HtmlString
    {
        return $this->memoizedRender($name, null);
    }

    /**
     * 渲染模块端点 RSS 2.0：name 为 null 输出该模块的聚合流（仅合并该模块的可见流），
     * 否则输出属于该模块的具名流（结果按流整份缓存）。
     */
    public function renderModule(string $module, ?string $name = null): HtmlString
    {
        return $this->memoizedRender($name, $module);
    }

    /**
     * 清空全部流（各端点：根 / 模块 × 聚合 / 具名）的渲染缓存。
     */
    public function flush(): static
    {
        $modules = $this->feeds->map(fn (array $feed): string => $feed['module'])->unique()->values()->all();
        $names = $this->feeds->keys()->all();

        foreach ($modules as $module) {
            // 模块聚合端点
            Cache::forget($this->cacheKey(null, $module));

            // 模块具名端点
            foreach ($names as $name) {
                Cache::forget($this->cacheKey($name, $module));
            }
        }

        // 根聚合端点与根具名端点
        foreach ($names as $name) {
            Cache::forget($this->cacheKey($name));
        }

        Cache::forget($this->cacheKey(null));

        return $this;
    }

    /*
     * ---------- 内部 ----------
     */

    protected function memoizedRender(?string $name, ?string $module): HtmlString
    {
        $ttl = SupportUtils::getConfig('feeds.cache_ttl', 3600);
        $xml = ($ttl === null || $ttl <= 0)
            ? $this->compile($name, $module)
            : Cache::remember($this->cacheKey($name, $module), $ttl, fn (): string => $this->compile($name, $module));

        return new HtmlString($xml);
    }

    /**
     * 模块域名过滤开关的配置键（SupportUtils::getConfig 的点号参数名）。
     */
    protected function domainFilterKey(): string
    {
        return 'feeds.domain_filter';
    }

    /**
     * 缓存键：携带租户、请求域名、模块与流标识，避免跨租户/跨域名/跨流串缓存。
     */
    protected function cacheKey(?string $name, ?string $module = null): string
    {
        $tenant = function_exists('has_tenancy') && has_tenancy() ? (current_tenant()?->id ?? 'none') : 'global';
        $host = request()->getHost();

        return 'sn-support:feed:' . $tenant . ':' . $host . ':' . ($module ?? '-') . ':' . ($name ?? '-aggregate');
    }

    /**
     * 聚合或输出单个流并拼接 RSS 2.0 XML（此时尚未缓存，逐流执行注册的 items 闭包）。
     *
     * 四种端点形态：全站聚合（/feed）、全站具名（/feed/{name}）、
     * 模块聚合（/cms/feed）、模块具名（/cms/feed/{name}）。
     */
    protected function compile(?string $name = null, ?string $module = null): string
    {
        $items = [];

        if ($name === null && $module === null) {
            // 全站聚合：合并全部可见模块流
            foreach ($this->available() as $feed) {
                $items = array_merge($items, $this->resolveItems($feed));
            }

            $channel = [
                'title' => config('app.name'),
                'description' => null,
                'link' => url('/'),
            ];
        } elseif ($name === null) {
            // 模块聚合：仅合并该模块的可见流
            foreach ($this->moduleFeeds($module) as $feed) {
                $items = array_merge($items, $this->resolveItems($feed));
            }

            $config = $this->configs->get($module, []);

            $channel = [
                'title' => (string) (value($config['title'] ?? null) ?: config('app.name')),
                'description' => value($config['description'] ?? null),
                'link' => (string) (value($config['link'] ?? null) ?: url('/')),
            ];
        } else {
            // 具名流（全站 /feed/{name} 与模块 /cms/feed/{name} 共用，getFeed 已校验归属）
            $feed = $this->getFeed($name, $module) ?? [];
            $items = $this->resolveItems($feed);

            $channel = [
                'title' => (string) value($feed['title'] ?? null ?: config('app.name')),
                'description' => value($feed['description'] ?? null),
                'link' => (string) value($feed['link'] ?? null ?: url('/')),
            ];
        }

        // 聚合流按 updated_at 倒序后总量截断（具名流沿用注册方自身的顺序与 limit）
        if ($name === null) {
            usort($items, fn (array $a, array $b): int => $this->timestamp($b['updated_at'] ?? null) <=> $this->timestamp($a['updated_at'] ?? null));
            $items = array_slice($items, 0, (int) SupportUtils::getConfig('feeds.limit', 50));
        }

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<rss version="2.0">',
            '    <channel>',
            '        <title>' . e($channel['title']) . '</title>',
            '        <link>' . e($channel['link']) . '</link>',
            '        <description>' . e((string) $channel['description']) . '</description>',
            '        <language>' . str_replace('_', '-', app()->getLocale()) . '</language>',
            '        <lastBuildDate>' . now()->toRfc2822String() . '</lastBuildDate>',
        ];

        foreach ($items as $item) {
            $url = (string) ($item['url'] ?? '');
            $pubDate = $this->formatPubDate($item['updated_at'] ?? null);

            $lines[] = '        <item>';
            $lines[] = '            <title>' . e((string) ($item['title'] ?? '')) . '</title>';
            $lines[] = '            <link>' . e($url) . '</link>';
            $lines[] = '            <guid>' . e($url) . '</guid>';

            if (filled($item['description'] ?? null)) {
                $lines[] = '            <description>' . e((string) $item['description']) . '</description>';
            }

            if ($pubDate !== null) {
                $lines[] = '            <pubDate>' . $pubDate . '</pubDate>';
            }

            $lines[] = '        </item>';
        }

        $lines[] = '    </channel>';
        $lines[] = '</rss>';

        return implode("\n", $lines);
    }

    /**
     * 执行流的 items 闭包并应用该流的 limit（limit 支持闭包，渲染期解析）。
     *
     * @param  array<string, mixed>  $feed
     * @return array<int, array<string, mixed>>
     */
    protected function resolveItems(array $feed): array
    {
        $items = ($feed['items'] ?? null) ? ($feed['items'])() : [];
        $limit = (int) value($feed['limit'] ?? 50);

        $resolved = [];

        foreach ($items as $item) {
            if (blank($item['url'] ?? null)) {
                continue;
            }

            $resolved[] = $item;
        }

        return $limit > 0 ? array_slice($resolved, 0, $limit) : $resolved;
    }

    /**
     * 聚合排序用的日期时间戳（无法解析按 0 计）。
     */
    protected function timestamp(mixed $date): int
    {
        try {
            return filled($date) ? Carbon::parse($date)->getTimestamp() : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * pubDate 归一化为 RFC 2822（RSS 2.0 规范），无法解析时丢弃该字段。
     */
    protected function formatPubDate(mixed $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->toRfc2822String();
        } catch (\Throwable) {
            return null;
        }
    }
}
