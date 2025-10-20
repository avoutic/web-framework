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

    // Convert snake_case to camelCase
    private function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }

    public function exists(int $id): bool
    {
        $result = $this->database->query('SELECT id FROM '.$this->tableName.
                                   ' WHERE id = ?', [$id]);

        if ($result->RecordCount() != 1)
        {
            return false;
        }

        return true;
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
     * @param array<null|array{string, null|array<bool|float|int|string>|bool|float|int|string}|bool|float|int|string> $filter
     */
    public function countObjects(array $filter = []): int
    {
        $params = [];
        $whereFmt = '';

        if (count($filter))
        {
            $filterArray = $this->getFilterArray($filter);
            $whereFmt = "WHERE {$filterArray['query']}";
            $params = $filterArray['params'];
        }

        $query = <<<SQL
        SELECT COUNT(id) AS cnt
        FROM {$this->tableName}
        {$whereFmt}
SQL;

        $class = static::$entityClass;
        $result = $this->database->query($query, $params, "Failed to retrieve object ({$class})");

        if ($result->RecordCount() != 1)
        {
            throw new \RuntimeException("Failed to count objects ({$class})");
        }

        return $result->fields['cnt'];
    }

    /**
     * @return ?T
     */
    public function getObjectById(int $id): ?Entity
    {
        $data = $this->getFieldsFromDb($id);
        if ($data === null)
        {
            return null;
        }

        return $this->instantiateEntityFromData($data);
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

    // Helper retrieval functions
    //
    /**
     * @param array<null|array{string, null|array<bool|float|int|string>|bool|float|int|string}|bool|float|int|string> $filter
     *
     * @return ?T
     */
    public function getObject(array $filter = []): ?Entity
    {
        $fieldsFmt = implode('`, `', $this->baseFields);
        $params = [];
        $whereFmt = '';

        if (count($filter))
        {
            $filterArray = $this->getFilterArray($filter);
            $whereFmt = "WHERE {$filterArray['query']}";
            $params = $filterArray['params'];
        }

        $query = <<<SQL
        SELECT id, `{$fieldsFmt}`
        FROM {$this->tableName}
        {$whereFmt}
SQL;

        $class = static::$entityClass;
        $result = $this->database->query($query, $params, "Failed to retrieve object ({$class})");

        if ($result->RecordCount() > 1)
        {
            throw new \RuntimeException("Non-unique object request ({$class})");
        }

        if ($result->RecordCount() === 0)
        {
            return null;
        }

        $row = $result->fields;
        $data = [
            'id' => $row['id'],
        ];

        foreach ($this->baseFields as $name)
        {
            $data[$name] = $row[$name];
        }

        return $this->instantiateEntityFromData($data);
    }

    /**
     * @param array<null|array{string, null|array<bool|float|int|string>|bool|float|int|string}|bool|float|int|string> $filter
     *
     * @return EntityCollection<T>
     */
    public function getObjects(int $offset = 0, int $results = 10, array $filter = [], string $order = ''): EntityCollection
    {
        $fieldsFmt = \implode('`, `', $this->baseFields);
        $params = [];
        $whereFmt = '';

        if (count($filter))
        {
            $filterArray = $this->getFilterArray($filter);
            $whereFmt = "WHERE {$filterArray['query']}";
            $params = $filterArray['params'];
        }

        $orderFmt = (strlen($order)) ? "ORDER BY {$order}" : '';
        $limitFmt = '';

        if ($results != -1)
        {
            $limitFmt = 'LIMIT ?,?';
            $params[] = (int) $offset;
            $params[] = (int) $results;
        }

        $query = <<<SQL
        SELECT id, `{$fieldsFmt}`
        FROM {$this->tableName}
        {$whereFmt}
        {$orderFmt}
        {$limitFmt}
SQL;

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
     * @param array<null|array{string, null|array<bool|float|int|string>|bool|float|int|string}|bool|float|int|string> $filter
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

            if (is_array($definition))
            {
                if (count($definition) === 3)
                {
                    $operator = $definition[0];

                    if ($operator === 'BETWEEN')
                    {
                        $filterFmt .= "`{$key}` BETWEEN ? AND ?";
                        $params[] = $definition[1];
                        $params[] = $definition[2];

                        continue;
                    }

                    if ($operator === 'NOT BETWEEN')
                    {
                        $filterFmt .= "`{$key}` NOT BETWEEN ? AND ?";
                        $params[] = $definition[1];
                        $params[] = $definition[2];

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
}
