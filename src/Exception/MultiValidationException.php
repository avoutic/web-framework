<?php

namespace WebFramework\Exception;

class MultiValidationException extends ValidationException
{
    /**
     * @param array<string, array<int, array{message: string, params: array<string, string>}>> $errors
     */
    public function __construct(
        array $errors = [],
    ) {
        $this->setErrors($errors);
    }
}
