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

use WebFramework\Core\EntityCore;

/**
 * Represents a user right in the system.
 */
class Right extends EntityCore
{
    protected static string $tableName = 'rights';
    protected static array $baseFields = ['short_name', 'name'];

    private int $id;
    private string $shortName = '';
    private string $name = '';

    /**
     * Get the right ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the short name of the right.
     */
    public function getShortName(): string
    {
        return $this->shortName;
    }

    /**
     * Set the short name of the right.
     */
    public function setShortName(string $shortName): void
    {
        $this->shortName = $shortName;
    }

    /**
     * Get the full name of the right.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the full name of the right.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
