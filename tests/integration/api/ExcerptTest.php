<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight\Tests\integration\api;

use ErnestDefoe\Skylight\Tests\integration\SkylightTestCase;
use PHPUnit\Framework\Attributes\Test;

class ExcerptTest extends SkylightTestCase
{
    #[Test]
    public function the_list_sends_no_excerpts_unless_cards_asks(): void
    {
        $this->assertSame([1 => false, 2 => false], $this->excerpts([]));
    }

    #[Test]
    public function cards_get_the_opening_post_but_never_a_hidden_one(): void
    {
        $this->assertSame([1 => 'The harbor walk is on the 14th.', 2 => null], $this->excerpts(['filter' => ['skylight' => 'cards']]));
    }

    #[Test]
    public function a_member_on_cards_gets_excerpts_without_asking(): void
    {
        $this->assertSame('The harbor walk is on the 14th.', $this->excerpts([], 2)[1]);
        $this->assertFalse($this->excerpts([], 3)[1]);
    }
}
