<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Repository;

use WebFramework\Entity\Entity;
use WebFramework\Entity\EntityCollection;
use WebFramework\Pagination\Paginator;

/**
 * @template T of Entity
 */
class RepositoryQuery
{
    /** @var array<string, mixed> */
    private array $filter = [];
    private ?int $limit = null;
    private ?int $offset = null;

    /** @var array<string> */
    private array $order = [];
    private bool $lockForUpdate = false;
    private bool $skipLocked = false;

    /**
     * @param RepositoryCore<T>    $repository
     * @param array<string>        $baseFields
     * @param array<string, mixed> $filter
     */
    public function __construct(
        private RepositoryCore $repository,
        private string $tableName,
        private array $baseFields,
        array $filter = []
    ) {
        $this->filter = $filter;
    }

    /**
     * @param array<string, mixed> $filter
     *
     * @return RepositoryQuery<T>
     */
    public function where(array $filter): self
    {
        $this->filter = array_merge($this->filter, $filter);

        return $this;
    }

    /**
     * @return RepositoryQuery<T>
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return RepositoryQuery<T>
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return RepositoryQuery<T>
     */
    public function orderBy(string $order): self
    {
        $this->order[] = $order;

        return $this;
    }

    /**
     * @return RepositoryQuery<T>
     */
    public function orderByAsc(string $field): self
    {
        return $this->orderBy("`{$field}` ASC");
    }

    /**
     * @return RepositoryQuery<T>
     */
    public function orderByDesc(string $field): self
    {
        return $this->orderBy("`{$field}` DESC");
    }

    /**
     * @return RepositoryQuery<T>
     */
    public function lockForUpdate(bool $skipLocked = false): self
    {
        $this->lockForUpdate = true;
        $this->skipLocked = $skipLocked;

        return $this;
    }

    /**
     * @return EntityCollection<T>
     */
    public function execute(): EntityCollection
    {
        $fieldsFmt = implode('`, `', $this->baseFields);

        $clauses = $this->buildClauses();
        $params = array_merge($clauses['whereParams'], $clauses['limitParams']);

        $lockFmt = '';
        if ($this->lockForUpdate)
        {
            $lockFmt = 'FOR UPDATE';
            if ($this->skipLocked)
            {
                $lockFmt .= ' SKIP LOCKED';
            }
        }

        $query = <<<SQL
        SELECT id, `{$fieldsFmt}`
        FROM {$this->tableName}
        {$clauses['where']}
        {$clauses['order']}
        {$clauses['limit']}
        {$lockFmt}
SQL;

        return $this->repository->getFromQuery($query, $params);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function pluck(string $column, ?string $key = null): array
    {
        $clauses = $this->buildClauses();
        $params = array_merge($clauses['whereParams'], $clauses['limitParams']);

        $select = "`{$column}`";
        if ($key !== null)
        {
            $select = "`{$key}`, `{$column}`";
        }

        $query = <<<SQL
        SELECT {$select}
        FROM {$this->tableName}
        {$clauses['where']}
        {$clauses['order']}
        {$clauses['limit']}
SQL;

        return $this->repository->getPluckFromQuery($query, $params, $column, $key);
    }

    /**
     * @return Paginator<T>
     */
    public function paginate(int $perPage = 15, int $page = 1): Paginator
    {
        $total = $this->count();
        $lastPage = (int) ceil($total / $perPage);

        // Ensure page is within valid bounds (1 to lastPage, or 1 if empty)
        $page = max(1, min($page, max(1, $lastPage)));

        $offset = ($page - 1) * $perPage;

        $items = $this->limit($perPage)
            ->offset($offset)
            ->execute()
        ;

        return new Paginator($items, $total, $perPage, $page);
    }

    /**
     * @return ?T
     */
    public function getOne(): ?Entity
    {
        $collection = $this->execute();

        if ($collection->count() > 1)
        {
            throw new \RuntimeException('Non-unique object request');
        }

        if ($collection->count() === 0)
        {
            return null;
        }

        return $collection->current();
    }

    public function count(): int
    {
        return (int) ($this->aggregate('COUNT', '*') ?? 0);
    }

    public function min(string $column): float|int|null
    {
        return $this->aggregate('MIN', $column);
    }

    public function max(string $column): float|int|null
    {
        return $this->aggregate('MAX', $column);
    }

    public function sum(string $column): float|int|null
    {
        return $this->aggregate('SUM', $column);
    }

    public function avg(string $column): float|int|null
    {
        return $this->aggregate('AVG', $column);
    }

    private function aggregate(string $function, string $column): float|int|null
    {
        $clauses = $this->buildClauses(false);

        $select = "`{$column}`";
        if ($column === '*')
        {
            $select = '*';
        }

        $query = <<<SQL
        SELECT {$function}({$select}) AS `aggregate`
        FROM {$this->tableName}
        {$clauses['where']}
SQL;

        return $this->repository->getAggregateFromQuery($query, $clauses['whereParams']);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Processes the query results in chunks.
     *
     * @param int      $count    The number of items per chunk
     * @param callable $callback The callback to execute for each chunk
     *
     * @return bool True if all chunks were processed, false if stopped early
     */
    public function chunk(int $count, callable $callback): bool
    {
        // Ensure stable ordering for pagination
        if (empty($this->order))
        {
            $this->orderBy('id ASC');
        }

        $originalLimit = $this->limit;
        $originalOffset = $this->offset ?? 0;
        $processed = 0;

        while (true)
        {
            $currentLimit = $count;

            // Respect global query limit if set
            if ($originalLimit !== null)
            {
                $remaining = $originalLimit - $processed;
                if ($remaining <= 0)
                {
                    break;
                }
                if ($remaining < $count)
                {
                    $currentLimit = $remaining;
                }
            }

            // Set limit/offset for this specific chunk
            $this->limit($currentLimit);
            $this->offset($originalOffset + $processed);

            $collection = $this->execute();

            if ($collection->count() === 0)
            {
                break;
            }

            // Stop if callback returns false
            if ($callback($collection) === false)
            {
                return false;
            }

            $resultsCount = $collection->count();
            $processed += $resultsCount;

            // Free memory
            unset($collection);

            // If we fetched fewer than requested, we've reached the end
            if ($resultsCount < $currentLimit)
            {
                break;
            }
        }

        return true;
    }

    /**
     * @return ?T
     */
    public function first(): ?Entity
    {
        return $this->limit(1)->getOne();
    }

    /**
     * @param array<null|bool|float|int|string> $values
     */
    public function update(array $values): int
    {
        if (count($values) === 0)
        {
            return 0;
        }

        if ($this->offset !== null)
        {
            throw new \RuntimeException('Offset not supported in UPDATE');
        }

        $setArray = $this->repository->getSetFmt($values);
        $setFmt = $setArray['query'];

        $clauses = $this->buildClauses(false);
        $params = array_merge($setArray['params'], $clauses['whereParams'], $clauses['limitParams']);

        $query = <<<SQL
        UPDATE {$this->tableName}
        SET {$setFmt}
        {$clauses['where']}
        {$clauses['order']}
        {$clauses['limit']}
SQL;

        return $this->repository->updateFromQuery($query, $params);
    }

    public function delete(): int
    {
        if ($this->offset !== null)
        {
            throw new \RuntimeException('Offset not supported in DELETE');
        }

        $clauses = $this->buildClauses(false);
        $params = array_merge($clauses['whereParams'], $clauses['limitParams']);

        $query = <<<SQL
        DELETE FROM {$this->tableName}
        {$clauses['where']}
        {$clauses['order']}
        {$clauses['limit']}
SQL;

        return $this->repository->deleteFromQuery($query, $params);
    }

    /**
     * @return array{
     *   where: string,
     *   order: string,
     *   limit: string,
     *   whereParams: array<mixed>,
     *   limitParams: array<mixed>
     * }
     */
    private function buildClauses(bool $supportOffset = true): array
    {
        $whereParams = [];
        $whereFmt = '';

        // Reuse RepositoryCore logic for filtering
        if (count($this->filter))
        {
            $filterArray = $this->repository->getFilterArray($this->filter);
            $whereFmt = "WHERE {$filterArray['query']}";
            $whereParams = $filterArray['params'];
        }

        $orderFmt = (count($this->order)) ? 'ORDER BY '.implode(', ', $this->order) : '';
        $limitParams = [];
        $limitFmt = '';

        if ($this->limit !== null)
        {
            if ($supportOffset && $this->offset !== null)
            {
                $limitFmt = 'LIMIT ?,?';
                $limitParams[] = $this->offset;
                $limitParams[] = $this->limit;
            }
            else
            {
                $limitFmt = 'LIMIT ?';
                $limitParams[] = $this->limit;
            }
        }

        return [
            'where' => $whereFmt,
            'order' => $orderFmt,
            'limit' => $limitFmt,
            'whereParams' => $whereParams,
            'limitParams' => $limitParams,
        ];
    }
}
