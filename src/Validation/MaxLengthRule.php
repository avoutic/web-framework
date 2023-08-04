<?php

namespace WebFramework\Validation;

class MaxLengthRule implements ValidationRule
{
    public function __construct(
        protected int $maxLength,
    ) {
    }

    public function isValid(string $value): bool
    {
        return (strlen($value) <= $this->maxLength);
    }

    public function getErrorMessage(): string
    {
        return 'validation.max_length';
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
            'max_length' => (string) $this->maxLength,
        ];
    }
}
