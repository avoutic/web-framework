<?php

namespace WebFramework\Exception;

use Psr\Http\Message\ServerRequestInterface as Request;

class HttpForbiddenException extends FrameworkHttpException
{
    public function __construct(
        Request $request,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($request, 'Forbidden.', 403, $previous);
    }
}
