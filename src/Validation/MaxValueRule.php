<?php

namespace WebFramework\Validation;

class MaxValueRule implements ValidationRule
{
    public function __construct(
        private int $maxValue,
    ) {}

    public function isValid(string $value): bool
    {
        return is_numeric($value) && $value <= $this->maxValue;
    }

    public function getErrorMessage(): string
    {
        return 'validation.max_value';
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
            'max_value' => (string) $this->maxValue,
        ];
    }
}
