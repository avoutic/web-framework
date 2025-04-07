<?php

namespace Tests\Support;

use WebFramework\Event\QueuedEventListener;
use WebFramework\Event\Event;

/**
 * @implements EventListener<TestEvent>
 */
class TestQueuedEventListener extends QueuedEventListener
{
    public function handle(Event $event): bool
    {
        return true;
    }
}