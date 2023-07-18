<?php

namespace WebFramework\Validation;

use WebFramework\Exception\ValidationException;

class InputValidationService
{
    /** @var array<string, array<int, array{message: string, params: array<string, string>}>> */
    private array $errors = [];

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

                foreach ($values as $value)
                {
                    $this->validateField($field, $value, $required, $isArray, $validator);
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

    private function validateField(string $field, string $value, bool $required, bool $isArray, Validator $validator): void
    {
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
                    $this->validated[$field][] = '';
                }
                else
                {
                    $this->validated[$field] = '';
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

        if ($isArray)
        {
            $this->validated[$field][] = trim($value);
        }
        else
        {
            $this->validated[$field] = trim($value);
        }
    }

    /**
     * @return array<string, array<int, array{message: string, params: array<string, string>}>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
