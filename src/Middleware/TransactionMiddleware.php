<?php

/*
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
use WebFramework\Database\Database;

/**
 * Middleware to ensure the request is wrapped in a database transaction.
 *
 * The transaction is committed when the handler returns a response, and rolled back when
 * the handler throws. To keep what was written, return a response instead of throwing.
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

        try
        {
            $response = $handler->handle($request);
        }
        catch (\Throwable $e)
        {
            // Any exception rolls back, also the ones that result in a normal response
            // further up, such as a redirect or a 404
            //
            try
            {
                $this->database->rollbackTransaction();
            }
            catch (\Throwable $rollbackError)
            {
                // The exception of the handler is the one that matters to the caller
            }

            throw $e;
        }

        $this->database->commitTransaction();

        return $response;
    }
}
