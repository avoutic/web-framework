<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Core\Database;

/**
 * Middleware to ensure the request is wrapped in a database transaction.
 */
class TransactionMiddleware implements MiddlewareInterface
{
    /**
     * @param Database $database The database service
     */
    public function __construct(
        private Database $database,
    ) {}

    /**
     * Process an incoming server request.
     *
     * @param Request                 $request The request
     * @param RequestHandlerInterface $handler The handler
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $this->database->startTransaction();

        $response = $handler->handle($request);

        $this->database->commitTransaction();

        return $response;
    }
}
