<?php

namespace Tests\Support;

use WebFramework\Event\Event;

class TestEvent implements Event
{
    public function __construct(
        public string $name,
    ) {}
}