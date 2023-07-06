<?php

namespace WebFramework\Core;

abstract class EntityCore implements EntityInterface
{
    /** @var array<string> */
    public static array $baseFields = [];

    /** @var array<string> */
    public static array $privateFields = [];

    /** @var array<string, mixed> */
    public array $originalValues = [];

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);

        $data = [];

        $property = $reflection->getProperty('id');
        $property->setAccessible(true);

        $data['id'] = $property->getValue($this);

        foreach (static::$baseFields as $name)
        {
            // Skip private fields
            //
            if (in_array($name, static::$privateFields))
            {
                continue;
            }

            $property = $reflection->getProperty($this->snakeToCamel($name));
            $property->setAccessible(true);

            $data[$name] = $property->getValue($this);
        }

        return $data;
    }

    // Convert snake_case to camelCase
    //
    private function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }

    /**
     * @return array<string, mixed>
     */
    public function getOriginalValues(): array
    {
        return $this->originalValues;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function setOriginalValues(array $values): void
    {
        $this->originalValues = $values;
    }
}
