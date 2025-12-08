<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Database;

class Lock
{
    private bool $isLocked = false;

    public function __construct(
        private Database $database,
        private string $tag,
    ) {}

    public function __destruct()
    {
        if ($this->isLocked)
        {
            $this->release();
        }
    }

    public function lock(int $wait = 0): bool
    {
        $query = <<<'SQL'
        SELECT GET_LOCK(?, ?) AS result
SQL;

        $result = $this->database->query($query, [
            $this->tag,
            $wait,
        ], 'Failed to try to acquire lock');

        $this->isLocked = $result->fields['result'] == 1;

        return $result->fields['result'] == 1;
    }

    public function release(): bool
    {
        $query = <<<'SQL'
        SELECT RELEASE_LOCK(?) AS result
SQL;

        $result = $this->database->query($query, [
            $this->tag,
        ], 'Failed to try to release lock');

        if ($result->fields['result'] == 1)
        {
            $this->isLocked = false;

            return true;
        }

        return false;
    }

    public function block(\Closure $callback, int $wait = 0): mixed
    {
        if (!$this->lock($wait))
        {
            throw new \RuntimeException('Failed to acquire lock');
        }

        try
        {
            return $callback();
        }
        finally
        {
            $this->release();
        }
    }
}
