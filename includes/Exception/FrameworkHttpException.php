<?php

namespace WebFramework\Exception;

use Psr\Http\Message\ServerRequestInterface;

class FrameworkHttpException extends \RuntimeException
{
    public function __construct(
        protected ServerRequestInterface $request,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function get_request(): ServerRequestInterface
    {
        return $this->request;
    }
}
