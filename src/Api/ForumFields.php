<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight\Api;

use ErnestDefoe\Skylight\ActiveTheme;
use ErnestDefoe\Skylight\Backgrounds;
use ErnestDefoe\Skylight\Look;
use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Everything the Studio and the admin page need, as one attribute on the
 * forum document. Computed from settings already in memory: no query.
 */
class ForumFields
{
    public function __construct(
        protected Look $look,
        protected Backgrounds $backgrounds,
        protected SettingsRepositoryInterface $settings,
        protected ActiveTheme $theme,
    ) {
    }

    public function __invoke(): array
    {
        return [
            Schema\Arr::make('skylight')
                ->get(function ($forum, Context $context) {
                    $actor = $context->getActor();
                    $presets = json_decode((string) $this->settings->get('ernestdefoe-skylight.presets'), true);

                    $payload = [
                        'defaults' => $this->look->defaults(),
                        'sections' => $this->look->sections(),
                        'canUseStudio' => $this->look->canUseStudio($actor),
                        'tagAccent' => (bool) $this->settings->get('ernestdefoe-skylight.tag_accent'),
                        'presets' => is_array($presets) ? array_values(array_filter(array_map('strval', $presets), fn ($hex) => preg_match('/^#[0-9a-f]{6}$/', $hex))) : [],
                        'backgrounds' => $this->backgrounds->all(),
                    ];

                    if ($actor->isAdmin()) {
                        $payload['blockedByBespoke'] = $this->theme->blockedByBespoke();
                    }

                    return $payload;
                }),
        ];
    }
}
