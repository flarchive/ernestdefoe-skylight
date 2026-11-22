<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight\Integration;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\User\User;

/**
 * Registered only while ernestdefoe/wardrobe is enabled (extend.php,
 * Extend\Conditional): tells ActiveTheme which theme a visitor is on.
 *
 * 🚨 Wardrobe's registry is named as a string, so nothing loads it unless
 * this provider runs.
 */
class WardrobeProvider extends AbstractServiceProvider
{
    public const BINDING = 'skylight.wardrobe';

    public function register(): void
    {
        $this->container->bind(self::BINDING, fn ($container) => fn (User $actor, ?string $cookie): ?string => $container->make('Ernestdefoe\\Wardrobe\\ThemeRegistry')->resolve($actor, $cookie));
    }
}
