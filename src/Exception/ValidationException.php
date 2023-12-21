<?php

namespace WebFramework\Exception;

class ValidationException extends \Exception
{
    /** @var array<string, array<int, array{message: string, params: array<string, string>}>> */
    private array $errors = [];

    /**
     * @param array<string, string> $params
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
     * @param array<string, array<int, array{message: string, params: array<string, string>}>> $errors
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    /**
     * @return array<string, array<int, array{message: string, params: array<string, string>}>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
