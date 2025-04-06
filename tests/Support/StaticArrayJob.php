<?php

namespace Tests\Support;

use WebFramework\Queue\Job;
class StaticArrayJob implements Job
{
    public function __construct(
        public string $name,
    ) {}

}