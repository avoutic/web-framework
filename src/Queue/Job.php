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

interface Job
{
    /**
     * Indicate if the job was properly handled and can be removed from the queue.
     */
    public function handle(): bool;
}
