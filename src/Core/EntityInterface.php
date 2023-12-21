<?php

namespace WebFramework\Core;

interface EntityInterface
{
    public static function getTableName(): string;

    /**
     *  @return array<string>
     */
    public static function getBaseFields(): array;

    /**
     *  @return array<string>
     */
    public static function getAdditionalIdFields();

    public function getId(): int;

    public function isNewObject(): bool;

    public function setObjectId(int $id): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * @return array<string, mixed>
     */
    public function toRawArray(): array;

    /**
     * @return array<string, mixed>
     */
    public function getOriginalValues(): array;

    /**
     * @param array<string, mixed> $values
     */
    public function setOriginalValues(array $values): void;
}
