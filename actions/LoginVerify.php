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
use Slim\Exception\HttpNotFoundException;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Config\ConfigService;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Http\ResponseEmitter;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\UserVerificationService;

/**
 * Class LoginVerify.
 *
 * This action handles logging in a user after verification.
 */
class LoginVerify
{
    /**
     * LoginVerify constructor.
     *
     * @param AuthenticationService   $authenticationService   The authentication service
     * @param ConfigService           $configService           The configuration service
     * @param ResponseEmitter         $responseEmitter         The response emitter
     * @param UserVerificationService $userVerificationService The user verification service
     */
    public function __construct(
        protected AuthenticationService $authenticationService,
        protected ConfigService $configService,
        protected ResponseEmitter $responseEmitter,
        protected UserVerificationService $userVerificationService,
    ) {}

    /**
     * Handle the login verification request.
     *
     * @param Request               $request   The current request
     * @param Response              $response  The response object
     * @param array<string, string> $routeArgs Route arguments
     *
     * @return ResponseInterface The response
     *
     * @throws CodeVerificationException If the verification code is invalid or expired
     *
     * @uses config actions.login.location
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        $guid = $request->getParam('guid');

        if (!$guid)
        {
            throw new HttpNotFoundException($request);
        }

        try
        {
            ['user' => $user, 'after_verify_data' => $afterVerifyData] = $this->userVerificationService->handleData($request, $guid, 'login');

            $this->authenticationService->authenticate($user);

            return $this->responseEmitter->buildQueryRedirect(
                $afterVerifyData['return_page'] ?? '/',
                [],
                $afterVerifyData['return_query'] ?? [],
                'success',
                'verify.success',
            );
        }
        catch (CodeVerificationException $e)
        {
            return $this->responseEmitter->buildRedirect(
                $this->configService->get('actions.login.location'),
                [],
                'error',
                'verify.code_expired',
            );
        }
    }
}
