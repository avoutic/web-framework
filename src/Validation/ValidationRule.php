<?php

namespace WebFramework\Validation;

/**
 * Interface ValidationRule.
 *
 * This interface defines the contract for validation rules.
 */
interface ValidationRule
{
    /**
     * Check if the given value is valid according to this rule.
     *
     * @param string $value The value to validate
     *
     * @return bool True if the value is valid, false otherwise
     */
    public function isValid(string $value): bool;

    /**
     * Get the error message key for this rule.
     *
     * @return string The error message key
     */
    public function getErrorMessage(): string;

    /**
     * Get any extra error message for this rule.
     *
     * @return string The extra error message
     */
    public function getErrorExtraMessage(): string;

    /**
     * Get the error parameters for this rule.
     *
     * @param string $fieldName The name of the field being validated
     *
     * @return array<string, string> The error parameters
     */
    public function getErrorParams(string $fieldName): array;
}
