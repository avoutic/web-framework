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

/**
 * @template T of Event
 */
interface EventListener
{
    /**
     * @param T $event The event to handle
     *
     * @return bool wether to continue with other listeners
     */
    public function handle(Event $event): bool;
}
