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

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Exception thrown when a user is on the Blacklist.
 */
class BlacklistException extends \RuntimeException
{
    /**
     * BlacklistException constructor.
     *
     * @param Request         $request  The request that triggered the exception
     * @param string          $message  The exception message
     * @param int             $code     The exception code
     * @param null|\Throwable $previous The previous throwable used for exception chaining
     */
    public function __construct(
        private Request $request,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the request that triggered the exception.
     *
     * @return Request The request object
     */
    public function getRequest(): Request
    {
        return $this->request;
    }
}
