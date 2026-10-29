<?php

namespace Wsmallnews\Support\Support;

/**
 * Runtime injection of the sn-* design tokens as a style block, so a theme can change
 * without rebuilding CSS. _lg keys go into their own media block, mirroring tokens.css.
 */
class Theme
{
    /**
     * Overridable token keys without the --sn- prefix; _lg is the desktop step.
     *
     * @var array<int, string>
     */
    public const TOKENS = [
        'radius_card',
        'radius_control',
        'space_page',
        'space_page_x',
        'space_card',
        'space_row',
    ];

    /** Accepted values: CSS lengths (rem, em, px, %) or 0 */
    private const VALUE_PATTERN = '/^-?\d*\.?\d+(rem|em|px|%)?$/';

    /** Render the override style block, or an empty string when nothing is configured. */
    public static function styles(): string
    {
        $theme = Utils::getThemeConfig();

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
