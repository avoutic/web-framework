<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Validation;

/**
 * Class CustomValidator.
 *
 * This class implements the Validator interface and provides basic validation functionality.
 */
class CustomValidator implements Validator
{
    private ?string $filter = null;
    private bool $required = false;
    private ?int $minLength = null;
    private ?int $maxLength = null;
    private mixed $default = '';

    /**
     * CustomValidator constructor.
     *
     * @param string $name The name of the field to validate
     */
    public function __construct(
        private string $name,
    ) {}

    /**
     * Set the filter for this validator.
     *
     * @param string $filter The filter regex
     */
    public function filter(string $filter): self
    {
        $this->filter = $filter;

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
     */
    public function required(): self
    {
        $this->required = true;

        return $this;
    }

    /**
     * Set the minimum length for this field.
     *
     * @param int $length The minimum length
     */
    public function minLength(int $length): self
    {
        $this->minLength = $length;

        return $this;
    }

    /**
     * Set the maximum length for this field.
     *
     * @param int $length The maximum length
     */
    public function maxLength(int $length): self
    {
        $this->maxLength = $length;

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
        $rules = [];

        if ($this->filter !== null)
        {
            $rules[] = new FilterRule($this->filter);
        }

        if ($this->minLength !== null)
        {
            $rules[] = new MinLengthRule($this->minLength);
        }

        if ($this->maxLength !== null)
        {
            $rules[] = new MaxLengthRule($this->maxLength);
        }

        return $rules;
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
}
