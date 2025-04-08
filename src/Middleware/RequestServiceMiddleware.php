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
use WebFramework\Support\RequestService;

/**
 * Middleware to store the request in the RequestService.
 *
 * Requires the IpMiddleware, AuthenticationMiddleware, and CsrfValidationMiddleware to run first.
 */
class RequestServiceMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RequestService $requestService,
    ) {}

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($request->getAttribute('ip') === null)
        {
            throw new \RuntimeException('IpMiddleware must run first');
        }

        if ($request->getAttribute('is_authenticated') === null)
        {
            throw new \RuntimeException('AuthenticationMiddleware must run first');
        }

        if ($request->getAttribute('passed_csrf') === null)
        {
            throw new \RuntimeException('CsrfValidationMiddleware must run first');
        }

        $this->requestService->setRequest($request);

        return $handler->handle($request);
    }
}
