<?php

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
