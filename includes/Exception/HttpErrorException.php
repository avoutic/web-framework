<?php

namespace WebFramework\Exception;

use Psr\Http\Message\ServerRequestInterface;

class HttpErrorException extends FrameworkHttpException
{
    public function __construct(
        ServerRequestInterface $request,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($request, 'Internal Error.', 500, $previous);
    }
}
