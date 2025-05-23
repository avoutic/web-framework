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
 * Class EmailRule.
 *
 * This class implements the ValidationRule interface to provide email validation.
 */
class EmailRule implements ValidationRule
{
    /**
     * EmailRule constructor.
     */
    public function __construct(
        private string $errorMessage = 'validation.email',
        private string $errorMessageExtra = '',
    ) {}

    /**
     * Check if the given value is valid according to the email rule.
     *
     * @param string $value The value to validate
     *
     * @return bool True if the value is valid, false otherwise
     */
    public function isValid(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
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
        ];
    }
}
