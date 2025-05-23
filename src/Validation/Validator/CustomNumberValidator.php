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

use WebFramework\Validation\Rule\MaxValueRule;
use WebFramework\Validation\Rule\MinValueRule;
use WebFramework\Validation\ValidationRule;

/**
 * Class CustomNumberValidator.
 *
 * This class extends CustomValidator to provide number validation functionality.
 */
class CustomNumberValidator extends CustomValidator
{
    private ?int $minValue = null;
    private ?int $maxValue = null;

    /**
     * CustomNumberValidator constructor.
     *
     * @param string $name The name of the field to validate
     */
    public function __construct(
        string $name,
    ) {
        parent::__construct($name);

        $this->filter('\d+')->default(null);
    }

    /**
     * Convert the validated string value to an integer.
     *
     * @param string $value The value to convert
     *
     * @return null|int The converted integer value or null if empty
     */
    public function getTyped(string $value): mixed
    {
        if (!strlen($value))
        {
            return $this->getDefault();
        }

        return (int) $value;
    }

    /**
     * Get the validation rules for this validator.
     *
     * @return array<ValidationRule> The array of validation rules
     */
    public function getRules(): array
    {
        $rules = parent::getRules();

        if ($this->minValue !== null)
        {
            $rules[] = new MinValueRule($this->minValue);
        }

        if ($this->maxValue !== null)
        {
            $rules[] = new MaxValueRule($this->maxValue);
        }

        return $rules;
    }

    /**
     * Set the minimum allowed value.
     *
     * @param int $value The minimum value
     */
    public function minValue(int $value): self
    {
        $this->minValue = $value;

        return $this;
    }

    /**
     * Set the maximum allowed value.
     *
     * @param int $value The maximum value
     */
    public function maxValue(int $value): self
    {
        $this->maxValue = $value;

        return $this;
    }
}
