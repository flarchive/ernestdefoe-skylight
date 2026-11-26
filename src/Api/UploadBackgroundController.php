<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight\Api;

use ErnestDefoe\Skylight\Backgrounds;
use Flarum\Api\Controller\UploadImageController;
use Illuminate\Support\Arr;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * POST /api/skylight/backgrounds/{slot}: core's own image upload (admin only,
 * checked by BackgroundValidator, the old file removed, the forum document
 * returned), with the picture scaled
 * down to 2400 px and re-encoded as WebP so a big photo stays a light page.
 */
class UploadBackgroundController extends UploadImageController
{
    protected string $filenamePrefix = 'skylight-bg';

    protected ?string $validator = BackgroundValidator::class;

    protected function makeImage(UploadedFileInterface $file): EncodedImageInterface
    {
        return $this->imageManager->read($file->getStream()->getMetadata('uri'))
            ->scaleDown(width: 2400, height: 2400)
            ->toWebp(quality: 78);
    }

    protected function filePathSettingKey(ServerRequestInterface $request, UploadedFileInterface $file): string
    {
        return Backgrounds::key(self::slot($request));
    }

    /** img1–img4: extend.php's route pattern lets nothing else through. */
    public static function slot(ServerRequestInterface $request): string
    {
        return (string) Arr::get((array) $request->getAttribute('routeParameters'), 'slot');
    }
}
