<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

/**
 * A "look": every choice the Studio offers, and how the forum's defaults, the
 * admin's locks and a member's own choices combine into what one visitor sees.
 *
 * Allow-list throughout: an unknown key or value is dropped, never passed on,
 * because a look ends up in attributes and a style on <html>.
 *
 * Mirrored in js/src/common/look.ts (keep the two in step).
 */
final class Look
{
    /** Studio sections and the look keys each one controls. Readability is always offered. */
    public const SECTIONS = [
        'accent' => ['accent'],
        'view' => ['view', 'tags'],
        'background' => ['bg', 'night'],
        'glass' => ['glass', 'blur'],
        'corners' => ['radius'],
        'density' => ['density'],
        'text' => ['scale'],
        'typeface' => ['font'],
        'motion' => ['motion'],
        'share' => [],
    ];

    /** Always offered, whatever the admin turns off. */
    public const READABILITY = ['rt', 'hc'];

    public const CHOICES = [
        'bg' => ['daylight', 'mist', 'dusk', 'plain', 'img1', 'img2', 'img3', 'img4'],
        'glass' => ['glass', 'solid'],
        'radius' => ['sharp', 'soft', 'round'],
        'density' => ['comfortable', 'compact'],
        'font' => ['system', 'inter', 'serif'],
        'motion' => ['full', 'reduced', 'off'],
        'view' => ['list', 'cards', 'compact'],
        'tags' => ['beside', 'below'],
        'night' => ['blue', 'black'],
    ];

    public const BUILT_IN = [
        'accent' => '#127c86',
        'bg' => 'daylight',
        'glass' => 'glass',
        'blur' => 14,
        'radius' => 'soft',
        'density' => 'comfortable',
        'scale' => 100,
        'font' => 'system',
        'motion' => 'full',
        'view' => 'list',
        'tags' => 'beside',
        'night' => 'blue',
        'rt' => false,
        'hc' => false,
    ];

    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    /**
     * Keep only known keys with valid values. Used for stored preferences, the
     * admin's defaults and anything read back from them.
     *
     * @return array<string, string|int|bool>
     */
    public static function sanitize(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return [];
        }

        $look = [];

        foreach ($value as $key => $item) {
            if (isset(self::CHOICES[$key])) {
                if (is_string($item) && in_array($item, self::CHOICES[$key], true)) {
                    $look[$key] = $item;
                }
            } elseif ($key === 'accent') {
                if (is_string($item) && ($hex = Color::hex($item)) !== null) {
                    $look[$key] = $hex;
                }
            } elseif ($key === 'blur') {
                if (is_numeric($item)) {
                    $look[$key] = max(4, min(20, (int) $item));
                }
            } elseif ($key === 'scale') {
                if (is_numeric($item)) {
                    $look[$key] = max(90, min(120, (int) $item));
                }
            } elseif (in_array($key, self::READABILITY, true)) {
                $look[$key] = (bool) $item;
            }
        }

        return $look;
    }

    /**
     * The forum's look: the built-in one with the admin's defaults on top.
     *
     * @return array<string, string|int|bool>
     */
    public function defaults(): array
    {
        return array_merge(self::BUILT_IN, self::sanitize($this->settings->get('ernestdefoe-skylight.defaults')));
    }

    /**
     * The Studio sections members may use.
     *
     * @return list<string>
     */
    public function sections(): array
    {
        $chosen = json_decode((string) $this->settings->get('ernestdefoe-skylight.sections'), true);

        if (! is_array($chosen)) {
            return array_keys(self::SECTIONS);
        }

        return array_values(array_intersect(array_keys(self::SECTIONS), $chosen));
    }

    public function canUseStudio(User $actor): bool
    {
        return $actor->hasPermission('ernestdefoe-skylight.useStudio');
    }

    /**
     * What this visitor sees: the forum's look, then the member's own choices
     * for the sections the admin left open. A member without the Studio
     * permission sees the forum's look, whatever they once saved.
     *
     * @return array{look: array<string, string|int|bool>, own: list<string>}
     */
    public function for(User $actor): array
    {
        $look = $this->defaults();
        $own = [];

        if ($actor->isGuest() || ! $this->canUseStudio($actor)) {
            return ['look' => $look, 'own' => $own];
        }

        $mine = self::sanitize($actor->getPreference('skylight'));
        $open = self::READABILITY;

        foreach ($this->sections() as $section) {
            $open = array_merge($open, self::SECTIONS[$section]);
        }

        foreach ($mine as $key => $value) {
            if (in_array($key, $open, true)) {
                $look[$key] = $value;
                $own[] = $key;
            }
        }

        return ['look' => $look, 'own' => $own];
    }
}
