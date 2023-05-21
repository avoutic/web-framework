<?php

namespace WebFramework\Exception;

use Psr\Http\Message\ServerRequestInterface as Request;

class HttpNotFoundException extends FrameworkHttpException
{
    public function __construct(
        Request $request,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($request, 'Not Found.', 404, $previous);
    }
}
