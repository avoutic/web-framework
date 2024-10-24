<?php

namespace WebFramework\Validation;

class FilterRule implements ValidationRule
{
    public function __construct(
        private string $regex,
    ) {}

    public function isValid(string $value): bool
    {
        return (preg_match("/^\\s*{$this->regex}\\s*$/m", $value) == 1);
    }

    public function getErrorMessage(): string
    {
        return 'validation.filter';
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
        ];
    }
}
