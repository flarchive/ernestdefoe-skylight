<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight;

/**
 * Accent colors that stay readable.
 *
 * Any accent a member or admin picks is nudged until it reaches 4.5:1 as text
 * on Skylight's panes: darker for the light scheme, lighter for the dark one.
 * The text on an accent button is then whichever of white or ink reads better.
 *
 * Mirrored in js/src/common/color.ts (keep the two in step).
 */
final class Color
{
    /** The lightest pane in the light scheme and the darkest in the dark one. */
    public const LIGHT_PANE = '#f4f7fa';

    public const DARK_PANE = '#161c27';

    public const INK = '#0b1220';

    public static function hex(string $value): ?string
    {
        $value = strtolower(trim($value));

        if (preg_match('/^#?([0-9a-f]{3})$/', $value, $m)) {
            [$r, $g, $b] = str_split($m[1]);

            return '#'.$r.$r.$g.$g.$b.$b;
        }

        return preg_match('/^#?([0-9a-f]{6})$/', $value, $m) ? '#'.$m[1] : null;
    }

    /**
     * The accent as used in each scheme, and the text color on it.
     *
     * @return array{light: string, dark: string, inkLight: string, inkDark: string}
     */
    public static function accents(string $accent): array
    {
        $accent = self::hex($accent) ?? Look::BUILT_IN['accent'];
        $light = self::reach($accent, self::LIGHT_PANE, '#000000');
        $dark = self::reach($accent, self::DARK_PANE, '#ffffff');

        return [
            'light' => $light,
            'dark' => $dark,
            'inkLight' => self::ink($light),
            'inkDark' => self::ink($dark),
        ];
    }

    /** Mix toward $toward in 4% steps until the color reaches 4.5:1 against $on. */
    private static function reach(string $color, string $on, string $toward): string
    {
        for ($i = 0; $i <= 25; $i++) {
            $mixed = self::mix($color, $toward, $i * 0.04);

            if (self::contrast($mixed, $on) >= 4.5) {
                return $mixed;
            }
        }

        return $toward;
    }

    private static function ink(string $background): string
    {
        return self::contrast('#ffffff', $background) >= self::contrast(self::INK, $background) ? '#ffffff' : self::INK;
    }

    public static function contrast(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    private static function luminance(string $hex): float
    {
        $channels = array_map(function (int $c) {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    private static function mix(string $a, string $b, float $amount): string
    {
        $ca = self::rgb($a);
        $cb = self::rgb($b);

        return sprintf('#%02x%02x%02x', ...array_map(fn ($i) => (int) round($ca[$i] + ($cb[$i] - $ca[$i]) * $amount), [0, 1, 2]));
    }

    /** @return array{int, int, int} */
    private static function rgb(string $hex): array
    {
        return [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
    }
}
