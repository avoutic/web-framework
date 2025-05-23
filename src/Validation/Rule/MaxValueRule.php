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
 * Class MaxValueRule.
 *
 * This class implements the ValidationRule interface to provide maximum value validation.
 */
class MaxValueRule implements ValidationRule
{
    /**
     * MaxValueRule constructor.
     *
     * @param int $maxValue The maximum allowed value
     */
    public function __construct(
        private int $maxValue,
    ) {}

    /**
     * Check if the given value is valid according to the maximum value rule.
     *
     * @param string $value The value to validate
     *
     * @return bool True if the value is valid, false otherwise
     */
    public function isValid(string $value): bool
    {
        return is_numeric($value) && $value <= $this->maxValue;
    }

    /**
     * Get the error message key for this rule.
     *
     * @return string The error message key
     */
    public function getErrorMessage(): string
    {
        return 'validation.max_value';
    }

    /**
     * Get any extra error message for this rule.
     *
     * @return string The extra error message (empty in this case)
     */
    public function getErrorExtraMessage(): string
    {
        return '';
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
            'max_value' => (string) $this->maxValue,
        ];
    }
}
