<?php

namespace Tests\Support;

use WebFramework\Core\EntityCore;

class TestEntity extends EntityCore
{
    protected static string $tableName = 'helper_entity';

    protected static array $baseFields = ['id', 'name', 'description'];

    private int $id;
    private string $name = '';
    private string $description = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}