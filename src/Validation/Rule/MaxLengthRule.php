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
 * Class MaxLengthRule.
 *
 * This class implements the ValidationRule interface to provide maximum length validation.
 */
class MaxLengthRule implements ValidationRule
{
    /**
     * MaxLengthRule constructor.
     *
     * @param int $maxLength The maximum allowed length
     */
    public function __construct(
        private int $maxLength,
        private string $errorMessage = 'validation.max_length',
        private string $errorMessageExtra = '',
    ) {}

    /**
     * Check if the given value is valid according to the maximum length rule.
     *
     * @param string $value The value to validate
     *
     * @return bool True if the value is valid, false otherwise
     */
    public function isValid(string $value): bool
    {
        return (strlen($value) <= $this->maxLength);
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
            'max_length' => (string) $this->maxLength,
        ];
    }
}
