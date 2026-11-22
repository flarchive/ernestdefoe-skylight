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

class BackgroundTest extends SkylightTestCase
{
    #[Test]
    public function only_an_admin_may_remove_a_background(): void
    {
        // A guest is refused before the controller (CSRF, then authentication).
        $this->assertContains($this->send($this->request('DELETE', '/api/skylight/backgrounds/img1'))->getStatusCode(), [400, 401]);
        $this->assertSame(403, $this->send($this->request('DELETE', '/api/skylight/backgrounds/img1', ['authenticatedAs' => 2]))->getStatusCode());
        $this->assertSame(204, $this->send($this->request('DELETE', '/api/skylight/backgrounds/img1', ['authenticatedAs' => 1]))->getStatusCode());
    }

    #[Test]
    public function only_four_slots_exist(): void
    {
        $this->assertSame(404, $this->send($this->request('DELETE', '/api/skylight/backgrounds/img5', ['authenticatedAs' => 1]))->getStatusCode());
        $this->assertSame(404, $this->send($this->request('DELETE', '/api/skylight/backgrounds/..%2Fconfig', ['authenticatedAs' => 1]))->getStatusCode());
    }
}
