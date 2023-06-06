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
        $data = [];

        foreach (static::$baseFields as $field)
        {
            $camelCase = $this->snakeToCamel($field);
            $data[$field] = $this->{$camelCase};
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
