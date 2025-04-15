<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Job;

use WebFramework\Event\Event;
use WebFramework\Queue\Job;

class EventJob implements Job
{
    private string $jobId;

    public function __construct(
        public readonly string $listenerClass,
        public readonly Event $event,
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
        return 'event:'.get_class($this->event);
    }
}
