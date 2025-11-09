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
use WebFramework\Exception\InvalidPasswordException;
use WebFramework\Exception\PasswordMismatchException;
use WebFramework\Exception\ValidationException;
use WebFramework\Exception\WeakPasswordException;
use WebFramework\Http\ResponseEmitter;
use WebFramework\Presentation\MessageService;
use WebFramework\Presentation\RenderService;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\ChangePasswordService;
use WebFramework\Security\Extension\ChangePasswordExtensionInterface;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\Validator\PasswordValidator;

/**
 * Class ChangePassword.
 *
 * This action handles the process of changing a user's password.
 */
class ChangePassword
{
    /**
     * ChangePassword constructor.
     *
     * @param AuthenticationService            $authenticationService   The authentication service
     * @param ChangePasswordExtensionInterface $changePasswordExtension The change password extension
     * @param ChangePasswordService            $changePasswordService   The change password service
     * @param ConfigService                    $configService           The configuration service
     * @param InputValidationService           $inputValidationService  The input validation service
     * @param MessageService                   $messageService          The message service
     * @param RenderService                    $renderer                The render service
     * @param ResponseEmitter                  $responseEmitter         The response emitter
     * @param string                           $templateName            The template name
     */
    public function __construct(
        private AuthenticationService $authenticationService,
        private ChangePasswordExtensionInterface $changePasswordExtension,
        private ChangePasswordService $changePasswordService,
        private ConfigService $configService,
        private InputValidationService $inputValidationService,
        private MessageService $messageService,
        private RenderService $renderer,
        private ResponseEmitter $responseEmitter,
        private string $templateName,
    ) {}

    /**
     * Handle the change password request.
     *
     * @param Request               $request   The current request
     * @param Response              $response  The response object
     * @param array<string, string> $routeArgs Route arguments
     *
     * @return ResponseInterface The response
     *
     * @throws ValidationException       If the input validation fails
     * @throws PasswordMismatchException If the new passwords don't match
     * @throws InvalidPasswordException  If the current password is incorrect
     * @throws WeakPasswordException     If the new password is too weak
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        $user = $this->authenticationService->getAuthenticatedUser();
        $params = $this->changePasswordExtension->getCustomParams($request);
        $csrfPassed = $request->getAttribute('passed_csrf', false);

        if (!$csrfPassed)
        {
            return $this->renderer->render($request, $response, $this->templateName, $params);
        }

        try
        {
            // Validate input
            //
            $filtered = $this->inputValidationService->validate(
                [
                    'orig_password' => new PasswordValidator('current password'),
                    'password' => new PasswordValidator('new password'),
                    'password2' => new PasswordValidator('password verification'),
                ],
                $request->getParams(),
            );

            $this->changePasswordService->validate($user, $filtered['orig_password'], $filtered['password'], $filtered['password2']);

            if ($this->changePasswordExtension->customValueCheck($request, $user))
            {
                // Change Password
                //
                $this->changePasswordService->changePassword($request, $user, $filtered['orig_password'], $filtered['password']);

                // Redirect to main sceen
                //
                return $this->responseEmitter->buildRedirect(
                    $this->configService->get('actions.change_password.return_page'),
                    [],
                    'success',
                    'change_password.success',
                );
            }
        }
        catch (ValidationException $e)
        {
            $this->messageService->addErrors($e->getErrors());

            return $this->renderer->render($request, $response, $this->templateName, $params);
        }
        catch (PasswordMismatchException $e)
        {
            $this->messageService->add('error', 'register.password_mismatch');
        }
        catch (InvalidPasswordException $e)
        {
            $this->messageService->add('error', 'change_password.invalid');
        }
        catch (WeakPasswordException $e)
        {
            $this->messageService->add('error', 'change_password.weak');
        }

        return $this->renderer->render($request, $response, $this->templateName, $params);
    }
}
