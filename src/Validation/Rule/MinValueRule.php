<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Validation\Rule;

use WebFramework\Validation\ValidationRule;

/**
 * Class MinValueRule.
 *
 * This class implements the ValidationRule interface to provide minimum value validation.
 */
class MinValueRule implements ValidationRule
{
    /**
     * MinValueRule constructor.
     *
     * @param int $minValue The minimum allowed value
     */
    public function __construct(
        private int $minValue,
        private string $errorMessage = 'validation.min_value',
        private string $errorMessageExtra = '',
    ) {}

    /**
     * Check if the given value is valid according to the minimum value rule.
     *
     * @param string $value The value to validate
     *
     * @return bool True if the value is valid, false otherwise
     */
    public function isValid(string $value): bool
    {
        return is_numeric($value) && $value >= $this->minValue;
    }

    /**
     * Get the error message key for this rule.
     *
     * @return string The error message key
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * Get any extra error message for this rule.
     *
     * @return string The extra error message (empty in this case)
     */
    public function getErrorExtraMessage(): string
    {
        return $this->errorMessageExtra;
    }

    /**
     * Get the error parameters for this rule.
     *
     * @param string $fieldName The name of the field being validated
     *
     * @return array<string, string> The error parameters
     */
    public function getErrorParams(string $fieldName): array
    {
        return [
            'field_name' => $fieldName,
            'min_value' => (string) $this->minValue,
        ];
    }
}
