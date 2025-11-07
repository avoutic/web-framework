<?php

namespace Tests\Support;

use WebFramework\Queue\Job;
use WebFramework\Queue\JobHandler;

class StaticArrayJobHandler implements JobHandler
{
    /** @var string[] */
    public static $data = [];

    public function handle(Job $job): void
    {
        // Add the data to the static array
        //
        self::$data[] = $job->name;
    }
}