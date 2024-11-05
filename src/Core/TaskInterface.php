<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

/**
 * Interface TaskInterface.
 *
 * Defines the contract for executable tasks in the WebFramework.
 */
interface TaskInterface
{
    /**
     * Execute the task.
     */
    public function execute(): void;
}
