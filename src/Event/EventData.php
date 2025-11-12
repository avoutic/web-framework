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
 * @codeCoverageIgnore
 */
class EventData
{
    /** @var array<class-string<EventListener<Event>>> */
    public array $listeners = [];
}
