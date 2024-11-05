<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Actions;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Security\AuthenticationService;

/**
 * Class Logoff.
 *
 * This action handles the user logout process.
 */
class Logoff
{
    /**
     * Logoff constructor.
     *
     * @param AuthenticationService $authenticationService The authentication service
     * @param ResponseEmitter       $responseEmitter       The response emitter
     */
    public function __construct(
        protected AuthenticationService $authenticationService,
        protected ResponseEmitter $responseEmitter,
    ) {}

    /**
     * Handle the logoff request.
     *
     * @param Request               $request   The current request
     * @param Response              $response  The response object
     * @param array<string, string> $routeArgs Route arguments
     *
     * @return ResponseInterface The response
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        $this->authenticationService->deauthenticate();

        return $this->responseEmitter->buildRedirect('/');
    }
}
