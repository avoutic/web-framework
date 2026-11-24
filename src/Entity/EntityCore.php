<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Entity;

/**
 * Abstract Class EntityCore.
 *
 * Provides a base implementation for entity objects in the WebFramework.
 */
abstract class EntityCore implements \Stringable, Entity
{
    /** @var string The name of the database table associated with this entity */
    protected static string $tableName = '';

    /** @var array<string> The base fields of the entity */
    protected static array $baseFields = [];

    /** @var array<string> Fields that should not be exposed in toArray() */
    protected static array $privateFields = [];

    /** @var array<string> Fields that can be mass-assigned */
    protected static array $fillableFields = [];

    /** @var array<string> Additional fields that form part of the entity's identifier */
    protected static array $additionalIdFields = [];

    /** @var array<class-string, array<string, array<string>>> Merged field lists per entity class */
    private static array $mergedFieldLists = [];

    /** @var array<string, mixed> The original values of the entity */
    private array $originalValues = [];

    /** @var bool Whether this is a new (unsaved) object */
    private bool $isNewObject = true;

    /**
     * Convert the entity to a string representation.
     *
     * @return string A string representation of the entity
     */
    public function __toString(): string
    {
        return print_r($this->toArray(), true);
    }

    /**
     * Provide debug information for the entity.
     *
     * @return array<mixed> Debug information
     */
    public function __debugInfo(): array
    {
        return $this->toArray();
    }

    public static function getTableName(): string
    {
        return static::$tableName;
    }

    public static function getBaseFields(): array
    {
        return self::getMergedFieldList('baseFields');
    }

    public static function getAdditionalIdFields(): array
    {
        return self::getMergedFieldList('additionalIdFields');
    }

    /**
     * Get the fields that should not be exposed in toArray().
     *
     * @return array<string> The private fields of this entity and its parent entities
     */
    public static function getPrivateFields(): array
    {
        return self::getMergedFieldList('privateFields');
    }

    /**
     * Get the fields that can be mass-assigned.
     *
     * @return array<string> The fillable fields of this entity and its parent entities
     */
    public static function getFillableFields(): array
    {
        return self::getMergedFieldList('fillableFields');
    }

    /**
     * Get a field list of this entity, merged with the same list of its parent entities.
     *
     * An entity that extends another entity only has to declare its own fields. The fields
     * of parent entities come first, and a field that is listed more than once is kept once.
     *
     * @param string $property The name of the static property holding the list
     *
     * @return array<string> The merged field list
     */
    private static function getMergedFieldList(string $property): array
    {
        $class = static::class;

        if (isset(self::$mergedFieldLists[$class][$property]))
        {
            return self::$mergedFieldLists[$class][$property];
        }

        $lists = [];

        for ($reflection = new \ReflectionClass($class); $reflection !== false; $reflection = $reflection->getParentClass())
        {
            if (!$reflection->hasProperty($property))
            {
                continue;
            }

            $reflectionProperty = $reflection->getProperty($property);

            // Only take the list from the class that declares it, a class that
            // does not redeclare the property shares it with its parent
            //
            if ($reflectionProperty->getDeclaringClass()->getName() !== $reflection->getName())
            {
                continue;
            }

            $reflectionProperty->setAccessible(true);

            array_unshift($lists, $reflectionProperty->getValue());
        }

        $merged = array_values(array_unique(array_merge(...$lists)));

        self::$mergedFieldLists[$class][$property] = $merged;

        return $merged;
    }

    /**
     * Get the cache ID for this entity instance.
     *
     * @return string The cache ID
     */
    public function getCacheId(): string
    {
        return static::getCacheIdFor($this->getId());
    }

    /**
     * Get the cache ID for a given entity ID.
     *
     * @param int $id The entity ID
     *
     * @return string The cache ID
     */
    public static function getCacheIdFor(int $id): string
    {
        return static::$tableName.'['.$id.']';
    }

    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);

        $data = [];

        if (!$this->isNewObject)
        {
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);

            $data['id'] = $property->getValue($this);
        }

        foreach (static::getBaseFields() as $name)
        {
            // Skip private fields
            //
            if (in_array($name, static::getPrivateFields()))
            {
                continue;
            }

            // Check if initialized
            //
            $property = $reflection->getProperty($this->snakeToCamel($name));
            $property->setAccessible(true);

            if (!$property->isInitialized($this))
            {
                continue;
            }

            $function = $this->snakeToGetter($name);

            // Retrieve via getter if present
            //
            if (method_exists($this, $function))
            {
                $data[$name] = $this->{$function}();
            }
            else
            {
                $data[$name] = $property->getValue($this);
            }
        }

        return $data;
    }

    public function toRawArray(): array
    {
        $reflection = new \ReflectionClass($this);

        $data = [];

        if (!$this->isNewObject)
        {
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);

            $data['id'] = $property->getValue($this);
        }

        foreach (static::getBaseFields() as $name)
        {
            // Skip private fields
            //
            if (in_array($name, static::getPrivateFields()))
            {
                continue;
            }

            $property = $reflection->getProperty($this->snakeToCamel($name));
            $property->setAccessible(true);

            $data[$name] = $property->getValue($this);
        }

        return $data;
    }

    /**
     * Populate the entity from an array of values.
     *
     * @param array<string, mixed> $values  The values to populate the entity with
     * @param array<string>        $exclude Fields to exclude from population
     */
    public function fromArray(array $values, array $exclude = []): void
    {
        $reflection = new \ReflectionClass($this);

        foreach (static::getFillableFields() as $name)
        {
            if (in_array($name, $exclude))
            {
                continue;
            }

            if (!array_key_exists($name, $values))
            {
                continue;
            }

            $function = $this->snakeToSetter($name);

            // Set via getter if present
            //
            if (method_exists($this, $function))
            {
                $this->{$function}($values[$name]);
            }
            else
            {
                $property = $reflection->getProperty($this->snakeToCamel($name));
                $property->setAccessible(true);

                $property->setValue($this, $values[$name]);
            }
        }
    }

    // Convert snake_case to camelCase
    //
    private function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }

    // Convert snake_case to getSnakeCase
    //
    private function snakeToGetter(string $input): string
    {
        return 'get'.str_replace('_', '', ucwords($input, '_'));
    }

    // Convert snake_case to setSnakeCase
    //
    private function snakeToSetter(string $input): string
    {
        return 'set'.str_replace('_', '', ucwords($input, '_'));
    }

    public function getOriginalValues(): array
    {
        return $this->originalValues;
    }

    public function setOriginalValues(array $values): void
    {
        $this->originalValues = $values;
    }

    public function isNewObject(): bool
    {
        return $this->isNewObject;
    }

    public function setObjectId(int $id): void
    {
        if ($this->isNewObject === false)
        {
            throw new \RuntimeException('Id already set');
        }

        $reflection = new \ReflectionClass($this);

        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($this, $id);

        $this->isNewObject = false;
    }

    public function resetToNew(): void
    {
        $reflection = new \ReflectionClass($this);
        $declaringClass = $reflection->getProperty('id')->getDeclaringClass()->getName();

        // Make the id uninitialized again, as on an object that was never stored. The id
        // can be private to the class that declares it, so unset it from that scope
        //
        \Closure::bind(function (string $property): void {
            // Not an assignment of null: the property has to become uninitialized
            unset($this->{$property});
        }, $this, $declaringClass)('id');

        $this->originalValues = [];
        $this->isNewObject = true;
    }
}
