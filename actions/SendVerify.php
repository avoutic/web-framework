<?php

/**
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
use WebFramework\Core\ConfigService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Security\UserVerificationService;

/**
 * Class SendVerify.
 *
 * This action handles sending verification emails to users.
 */
class SendVerify
{
    /**
     * SendVerify constructor.
     *
     * @param ConfigService           $configService           The configuration service
     * @param ResponseEmitter         $responseEmitter         The response emitter
     * @param UserVerificationService $userVerificationService The user verification service
     */
    public function __construct(
        protected ConfigService $configService,
        protected ResponseEmitter $responseEmitter,
        protected UserVerificationService $userVerificationService,
    ) {}

    /**
     * Handle the send verification request.
     *
     * @param Request               $request   The current request
     * @param Response              $response  The response object
     * @param array<string, string> $routeArgs Route arguments
     *
     * @return ResponseInterface The response
     *
     * @throws CodeVerificationException If the verification code is invalid or expired
     *
     * @uses config actions.send_verify.after_verify_page
     * @uses config actions.login.location
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        try
        {
            $this->userVerificationService->handleSendVerify($request->getParam('code', ''));

            return $this->responseEmitter->buildRedirect(
                $this->configService->get('actions.send_verify.after_verify_page'),
                [],
                'success',
                'verify.mail_sent',
            );
        }
        catch (CodeVerificationException $e)
        {
            return $this->responseEmitter->buildRedirect(
                $this->configService->get('actions.login.location'),
                [],
                'error',
                'verify.link_expired',
            );
        }
    }
}
