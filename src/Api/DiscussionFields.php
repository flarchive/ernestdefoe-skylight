<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight\Api;

use ErnestDefoe\Skylight\ActiveTheme;
use ErnestDefoe\Skylight\Look;
use Flarum\Api\Context;
use Flarum\Api\Resource\EloquentBuffer;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Flarum\Settings\SettingsRepositoryInterface;
use s9e\TextFormatter\Utils;

/**
 * skylightExcerpt: two lines of the opening post for the Cards view.
 *
 * Sent only for Cards, so List and Compact pay nothing: when the reader's own
 * look is Cards (which covers the list preloaded into the page), or when the
 * list asks with filter[skylight]=cards (a guest whose Cards choice lives in
 * their browser). The opening
 * posts of a page come through the relationship buffer in ONE query, scoped
 * by the posts resource (a post the reader can't see gives no excerpt), and
 * the text comes straight from the stored XML, with no formatter render. The
 * same approach as flarum/sticky's firstPostExcerpt.
 */
class DiscussionFields
{
    public const LENGTH = 160;

    public function __construct(protected Look $look, protected ActiveTheme $theme, protected SettingsRepositoryInterface $settings)
    {
    }

    public function __invoke(): array
    {
        return [
            Schema\Str::make('skylightExcerpt')
                ->nullable()
                ->visible(fn (Discussion $discussion, Context $context) => $this->wanted($context) || $this->replacesStickyExcerpt($discussion, $context))
                ->get(function (Discussion $discussion, Context $context) {
                    if (! $discussion->relationLoaded('firstPost')) {
                        EloquentBuffer::add($discussion, 'firstPost');
                    }

                    return function () use ($discussion, $context) {
                        if (! $discussion->relationLoaded('firstPost')) {
                            $resource = $context->api->getResource('discussions');

                            /** @var Schema\Relationship\ToOne|null $relationship */
                            $relationship = collect($context->fields($resource))->first(fn ($field) => $field->name === 'firstPost');

                            EloquentBuffer::load($discussion, 'firstPost', $relationship, $context);
                        }

                        $post = $discussion->getRelation('firstPost');

                        if (! $post instanceof CommentPost || empty($post->parsed_content)) {
                            return null;
                        }

                        return mb_substr(self::plain($post->parsed_content), 0, self::LENGTH);
                    };
                }),
        ];
    }

    /**
     * A post's text on one line. Images (their alt text) are dropped, and
     * paragraphs, line breaks and other blocks become spaces, so "a</p><p>b"
     * reads "a b", not "ab".
     */
    public static function plain(string $xml): string
    {
        $xml = preg_replace('#<(IMG|UPL-IMAGE-PREVIEW)\b[^>]*/>|<(IMG|UPL-IMAGE-PREVIEW)\b.*?</\2>#s', ' ', $xml) ?? $xml;
        $xml = preg_replace('#<br/>|</(p|LI|QUOTE|CODE|H[1-6]|SPOILER|DETAILS|TD|TH)>#i', '$0 ', $xml) ?? $xml;

        return trim(preg_replace('/\s+/u', ' ', Utils::removeFormatting($xml)) ?? '');
    }

    /**
     * flarum/sticky shows its own excerpt on sticky rows, built with
     * removeFormatting alone (paragraphs run together, image alt text shows).
     * While Skylight styles the page it sends its own text for those rows and
     * shows it in their place (less/forum/list.less). Same relation buffer as
     * sticky's, so no extra query.
     */
    public function replacesStickyExcerpt(Discussion $discussion, Context $context): bool
    {
        return $discussion->getAttribute('is_sticky')
            && $this->settings->get('flarum-sticky.enable_display_excerpt')
            && $this->theme->for($context->request);
    }

    public function wanted(Context $context): bool
    {
        return (($context->request->getQueryParams()['filter'] ?? [])['skylight'] ?? null) === 'cards'
            || ($this->theme->for($context->request) && $this->look->for($context->getActor())['look']['view'] === 'cards');
    }
}
