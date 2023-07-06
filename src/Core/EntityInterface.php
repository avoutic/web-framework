<?php

namespace WebFramework\Core;

interface EntityInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * @return array<string, mixed>
     */
    public function getOriginalValues(): array;

    /**
     * @param array<string, mixed> $values
     */
    public function setOriginalValues(array $values): void;
}
