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
