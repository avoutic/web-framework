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
 * Exception thrown to trigger a redirect.
 */
class RedirectException extends \Exception
{
    /**
     * RedirectException constructor.
     *
     * @param string          $url      The URL to redirect to
     * @param string          $message  The exception message
     * @param int             $code     The exception code
     * @param null|\Exception $previous The previous exception used for exception chaining
     */
    public function __construct(
        private string $url,
        string $message = '',
        int $code = 0,
        ?\Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the URL to redirect to.
     *
     * @return string The redirect URL
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
