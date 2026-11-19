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

    public function __construct(protected Look $look, protected ActiveTheme $theme)
    {
    }

    public function __invoke(): array
    {
        return [
            Schema\Str::make('skylightExcerpt')
                ->nullable()
                ->visible(fn (Discussion $discussion, Context $context) => $this->wanted($context))
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

                        $plain = trim(preg_replace('/\s+/', ' ', Utils::removeFormatting($post->parsed_content)) ?? '');

                        return mb_substr($plain, 0, self::LENGTH);
                    };
                }),
        ];
    }

    public function wanted(Context $context): bool
    {
        return (($context->request->getQueryParams()['filter'] ?? [])['skylight'] ?? null) === 'cards'
            || ($this->theme->for($context->request) && $this->look->for($context->getActor())['look']['view'] === 'cards');
    }
}
