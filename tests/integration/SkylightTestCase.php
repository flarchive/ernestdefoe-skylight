<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight\Tests\integration;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

/**
 * Users: 1 admin, 2 a member on Cards with an Iris accent, 3 a member with a
 * look that tries to smuggle markup in.
 * Discussions: 1 with a visible opening post, 2 with its opening post hidden.
 */
abstract class SkylightTestCase extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('ernestdefoe-skylight');

        $member = fn (int $id, array $look) => [
            'id' => $id, 'username' => "member$id", 'email' => "member$id@machine.local", 'is_email_confirmed' => 1,
            'preferences' => json_encode(['skylight' => $look]),
        ];

        $this->prepareDatabase([
            User::class => [
                $member(2, ['accent' => '#6a4fc7', 'view' => 'cards', 'bg' => 'dusk']),
                $member(3, ['accent' => 'red;}</style><script>', 'font' => '"><b>', 'view' => 'list']),
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 3],
                ['user_id' => 3, 'group_id' => 3],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Visible', 'created_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'Hidden opener', 'created_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>The harbor walk is on the 14th.</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Secret words</p></t>', 'hidden_at' => Carbon::now()],
            ],
        ]);
    }

    protected function page(?int $actor = null): string
    {
        return (string) $this->send($this->request('GET', '/', $actor ? ['authenticatedAs' => $actor] : []))->getBody();
    }

    /** @return array<int, string|null|false> discussion id => excerpt (false: attribute not sent) */
    protected function excerpts(array $query, ?int $actor = null): array
    {
        $response = $this->send($this->request('GET', '/api/discussions', $actor ? ['authenticatedAs' => $actor] : [])->withQueryParams($query));
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        $out = [];
        foreach (json_decode((string) $response->getBody(), true)['data'] as $d) {
            $out[(int) $d['id']] = array_key_exists('skylightExcerpt', $d['attributes']) ? $d['attributes']['skylightExcerpt'] : false;
        }
        ksort($out);

        return $out;
    }
}
