<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Wsmallnews\Support\Exceptions\InvalidSiteException;
use Wsmallnews\Support\Features\Currency;
use Wsmallnews\Support\Features\Money\MoneyManager;
use Wsmallnews\Support\Features\Site\SiteContext;
use Wsmallnews\Support\Support\Utils;

if (! function_exists('get_sn')) {
    /**
     * Build a unique serial number: timestamp + random + zero-padded id.
     *
     * @param  mixed  $id  Unique identifier
     * @param  string  $type  Prefix
     * @return string
     */
    function get_sn($id, $type = '')
    {
        $id = (string) $id;

        $rand = $id < 9999 ? mt_rand(100000, 99999999) : mt_rand(100, 99999);
        $sn = date('Yhis') . $rand;

        $id = str_pad($id, (24 - strlen($sn)), '0', STR_PAD_BOTH);

        return $type . $sn . $id;
    }
}

if (! function_exists('client_unique')) {
    /**
     * Client fingerprint derived from path, IP and user agent.
     *
     * @return bool
     */
    function client_unique()
    {
        $httpName = '';
        $url = request()->path();
        $ip = request()->ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $key = $httpName . ':' . $url . ':' . $ip . ':' . $user_agent;

        return md5($key);
    }
}

if (! function_exists('db_listen')) {
    /**
     * Echo every executed query with bindings and duration (debug only).
     *
     * @return bool
     */
    function db_listen()
    {
        DB::listen(function ($query) {
            $sql = $query->sql . ' ## | ';
            foreach ($query->bindings as $k => $v) {
                $sql .= $k . ' => ' . $v . ' | ';
            }
            $sql .= ' ## ' . $query->time . '<br>';

            echo $sql;
        });
    }
}

if (! function_exists('sn_currency')) {
    /**
     * @deprecated Use sn_money() (MoneyManager) instead.
     *
     * @return Currency
     */
    function sn_currency()
    {
        return app(Currency::class);
    }
}

if (! function_exists('sn_money')) {
    /**
     * The money manager: arithmetic, allocation and formatting.
     *
     * @return MoneyManager
     */
    function sn_money()
    {
        return app(MoneyManager::class);
    }
}

if (! function_exists('exception_log')) {
    /**
     * Log an exception with message, file and trace.
     *
     * @param  object  $exception
     * @param  string  $name
     * @param  string  $message
     * @return void
     */
    function exception_log($exception, $name = '', $message = '')
    {
        $logInfo = [
            "========== $name EXCEPTION LOG INFO BEGIN ==========",
            '[ Message ] ' . var_export('[' . $exception->getCode() . ']' . $exception->getMessage() . ' ' . $message, true),
            '[ File ] ' . var_export($exception->getFile() . ':' . $exception->getLine(), true),
            '[ Trace ] ' . var_export($exception->getTraceAsString(), true),
            "========== $name EXCEPTION LOG INFO ENDED ==========",
        ];

        $logInfo = implode(PHP_EOL, $logInfo) . PHP_EOL;
        Log::error($logInfo);
    }
}

if (! function_exists('through_cache')) {
    function through_cache($key, $callback, $store = null, $is_force = false, $ttl = 0)
    {
        $cache = Cache::store($store);

        if (! $is_force && $cache->has($key)) {
            // Cache hit
            return $cache->get($key);
        }

        $data = $callback();

        $cache->put($key, $data, $ttl);

        return $data;
    }
}

if (! function_exists('href_format')) {

    /** Build href/target/wire:navigate attributes, empty string for a blank URL. */
    function href_format(?string $url, bool $shouldOpenInNewTab = false, ?bool $shouldOpenInSpaMode = null): Htmlable
    {
        if (blank($url)) {
            return new HtmlString('');
        }

        $html = "href=\"{$url}\"";

        if ($shouldOpenInNewTab) {
            $html .= ' target="_blank"';
        } elseif ($shouldOpenInSpaMode ?? (FilamentView::hasSpaMode($url))) {
            $html .= ' wire:navigate';
        }

        return new HtmlString($html);
    }
}

if (! function_exists('files_url')) {
    function files_url($originalFiles, $disk = null)
    {
        $disk = $disk ?? Utils::getFilesystemDisk();

        $files = Arr::wrap($originalFiles);

        $diskUrl = config('filesystems.disks.' . $disk . '.url');
        $files = Arr::map($files, function ($file) use ($diskUrl) {
            return str($file)->startsWith(['http://', 'https://', 'data:image/']) ? $file : $diskUrl . '/' . $file;
        });

        return Arr::accessible($originalFiles) ? $files : Arr::first($files);
    }
}

if (! function_exists('filter_richeditor')) {
    function filter_richeditor($content)
    {
        $regex = '/<a[^>]*>(<img[^>]*>).*?<figcaption[^>]*>.*?<\/figcaption>.*?<\/a>/is';

        return preg_replace($regex, '$1', $content);
    }
}

if (! function_exists('current_tenant')) {
    /**
     * Tenant of the current execution context.
     *
     * Panel: the Filament tenant. Frontend: the tenant SiteContext resolved from
     * the site (and its mode). There is no third source — code that runs outside
     * both contexts has no tenant.
     */
    function current_tenant(): ?Model
    {
        if (Filament::getCurrentPanel()) {
            return Filament::getTenant();
        }

        $tenant = app(SiteContext::class)->tenant();

        return $tenant instanceof Model ? $tenant : null;
    }
}

if (! function_exists('get_tenancy_scope_name')) {
    /** Tenancy scope name of a panel. */
    function get_tenancy_scope_name(Panel | string | null $panel = null): ?string
    {
        $panel = $panel instanceof Panel ? $panel : (is_string($panel) ? Filament::getPanel($panel) : Filament::getCurrentPanel());

        return $panel?->getTenancyScopeName();
    }
}

if (! function_exists('is_in_panel')) {
    /** Whether the current request runs inside a Filament panel. */
    function is_in_panel(): bool
    {
        return ! is_null(Filament::getCurrentPanel());
    }
}

if (! function_exists('tree_to_flatten')) {
    /**
     * Flatten a tree collection into a single level.
     *
     * @param  Collection  $tree
     * @return Collection
     */
    function tree_to_flatten($tree)
    {
        return $tree->flatMap(function ($node) {
            // Recurse, but only when children were eager loaded
            $children = $node->relationLoaded('children') ? tree_to_flatten($node->children) : collect();

            // Current node followed by its flattened children
            return collect([$node])->merge($children);
        });
    }
}

if (! function_exists('sn_route')) {
    /**
     * Generate a route, injecting the current tenant when the route declares one.
     *
     * Tenant routes must use {tenant:slug}: the tenant is always resolved by
     * slug (see ResolveSiteContext), so a field-less {tenant} would generate a
     * URL the middleware can never bind.
     *
     * @param  string  $name
     * @param  mixed  $parameters
     * @param  bool  $absolute
     */
    function sn_route($name, $parameters = [], $absolute = true): string
    {
        $route = Route::getRoutes()->getByName($name);

        // Gate on the route signature: a route without {tenant} would get the
        // value appended as a query string. A tenant route without a tenant is
        // a configuration error, not a URL to generate.
        if ($route && in_array('tenant', $route->parameterNames(), true)) {
            if ($route->bindingFieldFor('tenant') !== 'slug') {
                throw InvalidSiteException::tenantRouteMustUseSlug($name);
            }

            $parameters = Arr::wrap($parameters);

            if (! isset($parameters['tenant'])) {        // never overwrite an explicit tenant
                $parameters['tenant'] = current_tenant()
                    ?? throw InvalidSiteException::tenantMissingForRoute($name);
            }
        }

        return route($name, $parameters, $absolute);
    }
}

if (! function_exists('remove_query_param_from_url')) {
    /** Strip query parameters from a URL. */
    function remove_query_param_from_url(string $url, string | array $keys): string
    {
        $parts = parse_url($url);

        parse_str($parts['query'] ?? '', $query);

        $keys = Arr::wrap($keys);
        foreach ($keys as $key) {
            if (isset($query[$key])) {
                unset($query[$key]);
            }
        }

        $newQuery = http_build_query($query);

        return $parts['scheme'] . '://' . $parts['host']
            . (($parts['port'] ?? '') ? ':' . $parts['port'] : '')
            . ($parts['path'] ?? '')
            . ($newQuery ? '?' . $newQuery : '');
    }
}

if (! function_exists('text_highlight')) {
    /** Highlight query terms in escaped text (case-insensitive) with <mark> tags. */
    function text_highlight(?string $text, ?string $query): string
    {
        $text = e((string) $text);

        $query = trim((string) $query);

        if ($query === '' || $text === '') {
            return $text;
        }

        $terms = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($terms as $term) {
            $text = preg_replace('/(' . preg_quote($term, '/') . ')/iu', '<mark class="sn-text-highlight">$1</mark>', $text);
        }

        return $text;
    }
}

if (! function_exists('generate_slug')) {
    /**
     * Slugify a title: transliterate by locale, cut at a word boundary, and fall back to
     * "{prefix}-{random}" when transliteration yields nothing (no intl extension).
     */
    function generate_slug(?string $value, int $limit = 80, ?string $fallbackPrefix = null): string
    {
        $slug = Str::slug(title: (string) $value, language: app()->getLocale());

        if (Str::length($slug) > $limit) {
            $truncated = substr($slug, 0, $limit);
            $boundary = strrpos($truncated, '-');
            $slug = $boundary > 0 ? substr($slug, 0, $boundary) : $truncated;
        }

        if (blank($slug)) {
            $slug = trim(($fallbackPrefix ?: 'slug') . '-' . Str::lower(Str::random(8)));
        }

        return $slug;
    }
}

if (! function_exists('sn_badge_color')) {
    /**
     * Map a Filament colour (name, hex or palette array) to a sn-badge class and style.
     * Unresolvable colours fall back to gray.
     *
     * @param  string  $variant  soft | outline | solid
     * @param  bool  $asVariables  Force the --sn-color-* variable path (e.g. tab selection, where a static class cannot be attached)
     * @return array{class: string, style: string}
     */
    function sn_badge_color(string | array | null $color, string $variant = 'soft', bool $asVariables = false): array
    {
        $named = ['primary', 'danger', 'success', 'info', 'warning', 'gray'];

        $namedClass = match ($variant) {
            'outline' => 'sn-badge-outline-{color}',
            'solid' => 'sn-badge-solid-{color}',
            default => 'sn-badge-{color}',
        };

        // Named colour: use the preset variant class
        if (is_string($color) && in_array($color, $named) && ! $asVariables) {
            return ['class' => str_replace('{color}', $color, $namedClass), 'style' => ''];
        }

        // Everything else resolves to a Filament palette (arrays as-is, names via the registry)
        $palette = $color;
        if (is_string($color) && filled($color)) {
            $palette = FilamentColor::getColor($color);
        }

        if (! is_array($palette) || blank($palette['500'] ?? null) || blank($palette['700'] ?? null)) {
            return ['class' => str_replace('{color}', 'gray', $namedClass), 'style' => ''];
        }

        // Solid: background 500 plus white text whenever contrast reaches 2:1; only the
        // palest hues (yellow/lime) fall back to 950. Use soft/outline for strict a11y.
        if ($variant === 'solid') {
            $bg = $palette['500'] ?? $palette['600'];
            $lightText = $palette['50'] ?? '#ffffff';
            $darkText = $palette['950'] ?? $palette['900'];

            $text = (Color::calculateContrastRatio($bg, $lightText) >= 2.0)
                ? $lightText
                : $darkText;

            return [
                'class' => 'sn-badge-dynamic-solid',
                'style' => "--sn-color-bg: {$bg}; --sn-color-text: {$text}; --sn-color-dark-bg: {$bg}; --sn-color-dark-text: {$text};",
            ];
        }

        // Soft/outline: inject 500/600/700 so the CSS mix matches the preset hues
        $class = $variant === 'outline' ? 'sn-badge-dynamic-outline' : 'sn-badge-dynamic';
        $style = sprintf(
            '--sn-color-bg: %s; --sn-color-text: %s; --sn-color-ring: %s; --sn-color-dark-bg: %s; --sn-color-dark-text: %s;',
            $palette['500'],
            $palette['700'],
            $palette['600'] ?? $palette['500'],
            $palette['500'],
            $palette['400'] ?? $palette['500'],
        );

        return ['class' => $class, 'style' => $style];
    }
}

if (! function_exists('sn_text_color')) {
    /**
     * Map a Filament colour to a text class and style; falls back to primary.
     *
     * @return array{class: string, style: string}
     */
    function sn_text_color(string | array $color = 'primary'): array
    {
        $named = [
            'primary' => 'text-primary-600 dark:text-primary-400',
            'danger' => 'text-danger-600 dark:text-danger-400',
            'success' => 'text-success-600 dark:text-success-400',
            'info' => 'text-info-600 dark:text-info-400',
            'warning' => 'text-warning-600 dark:text-warning-400',
            'gray' => 'text-gray-600 dark:text-gray-400',
        ];

        if (is_string($color) && isset($named[$color])) {
            return ['class' => $named[$color], 'style' => ''];
        }

        // Dynamic path: arrays as-is, hex/rgb through the palette generator, names via the registry
        $palette = $color;
        if (is_string($color)) {
            $palette = str_starts_with($color, '#') || str_starts_with($color, 'rgb')
                ? Color::generateV3Palette($color)
                : FilamentColor::getColor($color);
        }

        if (! is_array($palette) || blank($palette['600'] ?? null)) {
            return ['class' => $named['primary'], 'style' => ''];
        }

        return [
            'class' => 'sn-text-dynamic',
            'style' => sprintf(
                '--sn-color-text: %s; --sn-color-dark-text: %s;',
                $palette['600'],
                $palette['400'] ?? $palette['500'],
            ),
        ];
    }
}
