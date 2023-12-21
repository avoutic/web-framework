<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;

/**
 * @template T of EntityInterface
 */
abstract class RepositoryCore
{
    /** @var array<string> */
    protected array $baseFields;

    /** @var array<string> */
    protected array $additionalIdFields;

    /** @var class-string<T> */
    protected static string $entityClass;

    protected string $tableName;

    public function __construct(
        protected Container $container,
        protected Database $database,
    ) {
        $class = static::$entityClass;

        $this->baseFields = $class::$baseFields;
        $this->additionalIdFields = $class::$additionalIdFields;
        $this->tableName = $class::$tableName;
    }

    // Convert snake_case to camelCase
    protected function snakeToCamel(string $input): string
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
     * @param T $entity
     *
     * @return array<string>
     */
    public function getEntityFields(EntityInterface $entity, bool $includeId = true): array
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
     * @return ?array<string>
     */
    protected function getFieldsFromDb(int $id): ?array
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
     * @param T $entity
     *
     * @return array<string, mixed>
     */
    public function getChangedFields(EntityInterface $entity): array
    {
        $currentData = $this->getEntityFields($entity, false);
        $originalData = $entity->getOriginalValues();

        return array_diff_assoc($currentData, $originalData);
    }

    /**
     * @param T $entity
     */
    public function save(EntityInterface $entity): void
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

        $params = [$entity->getId()];

        $class = static::$entityClass;
        $this->database->query($query, $params, "Failed to update object ({$class})");
    }

    /**
     * @param T $entity
     */
    public function delete(EntityInterface $entity): void
    {
        $query = <<<SQL
        DELETE FROM {$this->tableName}
        WHERE id = ?
SQL;

        $params = [$entity->getId()];

        $this->database->query($query, $params, 'Failed to delete item');
    }

    /**
     * @param array<null|bool|float|int|string> $data
     *
     * @return T
     */
    public function create(array $data): EntityInterface
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
     * @param array<null|bool|float|int|string|array{string, bool|float|int|string}> $filter
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
    public function getObjectById(int $id): ?EntityInterface
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
     *
     * @return T
     */
    public function instantiateEntityFromData(array $data): EntityInterface
    {
        $entity = new static::$entityClass();
        $reflection = new \ReflectionClass($entity);

        $entity->setObjectId($data['id']);

        foreach ($this->baseFields as $name)
        {
            if (!isset($data[$name]))
            {
                continue;
            }

            $property = $reflection->getProperty($this->snakeToCamel($name));
            $property->setAccessible(true);
            $property->setValue($entity, $data[$name]);
        }

        $entity->setOriginalValues($data);

        return $entity;
    }

    // Helper retrieval functions
    //
    /**
     * @param array<null|bool|float|int|string|array{string, bool|float|int|string}> $filter
     *
     * @return ?T
     */
    public function getObject(array $filter = []): ?EntityInterface
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
     * @param array<null|bool|float|int|string|array{string, bool|float|int|string}> $filter
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
     * @param array<null|bool|float|int|string|array{string, bool|float|int|string}> $filter
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
                if (count($definition) !== 2)
                {
                    throw new \RuntimeException('Invalid filter definition');
                }

                $operator = $definition[0];
                $value = $definition[1];
            }
            else
            {
                $operator = '=';
                $value = $definition;
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
                $filterFmt .= "`{$key}` {$operator} ?";
                $params[] = $value;
            }
        }

        return [
            'query' => $filterFmt,
            'params' => $params,
        ];
    }
}
