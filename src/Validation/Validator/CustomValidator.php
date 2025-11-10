<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Validation\Validator;

use WebFramework\Validation\Rule\EmailRule;
use WebFramework\Validation\Rule\FilterRule;
use WebFramework\Validation\Rule\MaxLengthRule;
use WebFramework\Validation\Rule\MinLengthRule;
use WebFramework\Validation\Rule\UrlRule;
use WebFramework\Validation\ValidationRule;
use WebFramework\Validation\Validator;

/**
 * Class CustomValidator.
 *
 * This class implements the Validator interface and provides basic validation functionality.
 */
class CustomValidator implements Validator
{
    private bool $required = false;
    private mixed $default = '';
    private string $requiredErrorMessage = 'validation.required';

    /** @var array<ValidationRule> */
    private array $rules = [];

    /**
     * CustomValidator constructor.
     *
     * @param string $name The name to use for error messages
     */
    public function __construct(
        private string $name,
    ) {}

    /**
     * Set the filter for this validator.
     *
     * @param string $filter The filter regex
     */
    public function filter(string $filter, string $errorMessage = 'validation.filter'): self
    {
        $this->addRule(new FilterRule($filter, $errorMessage));

        return $this;
    }

    /**
     * Mark this field as optional.
     */
    public function optional(): self
    {
        $this->required = false;

        return $this;
    }

    /**
     * Mark this field as required.
     *
     * @param string $errorMessage The error message key (default: 'validation.required')
     */
    public function required(string $errorMessage = 'validation.required'): self
    {
        $this->required = true;
        $this->requiredErrorMessage = $errorMessage;

        return $this;
    }

    /**
     * Set the minimum length for this field.
     *
     * @param int $length The minimum length
     */
    public function minLength(int $length, string $errorMessage = 'validation.min_length'): self
    {
        $this->addRule(new MinLengthRule($length, $errorMessage));

        return $this;
    }

    /**
     * Set the maximum length for this field.
     *
     * @param int $length The maximum length
     */
    public function maxLength(int $length, string $errorMessage = 'validation.max_length'): self
    {
        $this->addRule(new MaxLengthRule($length, $errorMessage));

        return $this;
    }

    /**
     * Set email validation for this field.
     */
    public function email(string $errorMessage = 'validation.email'): self
    {
        $this->addRule(new EmailRule($errorMessage));

        return $this;
    }

    /**
     * Set url validation for this field.
     */
    public function url(string $errorMessage = 'validation.url'): self
    {
        $this->addRule(new UrlRule($errorMessage));

        return $this;
    }

    /**
     * Set the default value for this field.
     *
     * @param mixed $default The default value
     */
    public function default(mixed $default): self
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Add a validation rule to this validator.
     */
    public function addRule(ValidationRule $rule): self
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * Get the name of this field.
     *
     * @return string The field name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the validation rules for this validator.
     *
     * @return array<ValidationRule> The array of validation rules
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Check if this field is required.
     *
     * @return bool True if the field is required, false otherwise
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Get the default value for this field.
     *
     * @return mixed The default value
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * Convert the validated string value to the appropriate type.
     *
     * @param string $value The value to convert
     *
     * @return mixed The converted value
     */
    public function getTyped(string $value): mixed
    {
        return $value;
    }

    /**
     * Get the error message key for required validation.
     *
     * @return string The error message key
     */
    public function getRequiredErrorMessage(): string
    {
        return $this->requiredErrorMessage;
    }

    /**
     * Get the error parameters for required validation.
     *
     * @param string $fieldName The name of the field being validated
     *
     * @return array<string, string> The error parameters
     */
    public function getRequiredErrorParams(string $fieldName): array
    {
        return ['field_name' => $fieldName];
    }
}
