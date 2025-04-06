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
 * Base class for event listeners that should be queued.
 *
 * @implements EventListener<Event>
 */
abstract class QueuedEventListener implements EventListener
{
    protected string $queueName = 'default';

    public function getQueueName(): string
    {
        return $this->queueName;
    }
}
