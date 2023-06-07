<?php

namespace WebFramework\Entity;

use WebFramework\Core\EntityCore;

class Right extends EntityCore
{
    public static string $tableName = 'rights';
    public static array $baseFields = ['short_name', 'name'];

    private int $id;
    private string $shortName;
    private string $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function getShortName(): string
    {
        return $this->shortName;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
