<?php

namespace WebFramework\Core;

/**
 * @template T of EntityInterface
 *
 * @implements \IteratorAggregate<int, T>
 */
class EntityCollection implements \IteratorAggregate, \Countable
{
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
    public function call(string $functionName): array
    {
        return array_map(function ($entity) use ($functionName) {
            return $entity->{$functionName}();
        }, $this->entities);
    }

    /**
     * @return \ArrayIterator<int, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->entities);
    }

    public function count(): int
    {
        return count($this->entities);
    }
}
