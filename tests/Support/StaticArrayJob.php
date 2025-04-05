<?php

namespace Tests\Support;

use WebFramework\Queue\Job;
class StaticArrayJob implements Job
{
    /** @var string[] */
    public static $data = [];

    public function __construct(
        public string $name,
    ) {}

    public function handle(): bool
    {
        // Add the data to the static array
        //
        self::$data[] = $this->name;

        return true;
    }
}