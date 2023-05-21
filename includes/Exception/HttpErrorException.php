<?php

namespace WebFramework\Exception;

use Psr\Http\Message\ServerRequestInterface as Request;

class HttpErrorException extends FrameworkHttpException
{
    public function __construct(
        Request $request,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($request, 'Internal Error.', 500, $previous);
    }
}
