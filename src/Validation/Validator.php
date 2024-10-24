<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Validation;

/**
 * Interface Validator.
 *
 * This interface defines the contract for validators.
 */
interface Validator
{
    /**
     * Get the name of the field being validated.
     *
     * @return string The field name
     */
    public function getName(): string;

    /**
     * Get the validation rules for this validator.
     *
     * @return array<ValidationRule> The array of validation rules
     */
    public function getRules(): array;

    /**
     * Check if this field is required.
     *
     * @return bool True if the field is required, false otherwise
     */
    public function isRequired(): bool;

    /**
     * Convert the validated string value to the appropriate type.
     *
     * @param string $value The value to convert
     *
     * @return mixed The converted value
     */
    public function getTyped(string $value): mixed;

    /**
     * Get the default value for this field.
     *
     * @return mixed The default value
     */
    public function getDefault(): mixed;
}
