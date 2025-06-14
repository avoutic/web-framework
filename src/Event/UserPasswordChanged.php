<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Event;

use Slim\Http\ServerRequest as Request;
use WebFramework\Entity\User;

class UserPasswordChanged implements Event
{
    /**
     * @param Request $request The request that triggered the event
     * @param User    $user    The user that had their password changed
     */
    public function __construct(public Request $request, public User $user) {}
}
