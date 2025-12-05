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

use Psr\Container\ContainerInterface as Container;
use WebFramework\Database\Database;
use WebFramework\Entity\Entity;
use WebFramework\Entity\EntityCollection;

/**
 * Abstract Class RepositoryCore.
 *
 * Provides a base implementation for repository classes in the WebFramework.
 *
 * @template T of Entity
 */
abstract class RepositoryCore
{
    /** @var array<string> Base fields of the entity */
    private array $baseFields;

    /** @var array<string> Additional ID fields of the entity */
    private array $additionalIdFields;

    /** @var class-string<T> The entity class associated with this repository */
    protected static string $entityClass;

    /** @var string The table name associated with this repository */
    private string $tableName;

    private const OPERATORS = [
        '=', '!=', '<', '>', '<=', '>=', '<>',
        'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN',
        'LIKE', 'NOT LIKE',
    ];

    /**
     * RepositoryCore constructor.
     *
     * @param Container $container The dependency injection container
     * @param Database  $database  The database interface
     */
    public function __construct(
        protected Container $container,
        protected Database $database,
    ) {
        $class = static::$entityClass;

        $this->baseFields = $class::getBaseFields();
        $this->additionalIdFields = $class::getAdditionalIdFields();
        $this->tableName = $class::getTableName();
    }

    /**
     * Begin a fluent query builder.
     *
     * @param array<string, mixed> $filter Initial filter criteria
     *
     * @return RepositoryQuery<T>
     */
    public function query(array $filter = []): RepositoryQuery
    {
        return new RepositoryQuery($this, $this->tableName, $this->baseFields, $filter);
    }

    /**
     * @param array<string, mixed> $filter
     *
     * @return ?T
     */
    public function getObject(array $filter = []): ?Entity
    {
        return $this->query()
            ->where($filter)
            ->getOne()
        ;
    }

    /**
     * @param array<string, mixed> $filter
     *
     * @return EntityCollection<T>
     */
    public function getObjects(int $offset = 0, int $results = 10, array $filter = [], string $order = ''): EntityCollection
    {
        $query = $this->query()
            ->where($filter)
        ;

        if (strlen($order))
        {
            $query = $query->orderBy($order);
        }

        if ($results != -1)
        {
            $query = $query->limit($results);
        }

        if ($offset !== 0)
        {
            $query = $query->offset($offset);
        }

        return $query->execute();
    }

    /**
     * @return ?T
     */
    public function getObjectById(int $id): ?Entity
    {
        return $this->query()
            ->where(['id' => $id])
            ->getOne()
        ;
    }

    public function exists(int $id): bool
    {
        return $this->query()
            ->where(['id' => $id])
            ->exists()
        ;
    }

    /**
     * @param array<string, mixed> $filter
     */
    public function countObjects(array $filter = []): int
    {
        return $this->query()
            ->where($filter)
            ->count()
        ;
    }

    /**
     * Create a new entity in the database.
     *
     * @param array<null|bool|float|int|string> $data The data to create the entity with
     *
     * @return T The created entity
     *
     * @throws \RuntimeException If there's an error during the creation
     */
    public function create(array $data): Entity
    {
        $query = '';
        $params = [];

        if (count($data) == 0)
        {
            $query = <<<SQL
        INSERT INTO {$this->tableName}
        VALUES()
SQL;
        }
        else
        {
            $setArray = $this->getSetFmt($data);
            $params = $setArray['params'];

            $query = <<<SQL
        INSERT INTO {$this->tableName}
        SET {$setArray['query']}
SQL;
        }

        $class = static::$entityClass;
        $result = $this->database->insertQuery($query, $params, "Failed to create object ({$class})");

        // Cannot use the given data to instantiate because it missed database defaults or
        // fields updated or filled by triggers. So instantiate from scratch.
        //
        $obj = $this->getObjectById($result);
        if ($obj === null)
        {
            throw new \RuntimeException("Failed to retrieve newly created object ({$class})");
        }

        return $obj;
    }

    /**
     * Save an entity to the database.
     *
     * @param T $entity The entity to save
     *
     * @throws \RuntimeException If there's an error during the save operation
     */
    public function save(Entity $entity): void
    {
        $reflection = new \ReflectionClass($entity);

        if ($entity->isNewObject())
        {
            $values = $this->getEntityFields($entity, false);
            $obj = $this->create($values);
            $entity->setObjectId($obj->getId());

            // Initialize additional database generated fields
            //
            foreach ($this->additionalIdFields as $field)
            {
                $property = $reflection->getProperty($field);
                $property->setAccessible(true);

                $fieldValue = $property->getValue($obj);
                $property->setValue($entity, $fieldValue);
            }

            return;
        }

        $data = $this->getChangedFields($entity);

        if (count($data) === 0)
        {
            // Nothing to update
            //
            return;
        }

        $setArray = $this->getSetFmt($data);
        $params = $setArray['params'];

        $query = <<<SQL
        UPDATE {$this->tableName}
        SET {$setArray['query']}
        WHERE id = ?
SQL;

        $params[] = $entity->getId();

        $class = static::$entityClass;
        $this->database->query($query, $params, "Failed to update object ({$class})");
    }

    /**
     * Delete an entity from the database.
     *
     * @param T $entity The entity to delete
     */
    public function delete(Entity $entity): void
    {
        $query = <<<SQL
        DELETE FROM {$this->tableName}
        WHERE id = ?
SQL;

        $params = [$entity->getId()];

        $this->database->query($query, $params, 'Failed to delete item');
    }

    /**
     * @param array<string, mixed> $data
     * @param null|string          $prefix Optional alias prefix used in the result set
     *
     * @return T
     */
    public function instantiateEntityFromData(array $data, ?string $prefix = null): Entity
    {
        $entity = new static::$entityClass();
        $reflection = new \ReflectionClass($entity);

        $prefixFmt = '';
        if ($prefix !== null && $prefix !== '')
        {
            $prefixFmt = rtrim($prefix, '.').'.';
        }

        $idKey = "{$prefixFmt}id";

        if (!isset($data[$idKey]))
        {
            $entityClass = static::$entityClass;
            $prefixInfo = ($prefixFmt === '') ? '' : " using prefix '{$prefixFmt}'";

            throw new \InvalidArgumentException(
                "Missing identifier field for {$entityClass}{$prefixInfo}"
            );
        }

        $entity->setObjectId((int) $data[$idKey]);

        $originalValues = [
            'id' => (int) $data[$idKey],
        ];

        foreach ($this->baseFields as $name)
        {
            $fieldKey = $prefixFmt.$name;

            if (!isset($data[$fieldKey]))
            {
                continue;
            }

            $property = $reflection->getProperty($this->snakeToCamel($name));
            $property->setAccessible(true);
            $property->setValue($entity, $data[$fieldKey]);

            $originalValues[$name] = $data[$fieldKey];
        }

        $entity->setOriginalValues($originalValues);

        return $entity;
    }

    /**
     * Get the entity fields as an array.
     *
     * @param T    $entity    The entity to get fields from
     * @param bool $includeId Whether to include the ID field
     *
     * @return array<string> The entity fields
     *
     * @throws \InvalidArgumentException If the provided entity is not of the correct type
     */
    public function getEntityFields(Entity $entity, bool $includeId = true): array
    {
        $providedClass = get_class($entity);
        $staticClass = static::$entityClass;

        if (!($entity instanceof $staticClass))
        {
            throw new \InvalidArgumentException("Provided {$providedClass} is not an {$staticClass}");
        }

        $reflection = new \ReflectionClass($entity);

        $info = [];

        if ($includeId)
        {
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);

            $info['id'] = $property->getValue($entity);
        }

        foreach ($this->baseFields as $name)
        {
            $property = $reflection->getProperty($this->snakeToCamel($name));
            $property->setAccessible(true);

            // Skip uninitialized values
            //
            if (!$property->isInitialized($entity))
            {
                continue;
            }

            $info[$name] = $property->getValue($entity);
        }

        return $info;
    }

    /**
     * Get the changed fields of an entity.
     *
     * @param T $entity The entity to check for changes
     *
     * @return array<string, mixed> The changed fields
     */
    public function getChangedFields(Entity $entity): array
    {
        $currentData = $this->getEntityFields($entity, false);
        $originalData = $entity->getOriginalValues();

        return array_diff_assoc($currentData, $originalData);
    }

    /**
     * Get the fields of an entity from the database.
     *
     * @param int $id The ID of the entity
     *
     * @return null|array<string> The entity fields or null if not found
     *
     * @throws \RuntimeException If multiple entities are found for the given ID
     */
    public function getFieldsFromDb(int $id): ?array
    {
        $fieldsFmt = implode('`, `', $this->baseFields);

        $query = <<<SQL
        SELECT `{$fieldsFmt}`
        FROM {$this->tableName}
        WHERE id = ?
SQL;

        $params = [$id];

        $result = $this->database->query($query, $params, "Failed to retrieve base fields for {$this->tableName}");

        if ($result->RecordCount() === 0)
        {
            return null;
        }

        if ($result->RecordCount() !== 1)
        {
            throw new \RuntimeException("Failed to select single item for {$id} in {$this->tableName}");
        }

        $row = $result->fields;
        $data = [
            'id' => $id,
        ];

        foreach ($this->baseFields as $name)
        {
            $data[$name] = $row[$name];
        }

        return $data;
    }

    /**
     * @return array<string>
     */
    public function getAliasedFields(string $alias, bool $includeId = true): array
    {
        $fields = $includeId ? ['id'] : [];
        $fields = array_merge($fields, $this->baseFields);

        return array_map(
            static fn (string $field): string => sprintf('%s.%s AS `%s.%s`', $alias, $field, $alias, $field),
            $fields
        );
    }

    /**
     * @param array<null|bool|float|int|string> $params
     *
     * @return EntityCollection<T>
     */
    public function getFromQuery(string $query, array $params): EntityCollection
    {
        $class = static::$entityClass;
        $result = $this->database->query($query, $params, "Failed to retrieve objects ({$class})");

        $info = [];
        foreach ($result as $k => $row)
        {
            $data = [
                'id' => $row['id'],
            ];

            foreach ($this->baseFields as $name)
            {
                $data[$name] = $row[$name];
            }

            $info[] = $this->instantiateEntityFromData($data);
        }

        return new EntityCollection($info);
    }

    /**
     * @param array<null|bool|float|int|string> $params
     */
    public function getAggregateFromQuery(string $query, array $params): float|int|null
    {
        $class = static::$entityClass;
        $result = $this->database->query($query, $params, "Failed to retrieve aggregate value ({$class})");

        if ($result->RecordCount() != 1)
        {
            throw new \RuntimeException("Failed to retrieve aggregate value ({$class})");
        }

        return $result->fields['aggregate'];
    }

    /**
     * Execute a batch update query.
     *
     * @param string                            $query  The SQL update query
     * @param array<null|bool|float|int|string> $params The parameters
     *
     * @return int The number of affected rows
     */
    public function updateFromQuery(string $query, array $params): int
    {
        $class = static::$entityClass;
        $this->database->query($query, $params, "Failed to batch update objects ({$class})");

        return $this->database->affectedRows();
    }

    /**
     * Execute a batch delete query.
     *
     * @param string                            $query  The SQL delete query
     * @param array<null|bool|float|int|string> $params The parameters
     *
     * @return int The number of affected rows
     */
    public function deleteFromQuery(string $query, array $params): int
    {
        $class = static::$entityClass;
        $this->database->query($query, $params, "Failed to batch delete objects ({$class})");

        return $this->database->affectedRows();
    }

    /**
     * @param array<null|bool|float|int|string> $params
     *
     * @return array<int|string, mixed>
     */
    public function getPluckFromQuery(string $query, array $params, string $column, ?string $key = null): array
    {
        $class = static::$entityClass;
        $result = $this->database->query($query, $params, "Failed to pluck fields ({$class})");

        $data = [];
        foreach ($result as $row)
        {
            if ($key !== null)
            {
                $data[$row[$key]] = $row[$column];
            }
            else
            {
                $data[] = $row[$column];
            }
        }

        return $data;
    }

    /**
     * @param array<null|bool|float|int|string> $values
     *
     * @return array{query: string, params: array<bool|float|int|string>}
     */
    public function getSetFmt(array $values): array
    {
        $setFmt = '';
        $params = [];
        $first = true;

        foreach ($values as $key => $value)
        {
            if (!$first)
            {
                $setFmt .= ', ';
            }
            else
            {
                $first = false;
            }

            // Mysqli does not accept empty for false, so force to zero
            //
            if ($value === false)
            {
                $value = 0;
            }

            if ($value === null)
            {
                $setFmt .= "`{$key}` = NULL";
            }
            else
            {
                $setFmt .= "`{$key}` = ?";
                $params[] = $value;
            }
        }

        return [
            'query' => $setFmt,
            'params' => $params,
        ];
    }

    /**
     * @param array<string, mixed> $filter
     *
     * @return array{query: string, params: array<bool|float|int|string>}
     */
    public function getFilterArray(array $filter): array
    {
        $filterFmt = '';
        $params = [];
        $first = true;

        foreach ($filter as $key => $definition)
        {
            if (!$first)
            {
                $filterFmt .= ' AND ';
            }
            else
            {
                $first = false;
            }

            if ($key === 'OR')
            {
                if (!is_array($definition))
                {
                    throw new \RuntimeException('Invalid OR filter definition');
                }

                $orQueries = [];
                foreach ($definition as $orKey => $orFilter)
                {
                    if (is_string($orKey))
                    {
                        $orFilter = [$orKey => $orFilter];
                    }

                    $result = $this->getFilterArray($orFilter);
                    if ($result['query'] !== '')
                    {
                        $orQuery = $result['query'];
                        if (str_contains($orQuery, ' AND ') || str_contains($orQuery, ' OR '))
                        {
                            $orQuery = "({$orQuery})";
                        }

                        $orQueries[] = $orQuery;
                        $params = array_merge($params, $result['params']);
                    }
                }

                if (count($orQueries) > 0)
                {
                    $filterFmt .= '('.implode(' OR ', $orQueries).')';
                }
                else
                {
                    $filterFmt .= '0';
                }

                continue;
            }

            if (is_array($definition))
            {
                if (isset($definition['OR']) && count($definition) === 1)
                {
                    if (!is_array($definition['OR']))
                    {
                        throw new \RuntimeException('Invalid OR filter definition');
                    }

                    $orFmts = [];
                    foreach ($definition['OR'] as $orKey => $orCond)
                    {
                        $targetKey = is_string($orKey) ? $orKey : $key;
                        $subResult = $this->getFilterArray([$targetKey => $orCond]);
                        if ($subResult['query'] !== '')
                        {
                            $subQuery = $subResult['query'];
                            if (str_contains($subQuery, ' AND ') || str_contains($subQuery, ' OR '))
                            {
                                $subQuery = "({$subQuery})";
                            }

                            $orFmts[] = $subQuery;
                            $params = array_merge($params, $subResult['params']);
                        }
                    }

                    if (count($orFmts) > 0)
                    {
                        $filterFmt .= '('.implode(' OR ', $orFmts).')';
                    }
                    else
                    {
                        $filterFmt .= '0';
                    }

                    continue;
                }

                // Support for multiple conditions on the same field
                if (!isset($definition[0])
                    || !is_string($definition[0])
                    || !in_array(strtoupper($definition[0]), self::OPERATORS, true)
                ) {
                    if (empty($definition))
                    {
                        throw new \RuntimeException('Invalid filter definition');
                    }

                    $subFmts = [];
                    foreach ($definition as $k => $subDef)
                    {
                        $subFilter = ($k === 'OR') ? ['OR' => $subDef] : $subDef;

                        $subResult = $this->getFilterArray([$key => $subFilter]);
                        if ($subResult['query'] !== '')
                        {
                            $subFmts[] = $subResult['query'];
                            $params = array_merge($params, $subResult['params']);
                        }
                    }
                    $filterFmt .= implode(' AND ', $subFmts);

                    continue;
                }

                if (count($definition) === 3)
                {
                    $operator = $definition[0];

                    if ($operator === 'BETWEEN' || $operator === 'NOT BETWEEN')
                    {
                        $v1 = $definition[1];
                        $v2 = $definition[2];

                        $p1 = '?';
                        if ($v1 instanceof Column)
                        {
                            $p1 = "`{$v1->name}`";
                        }
                        else
                        {
                            $params[] = $v1;
                        }

                        $p2 = '?';
                        if ($v2 instanceof Column)
                        {
                            $p2 = "`{$v2->name}`";
                        }
                        else
                        {
                            $params[] = $v2;
                        }

                        $filterFmt .= "`{$key}` {$operator} {$p1} AND {$p2}";

                        continue;
                    }

                    throw new \RuntimeException('Invalid filter definition');
                }

                if (count($definition) === 2)
                {
                    $operator = $definition[0];

                    if ($operator === 'IN' || $operator === 'NOT IN')
                    {
                        if (!is_array($definition[1]))
                        {
                            throw new \RuntimeException('Invalid filter definition');
                        }

                        $params = array_merge($params, $definition[1]);

                        $filterFmt .= "`{$key}` {$operator} (";
                        $filterFmt .= implode(', ', array_map(fn ($v) => '?', $definition[1]));
                        $filterFmt .= ')';

                        continue;
                    }

                    $value = $definition[1];

                    if ($value instanceof Column)
                    {
                        $filterFmt .= "`{$key}` {$operator} `{$value->name}`";

                        continue;
                    }

                    // Mysqli does not accept empty for false, so force to zero
                    //
                    if ($value === false)
                    {
                        $value = 0;
                    }

                    if ($value === null && $operator === '=')
                    {
                        $filterFmt .= "`{$key}` IS NULL";
                    }
                    elseif ($value === null && $operator === '!=')
                    {
                        $filterFmt .= "`{$key}` IS NOT NULL";
                    }
                    else
                    {
                        $filterFmt .= "`{$key}` {$operator} ?";
                        $params[] = $value;
                    }

                    continue;
                }

                throw new \RuntimeException('Invalid filter definition');
            }

            $value = $definition;

            if ($value instanceof Column)
            {
                $filterFmt .= "`{$key}` = `{$value->name}`";

                continue;
            }

            // Mysqli does not accept empty for false, so force to zero
            //
            if ($value === false)
            {
                $value = 0;
            }

            if ($value === null)
            {
                $filterFmt .= "`{$key}` IS NULL";
            }
            else
            {
                $filterFmt .= "`{$key}` = ?";
                $params[] = $value;
            }
        }

        return [
            'query' => $filterFmt,
            'params' => $params,
        ];
    }

    // Convert snake_case to camelCase
    private function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }
}
