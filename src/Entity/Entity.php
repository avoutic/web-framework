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
 * Interface Entity.
 *
 * Defines the contract for entity objects in the WebFramework.
 */
interface Entity
{
    /**
     * Get the name of the database table associated with this entity.
     *
     * @return string The table name
     */
    public static function getTableName(): string;

    /**
     * Get the list of base fields for this entity.
     *
     * @return array<string> An array of field names
     */
    public static function getBaseFields(): array;

    /**
     * Get additional ID fields for this entity, if any.
     *
     * @return array<string> An array of additional ID field names
     */
    public static function getAdditionalIdFields();

    /**
     * Get the primary ID of the entity.
     *
     * @return int The entity's ID
     */
    public function getId(): int;

    /**
     * Check if this is a new (unsaved) object.
     *
     * @return bool True if the object is new, false otherwise
     */
    public function isNewObject(): bool;

    /**
     * Set the object's ID, marking it as no longer new.
     *
     * @param int $id The ID to set
     */
    public function setObjectId(int $id): void;

    /**
     * Convert the entity to an array representation.
     *
     * @return array<string, mixed> An array representation of the entity
     */
    public function toArray(): array;

    /**
     * Convert the entity to a raw array representation.
     *
     * @return array<string, mixed> A raw array representation of the entity
     */
    public function toRawArray(): array;

    /**
     * Get the original values of the entity.
     *
     * @return array<string, mixed> An array of original values
     */
    public function getOriginalValues(): array;

    /**
     * Set the original values of the entity.
     *
     * @param array<string, mixed> $values The original values to set
     */
    public function setOriginalValues(array $values): void;
}
