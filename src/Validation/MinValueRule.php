<?php

namespace WebFramework\Validation;

class MinValueRule implements ValidationRule
{
    public function __construct(
        private int $minValue,
    ) {
    }

    public function isValid(string $value): bool
    {
        return is_numeric($value) && $value >= $this->minValue;
    }

    public function getErrorMessage(): string
    {
        return 'validation.min_value';
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
            'min_value' => (string) $this->minValue,
        ];
    }
}
