<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight\Search;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;

/**
 * filter[skylight]=cards: the Cards view asking for excerpts.
 *
 * It changes nothing about which discussions come back. It exists because
 * the JSON:API refuses query parameters it doesn't know, and a filter is the
 * supported way for a list request to carry a flag. Api\DiscussionFields
 * reads it to decide whether to send skylightExcerpt.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class ViewFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'skylight';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
    }
}
