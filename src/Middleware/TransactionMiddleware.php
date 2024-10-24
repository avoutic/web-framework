<?php

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Core\Database;

class TransactionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Database $database,
    ) {}

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $this->database->startTransaction();

        $response = $handler->handle($request);

        $this->database->commitTransaction();

        return $response;
    }
}
