<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight\Integration;

use Flarum\Foundation\AbstractServiceProvider;

/**
 * Registered only while ernestdefoe/bespoke is enabled (extend.php,
 * Extend\Conditional): Bespoke styles every page itself, so ActiveTheme keeps
 * Skylight off unless Wardrobe lets each visitor pick one of the two.
 */
class BespokeProvider extends AbstractServiceProvider
{
    public const BINDING = 'skylight.bespoke';

    public function register(): void
    {
        $this->container->instance(self::BINDING, true);
    }
}
