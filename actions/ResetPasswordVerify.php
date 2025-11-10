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
use WebFramework\Config\ConfigService;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Http\ResponseEmitter;
use WebFramework\Security\ResetPasswordService;

/**
 * Class ResetPasswordVerify.
 *
 * This action handles the password reset process.
 */
class ResetPasswordVerify
{
    /**
     * ResetPasswordVerify constructor.
     *
     * @param ConfigService        $configService        The configuration service
     * @param ResponseEmitter      $responseEmitter      The response emitter
     * @param ResetPasswordService $resetPasswordService The reset password service
     */
    public function __construct(
        protected ConfigService $configService,
        protected ResponseEmitter $responseEmitter,
        protected ResetPasswordService $resetPasswordService,
    ) {}

    /**
     * Handle the password reset verify request.
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
     * @uses config actions.reset_password.location
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        try
        {
            $this->resetPasswordService->handleData($request, $request->getParam('guid', ''));

            // Redirect to main screen
            //
            return $this->responseEmitter->buildRedirect(
                $this->configService->get('actions.login.location'),
                [],
                'success',
                'reset_password.success',
            );
        }
        catch (CodeVerificationException $e)
        {
            return $this->responseEmitter->buildRedirect(
                $this->configService->get('actions.reset_password.location'),
                [],
                'error',
                'verify.code_expired',
            );
        }
    }
}
