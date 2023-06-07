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

    /** @var class-string<T> */
    protected static string $entityClass;

    protected bool $isCacheable = false;
    protected string $tableName;

    public function __construct(
        protected Container $container,
        protected Cache $cache,
        protected Database $database,
    ) {
        $class = static::$entityClass;

        $this->baseFields = $class::$baseFields;
        $this->tableName = $class::$tableName;

        if (property_exists($class, 'isCacheable'))
        {
            $this->isCacheable = $class::$isCacheable;
        }
    }

    // Convert snake_case to camelCase
    protected function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }

    /**
     * @param T $entity
     */
    protected function getId(EntityInterface $entity): int
    {
        $reflection = new \ReflectionClass($entity);

        $property = $reflection->getProperty('id');
        $property->setAccessible(true);

        return $property->getValue($entity);
    }

    public function exists(int $id): bool
    {
        if ($this->isCacheable)
        {
            if ($this->cache->exists($this->getCacheId($id)) === true)
            {
                return true;
            }
        }

        $result = $this->database->query('SELECT id FROM '.$this->tableName.
                                   ' WHERE id = ?', [$id]);

        if ($result === false)
        {
            return false;
        }

        if ($result->RecordCount() != 1)
        {
            return false;
        }

        return true;
    }

    public function getCacheId(int $id): string
    {
        return $this->tableName.'['.$id.']';
    }

    /**
     * @param T $entity
     */
    protected function updateInCache(EntityInterface $entity): void
    {
        if ($this->isCacheable)
        {
            $this->cache->set($this->getCacheId($this->getId($entity)), $entity);
        }
    }

    protected function deleteFromCache(int $id): void
    {
        if ($this->isCacheable)
        {
            $this->cache->invalidate($this->getCacheId($id));
        }
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

        if ($providedClass !== $staticClass)
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

            $info[$name] = $property->getValue($entity);
        }

        return $info;
    }

    /**
     * @return array<string>
     */
    protected function getFieldsFromDb(int $id): array
    {
        $fieldsFmt = implode('`, `', $this->baseFields);

        $query = <<<SQL
        SELECT `{$fieldsFmt}`
        FROM {$this->tableName}
        WHERE id = ?
SQL;

        $params = [$id];

        $result = $this->database->query($query, $params);
        if ($result === false)
        {
            throw new \RuntimeException("Failed to retrieve base fields for {$this->tableName}");
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
     */
    public function save(EntityInterface $entity): void
    {
        $data = $this->getEntityFields($entity, false);

        // TODO compare to original values
        //
        $setArray = $this->getSetFmt($data);
        $params = $setArray['params'];

        $query = <<<SQL
        UPDATE {$this->tableName}
        SET {$setArray['query']}
        WHERE id = ?
SQL;

        $params[] = $this->getId($entity);

        $result = $this->database->query($query, $params);
        $class = static::$entityClass;
        if ($result === false)
        {
            throw new \RuntimeException("Failed to update object ({$class})");
        }

        $this->updateInCache($entity);
    }

    /**
     * @param T $entity
     */
    public function delete(EntityInterface $entity): void
    {
        $this->deleteFromCache($this->getId($entity));

        $query = <<<SQL
        DELETE FROM {$this->tableName}
        WHERE id = ?
SQL;

        $params = [$this->getId($entity)];

        $result = $this->database->query($query, $params);
        if ($result === false)
        {
            throw new \RuntimeException('Failed to delete item');
        }
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

        $result = $this->database->insertQuery($query, $params);
        $class = static::$entityClass;
        if ($result === false)
        {
            throw new \RuntimeException("Failed to create object ({$class})");
        }

        $entity = new $class();
        $reflection = new \ReflectionClass($entity);

        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $result);

        foreach ($this->baseFields as $name)
        {
            $property = $reflection->getProperty($this->snakeToCamel($name));
            $property->setAccessible(true);
            $property->setValue($entity, $data[$name]);
        }

        return $entity;
    }

    /**
     * @param array<string, null|bool|float|int|string> $filter
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

        $result = $this->database->query($query, $params);
        $class = static::$entityClass;

        if ($result === false)
        {
            throw new \RuntimeException("Failed to retrieve object ({$class})");
        }
        if ($result->RecordCount() != 1)
        {
            throw new \RuntimeException("Failed to count objects ({$class})");
        }

        return $result->fields['cnt'];
    }

    // This is the base retrieval function that all object functions should use
    // Cache checking is done here
    //
    /**
     * @return ?T
     */
    public function getObjectById(int $id): ?EntityInterface
    {
        if ($this->isCacheable)
        {
            $obj = $this->cache->get($this->getCacheId($id));

            // Cache hit
            //
            if ($obj !== false)
            {
                return $obj;
            }
        }

        $data = $this->getFieldsFromDb($id);

        return $this->instantiateEntityFromData($data);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return T
     */
    protected function instantiateEntityFromData(array $data): EntityInterface
    {
        $entity = new static::$entityClass();
        $reflection = new \ReflectionClass($entity);

        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $data['id']);

        foreach ($this->baseFields as $name)
        {
            $property = $reflection->getProperty($this->snakeToCamel($name));
            $property->setAccessible(true);
            $property->setValue($entity, $data[$name]);
        }

        // Cache miss
        //
        if ($this->isCacheable)
        {
            $this->updateInCache($entity);
        }

        return $entity;
    }

    // Helper retrieval functions
    //
    /**
     * @param array<null|bool|float|int|string> $filter
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

        $result = $this->database->query($query, $params);
        $class = static::$entityClass;

        if ($result === false)
        {
            throw new \RuntimeException("Failed to retrieve object ({$class})");
        }
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
     * @param array<null|bool|float|int|string> $filter
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

        $result = $this->database->query($query, $params);
        $class = static::$entityClass;
        if ($result === false)
        {
            throw new \RuntimeException("Failed to retrieve objects ({$class})");
        }

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
     * @param array<null|bool|float|int|string> $filter
     *
     * @return array{query: string, params: array<bool|float|int|string>}
     */
    public function getFilterArray(array $filter): array
    {
        $filterFmt = '';
        $params = [];
        $first = true;

        foreach ($filter as $key => $value)
        {
            if (!$first)
            {
                $filterFmt .= ' AND ';
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
}
