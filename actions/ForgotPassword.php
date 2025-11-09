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
use WebFramework\Exception\ValidationException;
use WebFramework\Http\ResponseEmitter;
use WebFramework\Presentation\MessageService;
use WebFramework\Presentation\RenderService;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\ResetPasswordService;
use WebFramework\Security\UserCodeService;
use WebFramework\Support\UuidProvider;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\Validator\EmailValidator;
use WebFramework\Validation\Validator\UsernameValidator;

/**
 * Class ForgotPassword.
 *
 * This action handles the process of initiating a password reset.
 */
class ForgotPassword
{
    /**
     * ForgotPassword constructor.
     *
     * @param ConfigService          $configService          The configuration service
     * @param InputValidationService $inputValidationService The input validation service
     * @param MessageService         $messageService         The message service
     * @param RenderService          $renderer               The render service
     * @param ResponseEmitter        $responseEmitter        The response emitter
     * @param ResetPasswordService   $resetPasswordService   The reset password service
     * @param UserCodeService        $userCodeService        The user code service
     * @param UserRepository         $userRepository         The user repository
     * @param UuidProvider           $uuidProvider           The UUID provider
     */
    public function __construct(
        protected ConfigService $configService,
        protected InputValidationService $inputValidationService,
        protected MessageService $messageService,
        protected RenderService $renderer,
        protected ResponseEmitter $responseEmitter,
        protected ResetPasswordService $resetPasswordService,
        protected UserCodeService $userCodeService,
        protected UserRepository $userRepository,
        protected UuidProvider $uuidProvider,
        protected string $templateName,
    ) {}

    /**
     * Handle the forgot password request.
     *
     * @param Request               $request   The current request
     * @param Response              $response  The response object
     * @param array<string, string> $routeArgs Route arguments
     *
     * @return ResponseInterface The response
     *
     * @throws ValidationException If the input validation fails
     *
     * @uses config authenticator.unique_identifier
     * @uses config actions.login.location
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        $csrfPassed = $request->getAttribute('passed_csrf', false);

        if (!$csrfPassed)
        {
            return $this->renderer->render($request, $response, $this->templateName, []);
        }

        $uniqueIdentifier = $this->configService->get('authenticator.unique_identifier');
        $validator = ($uniqueIdentifier === 'email') ? new EmailValidator('username') : new UsernameValidator();

        try
        {
            // Validate input
            //
            $filtered = $this->inputValidationService->validate(
                ['username' => $validator->required()],
                $request->getParams(),
            );

            // Retrieve user
            //
            $user = $this->userRepository->getUserByUsername($filtered['username']);

            if ($user === null)
            {
                // Don't reveal if user exists - still redirect to verify page
                // but without a valid GUID, verification will fail gracefully
                $guid = $this->uuidProvider->generate();
            }
            else
            {
                $guid = $this->resetPasswordService->sendPasswordResetMail($user);
            }

            return $this->responseEmitter->buildRedirect(
                $this->configService->get('actions.verify.location'),
                [
                    'flow' => 'reset_password',
                    'guid' => $guid,
                ],
                'success',
                'forgot_password.reset_link_mailed',
            );
        }
        catch (ValidationException $e)
        {
            $this->messageService->addErrors($e->getErrors());
        }

        return $this->renderer->render($request, $response, $this->templateName, []);
    }
}
