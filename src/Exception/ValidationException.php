<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Exception;

/**
 * Exception thrown when validation fails.
 */
class ValidationException extends \Exception
{
    /** @var array<string, array<int, array{message: string, params: array<string, string>}>> */
    private array $errors = [];

    /**
     * ValidationException constructor.
     *
     * @param string                $field   The field that failed validation
     * @param string                $message The error message
     * @param array<string, string> $params  Additional parameters for the error message
     */
    public function __construct(
        string $field,
        string $message,
        array $params = [],
    ) {
        $this->errors[$field][] = [
            'message' => $message,
            'params' => $params,
        ];
    }

    /**
     * Set the validation errors.
     *
     * @param array<string, array<int, array{message: string, params: array<string, string>}>> $errors The validation errors
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    /**
     * Get the validation errors.
     *
     * @return array<string, array<int, array{message: string, params: array<string, string>}>> The validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
