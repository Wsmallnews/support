<?php

namespace Wsmallnews\Support\Features\Seo;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * 页面级 SEO 值对象：一次页面渲染对应一个实例（由 Seo::init() 重建），链式声明当前页的
 * title/description/image 等数据，render() 时接收模块默认值（由 Seo 注册器解析）完成兜底
 * 并输出全部 <head> 标签。
 *
 * 与 Seo（注册器）的分工：Seo 持有各模块注册的站点默认值配置并解析当前页面归属；
 * 本类只承载「当前这一页」的数据，随页面渲染生灭，可独立实例化、独立测试。
 */
class SeoPage
{
    protected ?string $title = null;

    protected ?string $description = null;

    protected ?string $image = null;

    protected string $type = 'website';

    protected ?string $url = null;

    protected ?string $robots = null;

    /**
     * @var array<int, array>
     */
    protected array $jsonLds = [];

    /**
     * Article 结构化数据的文章特有字段（article() 声明，render 时组装完整节点）。
     *
     * @var ?array{datePublished?: ?string, dateModified?: ?string, author?: ?string}
     */
    protected ?array $articleExtra = null;

    /**
     * 是否输出 WebSite 结构化数据（首页用，name/url 由渲染器取模块默认值与当前请求）。
     */
    protected bool $website = false;

    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function image(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function url(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function robots(?string $robots): static
    {
        $this->robots = $robots;

        return $this;
    }

    /**
     * 附加一个 JSON-LD 结构化数据节点（schema.org 数组结构，原样输出）。
     */
    public function jsonLd(array $schema): static
    {
        $this->jsonLds[] = $schema;

        return $this;
    }

    /**
     * 声明 Article 结构化数据（同时把 og:type 切为 article）。
     *
     * headline/description/image/url 从已声明的页面数据自动组装（含模块默认值兜底与绝对地址转换），
     * 调用方只需传文章特有字段：
     * - datePublished / dateModified：ISO 8601 时间字符串
     * - author：作者名（schema.org 的 author = 写内容的人；未传时兜底站点组织）
     * publisher 固定为站点组织（schema.org 的 publisher = 发布内容的机构，即站点本身）。
     */
    public function article(array $extra = []): static
    {
        $this->articleExtra = $extra;
        $this->type('article');

        return $this;
    }

    /**
     * 声明输出 WebSite 结构化数据（首页用）。
     */
    public function website(): static
    {
        $this->website = true;

        return $this;
    }

    /**
     * 完整页面标题（带站点名后缀；首页等未声明标题时仅输出站点名）。
     */
    public function resolveTitle(string $siteName): string
    {
        return filled($this->title) ? "{$this->title} - {$siteName}" : $siteName;
    }

    /**
     * 输出 <head> 中的全部 SEO 标签。
     *
     * @param  array{site_name: string, description: ?string, image: ?string, favicon: ?string, analytics_code: ?string}  $defaults  模块默认值（Seo 注册器解析）
     */
    public function render(array $defaults): HtmlString
    {
        $title = $this->resolveTitle($defaults['site_name']);
        $description = filled($this->description) ? $this->description : $defaults['description'];
        $image = filled($this->image) ? $this->image : $defaults['image'];
        $url = filled($this->url) ? $this->url : request()->url();
        $locale = str_replace('_', '-', app()->getLocale());

        $tags = [];

        $tags[] = '<title>' . e($title) . '</title>';

        if (filled($description)) {
            $tags[] = '<meta name="description" content="' . e($description) . '">';
        }

        if (filled($this->robots)) {
            $tags[] = '<meta name="robots" content="' . e($this->robots) . '">';
        }

        // canonical
        $tags[] = '<link rel="canonical" href="' . e($this->absoluteUrl($url)) . '">';

        // favicon
        if (filled($defaults['favicon'])) {
            $tags[] = '<link rel="icon" href="' . e($this->absoluteUrl($defaults['favicon'])) . '">';
        }

        // OpenGraph
        $og = [
            'og:site_name' => $defaults['site_name'],
            'og:title' => $title,
            'og:type' => $this->type,
            'og:url' => $this->absoluteUrl($url),
            'og:locale' => $locale,
        ];

        if (filled($description)) {
            $og['og:description'] = $description;
        }

        if (filled($image)) {
            $og['og:image'] = $this->absoluteUrl($image);
        }

        foreach ($og as $property => $content) {
            $tags[] = '<meta property="' . e($property) . '" content="' . e($content) . '">';
        }

        // Twitter Card
        $tags[] = '<meta name="twitter:card" content="' . (filled($image) ? 'summary_large_image' : 'summary') . '">';

        // JSON-LD：网站结构化数据（首页）
        if ($this->website) {
            $this->appendJsonLd($tags, [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $defaults['site_name'],
                'url' => $this->absoluteUrl($url),
            ]);
        }

        // JSON-LD：文章结构化数据（headline/description/image/url 复用上面已解析并兜底的页面数据）
        if ($this->articleExtra !== null) {
            $extra = $this->articleExtra;
            $authorName = $extra['author'] ?? null;

            $this->appendJsonLd($tags, array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $this->title,
                'description' => $description,
                'image' => filled($image) ? $this->absoluteUrl($image) : null,
                'url' => $this->absoluteUrl($url),
                'datePublished' => $extra['datePublished'] ?? null,
                'dateModified' => $extra['dateModified'] ?? null,
                'author' => filled($authorName)
                    ? ['@type' => 'Person', 'name' => $authorName]        // schema.org 的 author = 写内容的人
                    : ['@type' => 'Organization', 'name' => $defaults['site_name']],
                'publisher' => ['@type' => 'Organization', 'name' => $defaults['site_name']],     // publisher = 发布内容的机构（站点本身）
            ], fn ($value) => filled($value)));
        }

        // JSON-LD：自定义节点
        foreach ($this->jsonLds as $jsonLd) {
            $this->appendJsonLd($tags, $jsonLd);
        }

        return new HtmlString(implode("\n        ", $tags));
    }

    /**
     * 相对地址转绝对地址（og:image / canonical 必须绝对）。
     */
    protected function absoluteUrl(string $url): string
    {
        if (Str::startsWith($url, ['http://', 'https://', 'data:'])) {
            return $url;
        }

        return url($url);
    }

    /**
     * @param  array<int, string>  $tags
     */
    protected function appendJsonLd(array &$tags, array $schema): void
    {
        $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $json = str_replace('</', '<\/', $json);    // 防止字符串值中的 </script> 提前闭合标签（JSON 中 \/ 是 / 的合法转义）

        $tags[] = '<script type="application/ld+json">' . $json . '</script>';
    }
}
