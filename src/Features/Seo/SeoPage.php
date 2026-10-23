<?php

namespace Wsmallnews\Support\Features\Seo;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/** Page-level SEO value object: holds only the current page's data, one instance per render. */
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
     * Article-specific fields; the full node is assembled at render time.
     *
     *
     * @var ?array{datePublished?: ?string, dateModified?: ?string, author?: ?string}
     */
    protected ?array $articleExtra = null;

    /** Whether to emit the WebSite JSON-LD node (home page). */
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

    /** Append a raw JSON-LD node. */
    public function jsonLd(array $schema): static
    {
        $this->jsonLds[] = $schema;

        return $this;
    }

    /**
     * Declare Article structured data and switch og:type to article.
     * $extra accepts datePublished, dateModified and author; other fields come from page data.
     */
    public function article(array $extra = []): static
    {
        $this->articleExtra = $extra;
        $this->type('article');

        return $this;
    }

    /** Emit WebSite structured data (home page). */
    public function website(): static
    {
        $this->website = true;

        return $this;
    }

    /** Full title with site-name suffix; the site name alone when no title is set. */
    public function resolveTitle(string $siteName): string
    {
        return filled($this->title) ? "{$this->title} - {$siteName}" : $siteName;
    }

    /**
     * Render every SEO tag for <head>.
     *
     * @param  array{site_name: string, description: ?string, image: ?string, favicon: ?string, analytics_code: ?string}  $defaults  Module defaults resolved by the Seo registry
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

        // JSON-LD: WebSite node (home page)
        if ($this->website) {
            $this->appendJsonLd($tags, [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $defaults['site_name'],
                'url' => $this->absoluteUrl($url),
            ]);
        }

        // JSON-LD: Article node, reusing the already-resolved page data
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
                    ? ['@type' => 'Person', 'name' => $authorName]        // author = who wrote the content
                    : ['@type' => 'Organization', 'name' => $defaults['site_name']],
                'publisher' => ['@type' => 'Organization', 'name' => $defaults['site_name']],     // publisher is the site itself
            ], fn ($value) => filled($value)));
        }

        // JSON-LD: custom nodes
        foreach ($this->jsonLds as $jsonLd) {
            $this->appendJsonLd($tags, $jsonLd);
        }

        return new HtmlString(implode("\n        ", $tags));
    }

    /** Make a URL absolute; og:image and canonical must never be relative. */
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
        $json = str_replace('</', '<\/', $json);    // stop a literal </script> in a value from closing the tag early

        $tags[] = '<script type="application/ld+json">' . $json . '</script>';
    }
}
