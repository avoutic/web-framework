<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Exception;

/**
 * Exception thrown when a mail backend fails to send a mail.
 *
 * Mail backends throw this instead of the exceptions of their own client library, so
 * callers do not depend on a specific backend. The exception of the client library is
 * available as the previous exception.
 */
class MailException extends \RuntimeException
{
    /**
     * MailException constructor.
     *
     * @param string          $message   The message describing the failure
     * @param null|int        $errorCode The error code of the mail backend, if any
     * @param null|\Throwable $previous  The exception of the mail backend, if any
     */
    public function __construct(
        string $message,
        private ?int $errorCode = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $errorCode ?? 0, $previous);
    }

    /**
     * Get the error code of the mail backend.
     *
     * @return null|int The error code, or null if the backend gave none
     */
    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }
}
