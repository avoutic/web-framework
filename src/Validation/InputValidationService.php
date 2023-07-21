<?php

namespace WebFramework\Validation;

use WebFramework\Exception\ValidationException;

class InputValidationService
{
    /** @var array<string, array<int, array{message: string, params: array<string, string>}>> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $all = [];

    /** @var array<string, mixed> */
    private array $validated = [];

    /**
     * @param array<string, string> $params
     */
    private function registerError(string $field, string $message, array $params = []): void
    {
        $this->errors[$field][] = [
            'message' => $message,
            'params' => $params,
        ];
    }

    /**
     * @param array<Validator>                    $validators
     * @param array<string, array<string>|string> $inputs
     *
     * @return array<string, mixed>
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
            throw new ValidationException(errors: $this->getErrors());
        }

        return $this->validated;
    }

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

        if ($required && !strlen($value))
        {
            $this->registerError(
                $field,
                'validation.required',
                [
                    'field_name' => $validator->getName(),
                ],
            );

            return;
        }

        if (!$required)
        {
            if (!strlen($value))
            {
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
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return $this->all;
    }

    /**
     * @return array<string, array<int, array{message: string, params: array<string, string>}>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function getValidated(): array
    {
        return $this->validated;
    }
}
