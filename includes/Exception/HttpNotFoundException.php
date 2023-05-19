<?php

namespace WebFramework\Exception;

use Psr\Http\Message\ServerRequestInterface;

class HttpNotFoundException extends FrameworkHttpException
{
    public function __construct(
        ServerRequestInterface $request,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($request, 'Not Found.', 404, $previous);
    }
}
