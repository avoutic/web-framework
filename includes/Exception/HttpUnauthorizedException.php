<?php

namespace WebFramework\Exception;

use Psr\Http\Message\ServerRequestInterface;

class HttpUnauthorizedException extends FrameworkHttpException
{
    public function __construct(
        ServerRequestInterface $request,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($request, 'Unauthorized.', 401, $previous);
    }
}
