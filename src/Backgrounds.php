<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory as Filesystem;

/**
 * The admin's own backgrounds: up to four images in the flarum-assets disk,
 * one setting each (ernestdefoe-skylight.bg_img1 … bg_img4).
 */
final class Backgrounds
{
    public const SLOTS = ['img1', 'img2', 'img3', 'img4'];

    public function __construct(protected SettingsRepositoryInterface $settings, protected Filesystem $filesystem)
    {
    }

    public static function key(string $slot): string
    {
        return 'ernestdefoe-skylight.bg_'.$slot;
    }

    public function url(string $slot): ?string
    {
        if (! in_array($slot, self::SLOTS, true) || ! ($path = $this->settings->get(self::key($slot)))) {
            return null;
        }

        return $this->filesystem->disk('flarum-assets')->url($path);
    }

    /** @return array<string, string> slot => url, uploaded slots only */
    public function all(): array
    {
        $all = [];

        foreach (self::SLOTS as $slot) {
            if ($url = $this->url($slot)) {
                $all[$slot] = $url;
            }
        }

        return $all;
    }
}
