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
 * Exception thrown when verification fails.
 */
class VerifyException extends \Exception
{
    /**
     * VerifyException constructor.
     *
     * @param string          $message  The exception message
     * @param int             $code     The exception code
     * @param null|\Throwable $previous The previous throwable used for exception chaining
     */
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
