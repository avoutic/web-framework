<?php

namespace Tests\Support;

use WebFramework\Queue\Job;

class InvalidJobHandler
{
    public function handle(Job $job): void
    {
        // This class doesn't implement JobHandler interface
    }
}