<?php

namespace Tests\Support;

use WebFramework\Queue\Job;

class InvalidJobHandler
{
    public function handle(Job $job): bool
    {
        return true;
    }
}