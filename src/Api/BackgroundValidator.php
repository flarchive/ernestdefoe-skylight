<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight\Api;

use Flarum\Foundation\AbstractImageValidator;

/**
 * Core's image checks for a background picture: a real image (sniffed and
 * decoded, never trusted by its name), no decompression bombs, and photos up
 * to 8 MB, since a background is a full-screen picture rather than a logo.
 */
class BackgroundValidator extends AbstractImageValidator
{
    public function getMaxSize(): int
    {
        return 8192;
    }

    protected function getAllowedTypes(): array
    {
        return ['jpeg', 'jpg', 'png', 'webp'];
    }
}
