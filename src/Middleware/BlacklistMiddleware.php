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
use WebFramework\Exception\BlacklistException;
use WebFramework\Security\BlacklistService;

/**
 * Middleware to check if a request is blacklisted.
 */
class BlacklistMiddleware implements MiddlewareInterface
{
    /**
     * @param BlacklistService $blacklistService The blacklist service
     */
    public function __construct(
        private BlacklistService $blacklistService,
    ) {}

    /**
     * Process an incoming server request.
     *
     * @param Request                 $request The request
     * @param RequestHandlerInterface $handler The handler
     *
     * @throws BlacklistException If the request is blacklisted
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($this->blacklistService->isBlacklisted(
            $request->getAttribute('ip'),
            $request->getAttribute('user_id'),
        ))
        {
            throw new BlacklistException($request, 'Blacklisted for suspicious behaviour');
        }

        return $handler->handle($request);
    }
}
