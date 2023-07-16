<?php

namespace WebFramework\Validation;

interface ValidationRule
{
    public function getTag(): string;

    public function isValid(string $value): bool;

    public function getErrorMessage(): string;

    public function getErrorExtraMessage(): string;

    /**
     * @return array<string, string>
     */
    public function getErrorParams(string $fieldName): array;
}
