<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight\Tests\unit;

use ErnestDefoe\Skylight\Api\DiscussionFields;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PlainTextTest extends TestCase
{
    #[Test]
    public function paragraphs_and_line_breaks_become_spaces(): void
    {
        $this->assertSame('One. Two. Three', DiscussionFields::plain('<r><p>One.</p><p>Two.<br/>Three</p></r>'));
    }

    #[Test]
    public function images_and_their_alt_text_are_left_out(): void
    {
        $xml = '<r><p><IMG alt="canoe" src="https://example.com/c.png"><s>![</s>canoe<e>](https://example.com/c.png)</e></IMG></p><p>Cedar strips.</p></r>';

        $this->assertSame('Cedar strips.', DiscussionFields::plain($xml));
    }

    #[Test]
    public function inline_formatting_keeps_words_whole(): void
    {
        $this->assertSame('bold and a link.', DiscussionFields::plain('<r><p><STRONG><s>**</s>bold<e>**</e></STRONG> and <URL url="https://x.test">a link</URL>.</p></r>'));
    }
}
