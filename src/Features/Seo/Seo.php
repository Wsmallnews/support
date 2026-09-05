<?php

namespace Wsmallnews\Support\Features\Seo;

use Closure;
use Illuminate\Support\HtmlString;
use Stringable;

/**
 * SEO 渲染器：模块默认值注册（启动期）+ 页面上下文持有与渲染（渲染期）。
 *
 * 页面数据声明方法（title/description/image/type/url/robots/jsonLd/article/website 等）
 * 经 __call 转发到当前 SeoPage，见 SeoPage 的公开方法清单。
 *
 * 页面上下文（SeoPage）经 init() 按页重建——由 seo-init 中间件在每个会输出 <head> 的
 * 请求（首屏）自动调用，测试或特殊场景可直调：
 *
 *     Seo::init('sn-cms')->title($post->title)->description($post->description)->article([...]);
 *
 * Livewire 语义：update 请求不经过页面路由中间件、也不重渲染 layout（head 不输出），
 * 因此 init 只在首屏执行即足够；update 时组件 render() 里的声明是落在内存对象上的
 * 空转，无输出路径、成本可忽略。Octane 下 init 由中间件保证每屏重置，无跨请求泄漏。
 *
 * 多模块支持（与 SearchRegistry 同构）：站点默认值（站点名、默认描述、默认分享图、
 * favicon、统计代码）由各扩展包在 ServiceProvider::packageBooted() 中经 config(模块名, ...)
 * 按模块注册，互不覆盖；模块名 = 插件 ID（如 sn-cms / sn-shop），由 seo-init:模块名
 * 路由中间件参数声明归属。配置值支持数组或闭包（闭包在渲染时才解析，天然跟随当前
 * 请求/租户上下文——多租户下各模块 Settings 走各自仓库）。未注册配置回退全局兜底（app.name）。
 *
 * URL 约定：canonical / og:url 缺省取当前请求地址（自动携带多租户域名或路径前缀）；
 * 图片地址允许相对路径，输出前统一转为绝对地址。
 *
 * @method SeoPage title(?string $title)
 * @method SeoPage description(?string $description)
 * @method SeoPage image(?string $image)
 * @method SeoPage type(string $type)
 * @method SeoPage url(?string $url)
 * @method SeoPage robots(?string $robots)
 * @method SeoPage jsonLd(array $schema)
 * @method SeoPage article(array $extra = [])
 * @method SeoPage website()
 */
class Seo implements Stringable
{
    /**
     * 模块默认值配置：[模块名 => array|Closure]。Closure 在 defaults() 解析时调用，
     * 返回 array{site_name?: string, description?: ?string, image?: ?string, favicon?: ?string, analytics_code?: ?string}
     *
     * @var array<string, array|Closure>
     */
    protected array $configs = [];

    /**
     * 当前页面归属的模块（null = 未声明，走全局兜底）。
     */
    protected ?string $forModule = null;

    /**
     * 已解析的模块默认值缓存（随 init()/config() 变更失效）。
     *
     * @var ?array{site_name: string, description: ?string, image: ?string, favicon: ?string, analytics_code: ?string}
     */
    protected ?array $resolvedDefaults = null;

    /**
     * 当前页面上下文（页面级 SEO 值对象）。
     */
    protected SeoPage $page;

    public function __construct()
    {
        $this->page = new SeoPage;
    }

    /*
     * ---------- 模块注册与解析 ----------
     */

    /**
     * 声明模块的站点默认值（消费方在 ServiceProvider::packageBooted 中调用；同名模块重复声明增量合并）。
     */
    public function config(string $module, array | Closure $config): static
    {
        if (! isset($this->configs[$module])) {
            $this->configs[$module] = $config;      // 首次注册原样保存（闭包保持延迟解析，渲染时才跟随请求/租户）
        } else {
            $this->configs[$module] = array_merge($this->resolveConfig($this->configs[$module]), $this->resolveConfig($config));
        }

        $this->resolvedDefaults = null;

        return $this;
    }

    /**
     * 开始一个页面的 SEO 声明：重建页面上下文（SeoPage）并声明模块归属。
     *
     * 由 seo-init 中间件（带模块名参数）在每个首屏请求自动调用；测试或未走中间件的场景可直调。
     */
    public function init(?string $module = null): static
    {
        $this->forModule = $module;
        $this->resolvedDefaults = null;
        $this->page = new SeoPage;

        return $this;
    }

    /**
     * 当前页面归属的模块名。
     */
    public function getFor(): ?string
    {
        return $this->forModule;
    }

    /**
     * 站点默认值：取当前归属模块的注册配置（闭包此时才解析，跟随请求/租户），逐项回退全局兜底。
     *
     * @return array{site_name: string, description: ?string, image: ?string, favicon: ?string, analytics_code: ?string}
     */
    public function defaults(): array
    {
        return $this->resolvedDefaults ??= $this->resolveDefaults();
    }

    /**
     * @return array{site_name: string, description: ?string, image: ?string, favicon: ?string, analytics_code: ?string}
     */
    protected function resolveDefaults(): array
    {
        $module = $this->forModule;
        $config = ($module !== null && isset($this->configs[$module])) ? $this->resolveConfig($this->configs[$module]) : [];

        return [
            'site_name' => $config['site_name'] ?? config('app.name'),
            'description' => $config['description'] ?? null,
            'image' => $config['image'] ?? null,
            'favicon' => $config['favicon'] ?? null,
            'analytics_code' => $config['analytics_code'] ?? null,
        ];
    }

    protected function resolveConfig(array | Closure $config): array
    {
        return $config instanceof Closure ? $config() : $config;
    }

    /*
     * ---------- 页面上下文 ----------
     */

    /**
     * 当前页面上下文。
     */
    public function page(): SeoPage
    {
        return $this->page;
    }

    /*
     * ---------- 页面数据声明（经 __call 转发到当前 SeoPage，调用方经门面链式使用） ----------
     */

    /**
     * 页面数据声明转发：SeoPage 的全部公开方法（title/description/image/type/url/robots/
     * jsonLd/article/website...）均可直接在 Seo 上调用，新增能力无需改本类。
     *
     * @param  array<int, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (method_exists($this->page, $method)) {
            return $this->page->{$method}(...$parameters);
        }

        throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $method));
    }

    /*
     * ---------- 渲染 ----------
     */

    /**
     * 输出 <head> 中的全部 SEO 标签（页面声明 + 模块默认值兜底）。
     */
    public function render(): HtmlString
    {
        return $this->page->render($this->defaults());
    }

    /**
     * 输出统计代码（layout </body> 前调用）。
     */
    public function renderAnalytics(): HtmlString
    {
        $analytics = $this->defaults()['analytics_code'];

        return new HtmlString(filled($analytics) ? $analytics : '');
    }

    public function __toString(): string
    {
        return $this->render()->toHtml();
    }
}
