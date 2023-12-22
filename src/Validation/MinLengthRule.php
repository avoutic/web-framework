<?php

namespace WebFramework\Validation;

class MinLengthRule implements ValidationRule
{
    public function __construct(
        private int $minLength,
    ) {
    }

    public function isValid(string $value): bool
    {
        return (strlen($value) >= $this->minLength);
    }

    public function getErrorMessage(): string
    {
        return 'validation.min_length';
    }

    public function getErrorExtraMessage(): string
    {
        return '';
    }

    /**
     * @return array<string, string>
     */
    public function getErrorParams(string $fieldName): array
    {
        return [
            'field_name' => $fieldName,
            'min_length' => (string) $this->minLength,
        ];
    }
}
