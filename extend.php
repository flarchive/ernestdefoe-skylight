<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ErnestDefoe\Skylight\Api;
use ErnestDefoe\Skylight\Content;
use ErnestDefoe\Skylight\Integration;
use ErnestDefoe\Skylight\Look;
use ErnestDefoe\Skylight\Search\ViewFilter;
use Flarum\Api\Resource\DiscussionResource;
use Flarum\Api\Resource\ForumResource;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Search\Database\DatabaseSearchDriver;
use Illuminate\Contracts\Filesystem\Factory as Filesystem;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        // The Studio is a chunk of its own, loaded on first open.
        ->jsDirectory(__DIR__.'/js/dist/forum')
        ->css(__DIR__.'/less/forum.less')
        // The visitor's look on <html>, before first paint.
        ->content(Content\Stamp::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    /*
     * Where the Inter font is, as an absolute URL baked into the stylesheet.
     * 🚨 Not a relative url(): Wardrobe serves each theme's CSS from its own
     * path, and a relative URL resolved against that 404s the font.
     */
    (new Extend\Theme())
        ->addCustomLessVariable('skylight-fonts', fn () => '"'.resolve(Filesystem::class)->disk('flarum-assets')->url('extensions/ernestdefoe-skylight/fonts/').'"'),

    // A member's Studio choices: one preferences key, cleaned on the way in
    // and out (Look::sanitize keeps known keys and values only). No table.
    (new Extend\User())
        ->registerPreference('skylight', fn ($value) => Look::sanitize($value), []),

    (new Extend\ApiResource(ForumResource::class))
        ->fields(Api\ForumFields::class),

    (new Extend\ApiResource(DiscussionResource::class))
        ->fields(Api\DiscussionFields::class),

    // filter[skylight]=cards: the Cards view's request for excerpts (a no-op
    // filter, since the API refuses unknown query parameters).
    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addFilter(DiscussionSearcher::class, ViewFilter::class),

    // Admin-only uploads of the four background pictures, on core's own image
    // upload and delete controllers (no JSON:API resource: a slot is a setting
    // holding a file path, not a model). The pattern routes only img1–img4.
    (new Extend\Routes('api'))
        ->post('/skylight/backgrounds/{slot:img[1-4]}', 'skylight.backgrounds.upload', Api\UploadBackgroundController::class)
        ->delete('/skylight/backgrounds/{slot:img[1-4]}', 'skylight.backgrounds.delete', Api\DeleteBackgroundController::class),

    // Optional integrations, declared here so the rest of the code never asks
    // whether another extension is installed (see ActiveTheme).
    (new Extend\Conditional())
        ->whenExtensionEnabled('ernestdefoe-wardrobe', fn () => [
            (new Extend\ServiceProvider())->register(Integration\WardrobeProvider::class),
        ])
        ->whenExtensionEnabled('ernestdefoe-bespoke', fn () => [
            (new Extend\ServiceProvider())->register(Integration\BespokeProvider::class),
        ])
        // Cards show fof/discussion-thumbnail's image (its customThumbnail
        // attribute) at the admin's size; the size reaches the forum only
        // while that extension is on.
        ->whenExtensionEnabled('fof-discussion-thumbnail', fn () => [
            (new Extend\Settings())
                ->serializeToForum('skylightCardThumbnail', 'ernestdefoe-skylight.card_thumbnail'),
        ]),

    // 🚨 Keys written out, never read from class constants while this file
    // loads (a composer update mid-request would fatal every page).
    (new Extend\Settings())
        // JSON look, merged over the built-in one ('' = built-in).
        ->default('ernestdefoe-skylight.defaults', '')
        // JSON list of Studio sections members may use ('' = all).
        ->default('ernestdefoe-skylight.sections', '')
        // JSON list of extra accent presets, added to the built-in six, or
        // in their place when presets_replace is on (off: installs that
        // already added extras keep the built-in six).
        ->default('ernestdefoe-skylight.presets', '[]')
        ->default('ernestdefoe-skylight.presets_replace', false)
        // One color for a discussion with several colored tags ('' = off:
        // the first tag's color, as before).
        ->default('ernestdefoe-skylight.multi_tag_color', '')
        // Cards: fof/discussion-thumbnail's image (off, small, medium, large).
        ->default('ernestdefoe-skylight.card_thumbnail', 'off')
        ->default('ernestdefoe-skylight.tag_accent', true)
        ->default('ernestdefoe-skylight.auto_solid', true),
];
