<?php

namespace WebFramework\Exception;

use Psr\Http\Message\ServerRequestInterface;

class HttpForbiddenException extends FrameworkHttpException
{
    public function __construct(
        ServerRequestInterface $request,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($request, 'Forbidden.', 403, $previous);
    }
}
