<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

/**
 * Class DatabaseResultWrapper.
 *
 * Wraps the result of a database query, providing an iterator interface
 * to access the result rows.
 *
 * @implements \Iterator<int, array<string, mixed>>
 */
class DatabaseResultWrapper implements \Iterator
{
    /** @var \mysqli_result|true The underlying mysqli result object */
    private \mysqli_result|true $result;

    /** @var bool Whether the current position is valid */
    private bool $valid = false;

    /** @var int The current row number */
    private int $currentRow = 0;

    /** @var array<string, mixed> The current row data */
    public mixed $fields = [];

    /**
     * DatabaseResultWrapper constructor.
     *
     * @param \mysqli_result|true $result The mysqli result object to wrap
     */
    public function __construct(\mysqli_result|true $result)
    {
        $this->result = $result;

        if ($this->result !== true)
        {
            $this->rewind();
        }
    }

    /**
     * Get the number of rows in the result set.
     *
     * @return int|string The number of rows
     */
    public function RecordCount(): int|string
    {
        if ($this->result === true)
        {
            return 0;
        }

        return $this->result->num_rows;
    }

    /**
     * Rewind the Iterator to the first element.
     */
    public function rewind(): void
    {
        if ($this->result === true)
        {
            return;
        }

        $this->result->data_seek(0);
        $this->currentRow = 0;

        $this->fetchData();
    }

    /**
     * Return the current element.
     *
     * @return array<string, mixed> The current row data
     */
    public function current(): array
    {
        if (!$this->valid)
        {
            return [];
        }

        return $this->fields;
    }

    /**
     * Return the key of the current element.
     *
     * @return int The current row number
     */
    public function key(): int
    {
        return $this->currentRow;
    }

    /**
     * Move forward to next element.
     */
    public function next(): void
    {
        if ($this->result === true)
        {
            return;
        }

        $this->fetchData();
        $this->currentRow++;
    }

    /**
     * Checks if current position is valid.
     *
     * @return bool Whether the current position is valid
     */
    public function valid(): bool
    {
        return $this->valid;
    }

    /**
     * Fetch data from the underlying mysqli result set.
     */
    private function fetchData(): void
    {
        if ($this->result === true)
        {
            $this->valid = false;

            return;
        }

        $element = $this->result->fetch_assoc();

        if ($element === null || $element === false)
        {
            $this->fields = [];
            $this->valid = false;
        }
        else
        {
            $this->fields = $element;
            $this->valid = true;
        }
    }
}
