<?php

namespace WebFramework\Core;

/**
 * @implements \Iterator<int, array<string, mixed>>
 */
class DatabaseResultWrapper implements \Iterator
{
    private \mysqli_result|true $result;
    private bool $valid = false;
    private int $current_row = 0;

    public mixed $fields = [];

    public function __construct(\mysqli_result|true $result)
    {
        $this->result = $result;

        if ($this->result !== true && $this->result->num_rows == 1)
        {
            $this->rewind();
        }
    }

    public function RecordCount(): int|string
    {
        if ($this->result === true)
        {
            return 0;
        }

        return $this->result->num_rows;
    }

    public function rewind(): void
    {
        if ($this->result === true)
        {
            return;
        }

        $this->result->data_seek(0);
        $this->current_row = 0;
        $this->fields = $this->result->fetch_assoc();
        $this->valid = ($this->fields !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function current(): array
    {
        if ($this->fields === true)
        {
            return [];
        }

        return $this->fields;
    }

    public function key(): int
    {
        return $this->current_row;
    }

    public function next(): void
    {
        if ($this->result === true)
        {
            return;
        }

        $this->fields = $this->result->fetch_assoc();
        $this->valid = ($this->fields !== null);
        $this->current_row++;
    }

    public function valid(): bool
    {
        return $this->valid;
    }
}
