<?php

namespace WebFramework\Core;

abstract class EntityCore implements EntityInterface
{
    /** @var array<string> */
    public static array $baseFields = [];

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
            $property = $reflection->getProperty($this->snakeToCamel($name));
            $property->setAccessible(true);

            $data[$name] = $property->getValue($this);
        }

        return $data;
    }

    //
    // Convert snake_case to camelCase
    private function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }
}
