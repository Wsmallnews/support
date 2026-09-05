<?php

namespace Wsmallnews\Support\Support;

/**
 * sn-* 设计令牌的运行时主题注入。
 *
 * 读取 config('sn-support.theme')，把非空项渲染为 <style> 块覆盖 tokens.css 的默认值，
 * 无需重新构建 CSS 即可换肤。
 *
 * 级联说明（关键）：响应式令牌在 tokens.css 的 @media (min-width: 64rem) 内有桌面档默认值，
 * 若只注入单个 :root 块，会因源顺序靠后而在 >= lg 时击败 media 块、杀死响应式。
 * 因此 `_lg` 后缀键被拆到独立的 @media 块中，与默认值的结构一一对应。
 */
class Theme
{
    /**
     * 允许覆盖的令牌键（不含 --sn- 前缀）。
     * `_lg` 后缀 = 该令牌的桌面档（>= lg / 64rem）。
     *
     * @var array<int, string>
     */
    public const TOKENS = [
        'radius_card',
        'radius_control',
        'space_page',
        'space_page_y',
        'space_page_x',
        'space_card',
        'space_row',
    ];

    /** 令牌值的合法格式：CSS 长度（rem/em/px/%）或 0 */
    private const VALUE_PATTERN = '/^-?\d*\.?\d+(rem|em|px|%)?$/';

    /**
     * 渲染主题覆盖 <style> 块；无有效配置时返回空字符串。
     */
    public static function styles(): string
    {
        $theme = config('sn-support.theme', []);

        if (! is_array($theme) || $theme === []) {
            return '';
        }

        $base = [];
        $desktop = [];

        foreach ($theme as $key => $value) {
            if (! is_string($key) || ! self::isValidKey($key)) {
                continue;
            }

            if (! is_string($value) || ! self::isValidValue($value)) {
                continue;
            }

            if (str_ends_with($key, '_lg')) {
                $desktop[substr($key, 0, -3)] = $value;
            } else {
                $base[$key] = $value;
            }
        }

        if ($base === [] && $desktop === []) {
            return '';
        }

        $css = '<style>';

        if ($base !== []) {
            $css .= ':root{' . self::declarations($base) . '}';
        }

        if ($desktop !== []) {
            $css .= '@media (min-width: 64rem){:root{' . self::declarations($desktop) . '}}';
        }

        return $css . '</style>';
    }

    private static function isValidKey(string $key): bool
    {
        $token = str_ends_with($key, '_lg') ? substr($key, 0, -3) : $key;

        return in_array($token, self::TOKENS, true);
    }

    private static function isValidValue(string $value): bool
    {
        return (bool) preg_match(self::VALUE_PATTERN, $value);
    }

    /**
     * @param  array<string, string>  $values
     */
    private static function declarations(array $values): string
    {
        return collect($values)
            ->map(fn (string $value, string $token) => '--sn-' . str_replace('_', '-', $token) . ":{$value};")
            ->implode('');
    }
}
