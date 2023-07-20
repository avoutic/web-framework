<?php

namespace WebFramework\Validation;

interface Validator
{
    public function getName(): string;

    /**
     * @return array<ValidationRule>
     */
    public function getRules(): array;

    public function isRequired(): bool;

    public function getTyped(string $value): mixed;

    public function getDefault(): mixed;
}
