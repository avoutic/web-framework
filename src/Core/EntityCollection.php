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
 * Class EntityCollection.
 *
 * Represents a collection of entities that can be iterated over.
 *
 * @template T of Entity
 *
 * @implements \Iterator<int, T>
 */
class EntityCollection implements \Iterator, \Countable
{
    /** @var int The current index in the collection */
    private int $index = 0;

    /**
     * EntityCollection constructor.
     *
     * @param array<T> $entities An array of entities in the collection
     */
    public function __construct(
        private array $entities,
    ) {}

    /**
     * Get the entities in the collection.
     *
     * @return array<T> The entities in the collection
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * Convert the collection to an array of entity arrays.
     *
     * @return array<string, mixed> An array representation of the collection
     */
    public function toArray(): array
    {
        return array_map(static function ($entity) {
            return $entity->toArray();
        }, $this->entities);
    }

    /**
     * Apply a callback function to each entity in the collection.
     *
     * @param callable $callback The callback function to apply
     *
     * @return array<mixed> The results of applying the callback to each entity
     */
    public function call(callable $callback): array
    {
        return array_map($callback, $this->entities);
    }

    /**
     * Get the number of entities in the collection.
     *
     * @return int The number of entities
     */
    public function count(): int
    {
        return count($this->entities);
    }

    /**
     * Get the current entity in the iteration.
     *
     * @return T The current entity
     */
    public function current(): mixed
    {
        return $this->entities[$this->index];
    }

    /**
     * Get the current index in the iteration.
     *
     * @return int The current index
     */
    public function key(): int
    {
        return $this->index;
    }

    /**
     * Move to the next entity in the iteration.
     */
    public function next(): void
    {
        $this->index++;
    }

    /**
     * Reset the iteration to the beginning.
     */
    public function rewind(): void
    {
        $this->index = 0;
    }

    /**
     * Check if the current position is valid.
     *
     * @return bool True if the current position is valid, false otherwise
     */
    public function valid(): bool
    {
        return isset($this->entities[$this->index]);
    }
}
