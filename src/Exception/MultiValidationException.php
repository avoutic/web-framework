<?php

namespace WebFramework\Exception;

/**
 * Exception thrown when multiple validation errors occur.
 */
class MultiValidationException extends ValidationException
{
    /**
     * MultiValidationException constructor.
     *
     * @param array<string, array<int, array{message: string, params: array<string, string>}>> $errors An array of validation errors
     */
    public function __construct(
        array $errors = [],
    ) {
        $this->setErrors($errors);
    }
}
