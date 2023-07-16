<?php

namespace WebFramework\Exception;

class ValidationException extends \Exception
{
    public function __construct(
        /** @var array<string, array<int, array{message: string, params: array<string, string>}>> */
        private array $errors = [],
    ) {
    }

    /**
     * @return array<string, array<int, array{message: string, params: array<string, string>}>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
