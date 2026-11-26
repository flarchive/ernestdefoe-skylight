<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight\Api;

use ErnestDefoe\Skylight\Backgrounds;
use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory;
use Psr\Http\Message\ServerRequestInterface;

/** DELETE /api/skylight/backgrounds/{slot}: removes the file and its setting. */
class DeleteBackgroundController extends AbstractDeleteController
{
    public function __construct(protected SettingsRepositoryInterface $settings, protected Factory $filesystem)
    {
    }

    protected function delete(ServerRequestInterface $request): void
    {
        RequestUtil::getActor($request)->assertAdmin();

        $key = Backgrounds::key(UploadBackgroundController::slot($request));
        $path = $this->settings->get($key);
        $this->settings->delete($key);

        $disk = $this->filesystem->disk('flarum-assets');
        if ($path && $disk->exists($path)) {
            $disk->delete($path);
        }
    }
}
