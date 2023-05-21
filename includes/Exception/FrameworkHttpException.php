<?php

namespace WebFramework\Exception;

use Psr\Http\Message\ServerRequestInterface as Request;

class FrameworkHttpException extends \RuntimeException
{
    public function __construct(
        protected Request $request,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function get_request(): Request
    {
        return $this->request;
    }
}
