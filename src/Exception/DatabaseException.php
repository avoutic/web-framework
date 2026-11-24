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
 * Exception thrown when a database operation fails.
 *
 * Carries the error details of the database driver, so callers do not have to parse
 * error messages or depend on a specific driver.
 */
class DatabaseException extends \RuntimeException
{
    /**
     * DatabaseException constructor.
     *
     * @param string            $message       The message describing the failed operation
     * @param null|int          $errorCode     The error number of the database driver, if any
     * @param null|string       $sqlState      The SQLSTATE of the error, if any
     * @param DatabaseErrorType $type          The kind of error, as classified by the database driver
     * @param string            $driverMessage The error message of the database driver
     * @param null|\Throwable   $previous      The exception of the database driver, if any
     */
    public function __construct(
        string $message,
        private ?int $errorCode = null,
        private ?string $sqlState = null,
        private DatabaseErrorType $type = DatabaseErrorType::Other,
        private string $driverMessage = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $errorCode ?? 0, $previous);
    }

    /**
     * Get the error number of the database driver.
     *
     * @return null|int The error number, or null if the driver gave none
     */
    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }

    /**
     * Get the SQLSTATE of the error.
     *
     * @return null|string The SQLSTATE, or null if the driver gave none
     */
    public function getSqlState(): ?string
    {
        return $this->sqlState;
    }

    /**
     * Get the kind of error.
     *
     * @return DatabaseErrorType The kind of error
     */
    public function getType(): DatabaseErrorType
    {
        return $this->type;
    }

    /**
     * Get the error message of the database driver.
     *
     * @return string The driver error message, or an empty string if the driver gave none
     */
    public function getDriverMessage(): string
    {
        return $this->driverMessage;
    }

    /**
     * Check if the transaction can be tried again: the error is the result of another
     * transaction getting in the way (a deadlock, a lock wait timeout or a changed record),
     * not of what this transaction tried to do.
     */
    public function isRetryable(): bool
    {
        return in_array($this->type, [
            DatabaseErrorType::Deadlock,
            DatabaseErrorType::LockTimeout,
            DatabaseErrorType::RecordChanged,
        ], true);
    }

    /**
     * Check if the error is a violation of a unique or primary key constraint.
     */
    public function isDuplicateKey(): bool
    {
        return $this->type === DatabaseErrorType::DuplicateKey;
    }

    /**
     * Check if the error is a timeout while waiting for a lock.
     */
    public function isLockTimeout(): bool
    {
        return $this->type === DatabaseErrorType::LockTimeout;
    }

    /**
     * Check if the error is a deadlock.
     */
    public function isDeadlock(): bool
    {
        return $this->type === DatabaseErrorType::Deadlock;
    }
}
