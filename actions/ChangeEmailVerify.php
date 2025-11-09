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
use WebFramework\Exception\DuplicateEmailException;
use WebFramework\Exception\WrongAccountException;
use WebFramework\Http\ResponseEmitter;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\ChangeEmailService;

/**
 * Class ChangeEmailVerify.
 *
 * This action handles the verification process for changing a user's email address.
 */
class ChangeEmailVerify
{
    /**
     * ChangeEmailVerify constructor.
     *
     * @param AuthenticationService $authenticationService The authentication service
     * @param ConfigService         $configService         The configuration service
     * @param ResponseEmitter       $responseEmitter       The response emitter
     * @param ChangeEmailService    $changeEmailService    The change email service
     */
    public function __construct(
        protected AuthenticationService $authenticationService,
        protected ConfigService $configService,
        protected ResponseEmitter $responseEmitter,
        protected ChangeEmailService $changeEmailService,
    ) {}

    /**
     * Handle the email change verification request.
     *
     * @param Request               $request   The current request
     * @param Response              $response  The response object
     * @param array<string, string> $routeArgs Route arguments
     *
     * @return ResponseInterface The response
     *
     * @throws CodeVerificationException If the verification code is invalid or expired
     * @throws WrongAccountException     If the verification is for a different account
     * @throws DuplicateEmailException   If the new email is already in use
     *
     * @uses config actions.change_email.location
     * @uses config actions.change_email.return_page
     * @uses config actions.login.location
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        $user = $this->authenticationService->getAuthenticatedUser();
        $guid = $request->getParam('guid');

        if (!$guid)
        {
            throw new HttpNotFoundException($request);
        }

        $changePage = $this->configService->get('actions.change_email.location');

        try
        {
            $this->changeEmailService->handleData($request, $user, $guid);

            return $this->responseEmitter->buildRedirect(
                $this->configService->get('actions.change_email.return_page'),
                [],
                'success',
                'change_email.success',
            );
        }
        catch (CodeVerificationException $e)
        {
            return $this->responseEmitter->buildRedirect(
                $changePage,
                [],
                'error',
                'change_email.code_expired',
            );
        }
        catch (WrongAccountException $e)
        {
            return $this->responseEmitter->buildRedirect(
                $this->configService->get('actions.login.location'),
                [],
                'error',
                'change_email.other_account',
            );
        }
        catch (DuplicateEmailException $e)
        {
            return $this->responseEmitter->buildRedirect(
                $changePage,
                [],
                'error',
                'change_email.duplicate',
            );
        }
    }
}
