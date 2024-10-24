<?php

namespace WebFramework\Actions;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Core\MessageService;
use WebFramework\Core\RenderService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Core\ValidatorService;
use WebFramework\Exception\ValidationException;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\ResetPasswordService;
use WebFramework\Validation\EmailValidator;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\UsernameValidator;

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
     * @param UserRepository         $userRepository         The user repository
     * @param ValidatorService       $validatorService       The validator service
     */
    public function __construct(
        protected ConfigService $configService,
        protected InputValidationService $inputValidationService,
        protected MessageService $messageService,
        protected RenderService $renderer,
        protected ResponseEmitter $responseEmitter,
        protected ResetPasswordService $resetPasswordService,
        protected UserRepository $userRepository,
        protected ValidatorService $validatorService,
    ) {}

    /**
     * Get the template name for rendering.
     *
     * @return string The template name
     */
    protected function getTemplateName(): string
    {
        return 'ForgotPassword.latte';
    }

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
            return $this->renderer->render($request, $response, $this->getTemplateName(), []);
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

            if ($user !== null)
            {
                $this->resetPasswordService->sendPasswordResetMail($user);
            }

            // Redirect to main sceen
            //
            return $this->responseEmitter->buildRedirect(
                $this->configService->get('actions.login.location'),
                [],
                'success',
                'forgot_password.reset_link_mailed',
            );
        }
        catch (ValidationException $e)
        {
            $this->messageService->addErrors($e->getErrors());
        }

        return $this->renderer->render($request, $response, $this->getTemplateName(), []);
    }
}
