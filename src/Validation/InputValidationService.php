<?php

namespace WebFramework\Validation;

use WebFramework\Exception\ValidationException;

class InputValidationService
{
    /** @var array<string, array<int, array{message: string, params: array<string, string>}>> */
    private array $errors = [];

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
     * @param array<Validator>      $validators
     * @param array<string, string> $inputs
     *
     * @return array<string, string>
     */
    public function validate(array $validators, array $inputs): array
    {
        $this->errors = [];
        $validated = [];

        foreach ($validators as $field => $validator)
        {
            $required = $validator->isRequired();
            $value = $inputs[$field] ?? null;

            if ($required && $value === null)
            {
                // Should not be missing in input params if field is required
                //
                throw new \InvalidArgumentException("Required field not present in inputs: {$field}");
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
            }

            if (!$required)
            {
                if ($value === null || !strlen($value))
                {
                    $validated[$field] = '';

                    continue;
                }
            }

            // Value is now non-empty, so other rules can be applied
            //
            $rules = $validator->getRules();

            foreach ($rules as $rule)
            {
                if (!$rule->isValid($value))
                {
                    $this->registerError(
                        $field,
                        $rule->getErrorMessage(),
                        $rule->getErrorParams($validator->getName()),
                    );
                }
                else
                {
                    $validated[$field] = trim($value);
                }
            }
        }

        if (count($this->errors))
        {
            throw new ValidationException(errors: $this->getErrors());
        }

        return $validated;
    }

    /**
     * @return array<string, array<int, array{message: string, params: array<string, string>}>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
