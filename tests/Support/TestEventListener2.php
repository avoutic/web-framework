<?php

namespace Tests\Support;

use WebFramework\Event\EventListener;
use WebFramework\Event\Event;
/**
 * @implements EventListener<TestEvent>
 */
class TestEventListener2 implements EventListener
{
    public function handle(Event $event): bool
    {
        return true;
    }
}