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

class StampTest extends SkylightTestCase
{
    #[Test]
    public function a_guest_gets_the_forum_look_on_the_page(): void
    {
        $page = $this->page();

        $this->assertStringContainsString('data-sky="on"', $page);
        $this->assertStringContainsString('data-sky-view="list"', $page);
        $this->assertStringContainsString('--sky-accent:#127c86;', $page);
    }

    #[Test]
    public function a_member_gets_their_own_look_before_the_page_loads(): void
    {
        $page = $this->page(2);

        $this->assertStringContainsString('data-sky-view="cards"', $page);
        $this->assertStringContainsString('data-sky-bg="dusk"', $page);
        $this->assertStringContainsString('--sky-accent:#6a4fc7;', $page);
    }

    #[Test]
    public function a_locked_section_stays_on_the_forum_default(): void
    {
        $this->setting('ernestdefoe-skylight.sections', '["view"]');

        $page = $this->page(2);

        $this->assertStringContainsString('data-sky-view="cards"', $page);
        $this->assertStringContainsString('data-sky-bg="daylight"', $page);
        $this->assertStringContainsString('--sky-accent:#127c86;', $page);
    }

    #[Test]
    public function stored_values_outside_the_allow_list_never_reach_the_page(): void
    {
        // Only the <html> element: the user's own preferences ride in the page's JSON payload, escaped.
        preg_match('/<html[^>]*>/', $this->page(3), $m);
        $html = $m[0];

        $this->assertStringNotContainsString('script', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('data-sky-font="system"', $html);
        $this->assertStringContainsString('--sky-accent:#127c86;', $html);
    }
}
