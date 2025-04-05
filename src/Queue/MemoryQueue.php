<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Queue;

use Carbon\Carbon;

/**
 * The MemoryQueue is a simple queue implementation that uses an array to store the jobs.
 * It is not persistent and will lose all data when the server restarts. And it does not work
 * across multiple processes or servers.
 *
 * This queue is intended for development and testing purposes.
 */
class MemoryQueue implements Queue
{
    private Carbon $lastMaintenance;

    /** @var array<string> */
    private array $now = [];

    /** @var array<array{timestamp: Carbon, job: string}> */
    private array $delayed = [];

    public function __construct(private string $name)
    {
        $this->lastMaintenance = Carbon::now();
    }

    public function dispatch(Job $job, int $delay = 0): void
    {
        if ($delay > 0)
        {
            array_unshift($this->delayed, [
                'timestamp' => Carbon::now()->addSeconds($delay),
                'job' => serialize($job),
            ]);
        }
        else
        {
            array_unshift($this->now, serialize($job));
        }
    }

    public function count(): int
    {
        return count($this->now) + count($this->delayed);
    }

    public function popJob(): ?Job
    {
        $now = Carbon::now();

        if ($now > $this->lastMaintenance && count($this->delayed))
        {
            // Sort delayed jobs by timestamp from lowest to highest
            usort($this->delayed, function ($a, $b) {
                return (int) $b['timestamp']->diffInSeconds($a['timestamp']);
            });

            foreach ($this->delayed as $key => $delayed)
            {
                if ($delayed['timestamp']->lessThanOrEqualTo($now))
                {
                    $job = $delayed['job'];
                    unset($this->delayed[$key]);
                    array_unshift($this->now, $job);
                }
            }

            $this->lastMaintenance = $now;
        }

        $job = array_pop($this->now);

        return $job ? unserialize($job) : null;
    }

    public function clear(): void
    {
        $this->now = [];
        $this->delayed = [];
    }

    public function getName(): string
    {
        return $this->name;
    }
}
