<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight;

use ErnestDefoe\Skylight\Integration\BespokeProvider;
use ErnestDefoe\Skylight\Integration\WardrobeProvider;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Is this visitor looking at Skylight?
 *
 * Every Skylight rule hangs off <html data-sky>, so answering "no" here turns
 * the whole theme off for that page, and the stylesheet costs nothing.
 *
 * - ernestdefoe/wardrobe enabled: yes only for visitors on Skylight.
 * - Bespoke enabled without Wardrobe: no. Two themes styling the same page
 *   fight; the admin page says to offer Skylight through Wardrobe instead.
 * - Otherwise: yes.
 *
 * Both integrations are bound by Extend\Conditional in extend.php
 * (Integration\*Provider), so this class never names either extension.
 */
final class ActiveTheme
{
    public const ID = 'ernestdefoe-skylight';

    /** @var \WeakMap<ServerRequestInterface, bool>|null One answer per request: entries go with their request. */
    private static ?\WeakMap $answers = null;

    public function __construct(protected Container $container, protected LoggerInterface $logger)
    {
    }

    public function blockedByBespoke(): bool
    {
        return $this->container->bound(BespokeProvider::BINDING) && ! $this->container->bound(WardrobeProvider::BINDING);
    }

    public function for(?ServerRequestInterface $request): bool
    {
        if ($this->blockedByBespoke()) {
            return false;
        }

        if (! $request || ! $this->container->bound(WardrobeProvider::BINDING)) {
            return true;
        }

        self::$answers ??= new \WeakMap();

        return self::$answers[$request] ??= $this->ask($request);
    }

    private function ask(ServerRequestInterface $request): bool
    {
        try {
            $cookie = $request->getCookieParams()['wardrobe_theme'] ?? null;
            $chosen = ($this->container->make(WardrobeProvider::BINDING))(RequestUtil::getActor($request), is_string($cookie) ? $cookie : null);
        } catch (\Throwable $e) {
            // Wardrobe could not say: Skylight serves as the only theme, which is what the visitor saw before Wardrobe.
            $this->logger->warning('Skylight: Wardrobe could not resolve the visitor\'s theme: '.$e->getMessage(), ['exception' => $e]);

            return true;
        }

        // null: Wardrobe serves the shared stylesheet with every enabled theme
        // in it. Skylight then styles the page only when Bespoke is not there too.
        return $chosen === self::ID || ($chosen === null && ! $this->container->bound(BespokeProvider::BINDING));
    }
}
