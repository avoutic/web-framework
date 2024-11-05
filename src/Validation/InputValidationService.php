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

use WebFramework\Exception\MultiValidationException;

/**
 * Class InputValidationService.
 *
 * This service is responsible for validating input data against a set of validators.
 */
class InputValidationService
{
    /** @var array<string, array<int, array{message: string, params: array<string, string>}>> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $all = [];

    /** @var array<string, mixed> */
    private array $validated = [];

    /**
     * Register an error for a specific field.
     *
     * @param string                $field   The field name
     * @param string                $message The error message
     * @param array<string, string> $params  Additional parameters for the error message
     */
    private function registerError(string $field, string $message, array $params = []): void
    {
        $this->errors[$field][] = [
            'message' => $message,
            'params' => $params,
        ];
    }

    /**
     * Validate input data against a set of validators.
     *
     * @param array<Validator>                    $validators The validators to use
     * @param array<string, array<string>|string> $inputs     The input data to validate
     *
     * @return array<string, mixed> The validated data
     *
     * @throws MultiValidationException  If validation fails
     * @throws \InvalidArgumentException If required fields are missing or have incorrect types
     */
    public function validate(array $validators, array $inputs): array
    {
        $this->errors = [];
        $this->validated = [];
        $this->all = [];

        foreach ($validators as $field => $validator)
        {
            $required = $validator->isRequired();
            $isArray = (substr($field, -2) == '[]');

            if ($isArray)
            {
                $field = substr($field, 0, -2);
            }

            if ($required && !isset($inputs[$field]))
            {
                // Should not be missing in input params if field is required
                //
                throw new \InvalidArgumentException("Required field not present in inputs: {$field}");
            }

            if ($isArray)
            {
                $values = $inputs[$field] ?? [];
                if (!is_array($values))
                {
                    throw new \InvalidArgumentException("Array field not array in inputs: {$field}");
                }

                $this->all[$field] = [];
                $this->validated[$field] = [];

                foreach ($values as $key => $value)
                {
                    $this->validateField($field, $value, $required, $isArray, $validator, $key);
                }
            }
            else
            {
                $value = $inputs[$field] ?? '';
                if (!is_string($value))
                {
                    throw new \InvalidArgumentException("String field is array in inputs: {$field}");
                }

                $this->validateField($field, $value, $required, $isArray, $validator);
            }
        }

        if (count($this->errors))
        {
            throw new MultiValidationException(errors: $this->getErrors());
        }

        return $this->validated;
    }

    /**
     * Validate a single field.
     *
     * @param string      $field     The field name
     * @param string      $value     The field value
     * @param bool        $required  Whether the field is required
     * @param bool        $isArray   Whether the field is an array
     * @param Validator   $validator The validator to use
     * @param null|string $key       The array key (if applicable)
     */
    private function validateField(string $field, string $value, bool $required, bool $isArray, Validator $validator, ?string $key = null): void
    {
        if ($isArray)
        {
            $this->all[$field][$key] = $validator->getTyped($value);
        }
        else
        {
            $this->all[$field] = $validator->getTyped($value);
        }

        if (!strlen($value))
        {
            if ($required)
            {
                $this->registerError($field, 'validation.required', ['field_name' => $validator->getName()]);

                return;
            }

            if ($isArray)
            {
                $this->validated[$field][$key] = $validator->getDefault();
            }
            else
            {
                $this->validated[$field] = $validator->getDefault();
            }

            return;
        }

        // Value is now non-empty, so other rules can be applied
        //
        $rules = $validator->getRules();
        $valid = true;

        foreach ($rules as $rule)
        {
            if (!$rule->isValid($value))
            {
                $valid = false;
                $this->registerError(
                    $field,
                    $rule->getErrorMessage(),
                    $rule->getErrorParams($validator->getName()),
                );
            }
        }

        if (!$valid)
        {
            return;
        }

        $value = $validator->getTyped($value);
        if ($isArray)
        {
            $this->validated[$field][$key] = $value;
        }
        else
        {
            $this->validated[$field] = $value;
        }
    }

    /**
     * Get all input data (including invalid inputs).
     *
     * @return array<string, mixed> All input data
     */
    public function getAll(): array
    {
        return $this->all;
    }

    /**
     * Get all validation errors.
     *
     * @return array<string, array<int, array{message: string, params: array<string, string>}>> The validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get all validated data.
     *
     * @return array<string, mixed> The validated data
     */
    public function getValidated(): array
    {
        return $this->validated;
    }
}
