<?php

namespace WebFramework\Entity;

use WebFramework\Core\EntityCore;

class Right extends EntityCore
{
    protected static string $tableName = 'rights';
    protected static array $baseFields = ['short_name', 'name'];

    private int $id;
    private string $shortName = '';
    private string $name = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function getShortName(): string
    {
        return $this->shortName;
    }

    public function setShortName(string $shortName): void
    {
        $this->shortName = $shortName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
