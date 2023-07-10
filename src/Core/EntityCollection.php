<?php

namespace WebFramework\Core;

/**
 * @template T of EntityInterface
 *
 * @implements \Iterator<int, T>
 */
class EntityCollection implements \Iterator, \Countable
{
    private int $index = 0;

    /**
     * @param array<T> $entities;
     */
    public function __construct(
        private array $entities,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_map(function ($entity) {
            return $entity->toArray();
        }, $this->entities);
    }

    /**
     * @return array<mixed>
     */
    public function call(callable $callback): array
    {
        return array_map($callback, $this->entities);
    }

    public function count(): int
    {
        return count($this->entities);
    }

    /**
     * @return T
     */
    public function current(): mixed
    {
        return $this->entities[$this->index];
    }

    public function key(): int
    {
        return $this->index;
    }

    public function next(): void
    {
        $this->index++;
    }

    public function rewind(): void
    {
        $this->index = 0;
    }

    public function valid(): bool
    {
        return isset($this->entities[$this->index]);
    }
}
