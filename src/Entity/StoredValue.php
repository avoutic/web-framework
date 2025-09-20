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
 * Entity class for stored configuration values.
 */
class StoredValue extends EntityCore
{
    protected static string $tableName = 'stored_values';
    protected static array $baseFields = ['module', 'name', 'value'];

    private int $id;
    private string $module = '';
    private string $name = '';
    private string $value = '';

    /**
     * Get the stored value ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the module name.
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * Set the module name.
     */
    public function setModule(string $module): void
    {
        $this->module = $module;
    }

    /**
     * Get the value name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the value name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the stored value.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Set the stored value.
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
