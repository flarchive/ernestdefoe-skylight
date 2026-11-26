<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Flarum\Group\Group;

// Members get the Studio out of the box; guests keep core's light/dark switch.
return Migration::addPermissions([
    'ernestdefoe-skylight.useStudio' => Group::MEMBER_ID,
]);
