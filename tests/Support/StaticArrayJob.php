<?php

namespace Tests\Support;

use WebFramework\Queue\Job;
class StaticArrayJob implements Job
{
    private string $jobId;

    public function __construct(
        public string $name,
    ) {}

    public function getJobId(): string
    {
        return $this->jobId;
    }

    public function setJobId(string $jobId): void
    {
        $this->jobId = $jobId;
    }

    public function getJobName(): string
    {
        return 'static-array-job:'.$this->name;
    }
}