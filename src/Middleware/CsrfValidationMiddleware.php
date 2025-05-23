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
use WebFramework\Core\MessageService;
use WebFramework\Security\BlacklistService;
use WebFramework\Security\CsrfService;

/**
 * Middleware to validate CSRF tokens.
 *
 * Requires the IpMiddleware and AuthenticationMiddleware to run first.
 *
 * Adds the 'passed_csrf' attribute to the request.
 */
class CsrfValidationMiddleware implements MiddlewareInterface
{
    /**
     * @param BlacklistService $blacklistService The blacklist service
     * @param CsrfService      $csrfService      The CSRF service
     * @param MessageService   $messageService   The message service
     */
    public function __construct(
        private BlacklistService $blacklistService,
        private CsrfService $csrfService,
        private MessageService $messageService,
    ) {}

    /**
     * Process an incoming server request.
     *
     * @param Request                 $request The request
     * @param RequestHandlerInterface $handler The handler
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $passedCsrf = false;

        // Only check CSRF for state-changing methods
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH']))
        {
            $params = $request->getParsedBody();
            $token = '';

            if (is_array($params))
            {
                $token = $params['token'] ?? '';
            }
            else
            {
                $token = $params->token ?? '';
            }

            if (!$this->csrfService->validateToken($token))
            {
                $ip = $request->getAttribute('ip');

                if ($ip === null)
                {
                    throw new \RuntimeException('IpMiddleware must run first');
                }

                $isAuthenticated = $request->getAttribute('is_authenticated');

                if ($isAuthenticated === null)
                {
                    throw new \RuntimeException('AuthenticationMiddleware must run first');
                }

                $authenticatedUserId = $request->getAttribute('authenticated_user_id');

                $this->blacklistService->addEntry($ip, $authenticatedUserId, 'missing-csrf');
                $this->messageService->add('error', 'generic.csrf_missing');
            }
            else
            {
                $passedCsrf = true;
            }
        }

        $request = $request->withAttribute('passed_csrf', $passedCsrf);

        return $handler->handle($request);
    }
}
