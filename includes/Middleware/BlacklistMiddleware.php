<?php

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Core\Security\BlacklistService;
use WebFramework\Exception\FrameworkHttpException;

class BlacklistMiddleware implements MiddlewareInterface
{
    public function __construct(
        private BlacklistService $blacklist_service,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($this->blacklist_service->is_blacklisted(
            $request->getAttribute('ip'),
            $request->getAttribute('user_id'),
        ))
        {
            throw new FrameworkHttpException($request, 'Blacklisted for suspicious behaviour');
        }

        return $handler->handle($request);
    }
}
