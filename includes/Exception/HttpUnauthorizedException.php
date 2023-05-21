<?php

namespace WebFramework\Exception;

use Psr\Http\Message\ServerRequestInterface as Request;

class HttpUnauthorizedException extends FrameworkHttpException
{
    public function __construct(
        Request $request,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($request, 'Unauthorized.', 401, $previous);
    }
}
