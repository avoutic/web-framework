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
 * Class FilterRule.
 *
 * This class implements the ValidationRule interface to provide regex-based filtering.
 */
class FilterRule implements ValidationRule
{
    /**
     * FilterRule constructor.
     *
     * @param string $regex The regular expression to use for filtering
     */
    public function __construct(
        private string $regex,
        private string $errorMessage = 'validation.filter',
        private string $errorMessageExtra = '',
    ) {}

    /**
     * Check if the given value is valid according to the filter regex.
     *
     * @param string $value The value to validate
     *
     * @return bool True if the value is valid, false otherwise
     */
    public function isValid(string $value): bool
    {
        return (preg_match("/^\\s*{$this->regex}\\s*$/m", $value) == 1);
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
