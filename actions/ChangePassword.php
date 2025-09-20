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

use Psr\Container\ContainerInterface as Container;
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
     * @param Container              $container              The dependency injection container
     * @param AuthenticationService  $authenticationService  The authentication service
     * @param ChangePasswordService  $changePasswordService  The change password service
     * @param ConfigService          $configService          The configuration service
     * @param InputValidationService $inputValidationService The input validation service
     * @param MessageService         $messageService         The message service
     * @param RenderService          $renderer               The render service
     * @param ResponseEmitter        $responseEmitter        The response emitter
     */
    public function __construct(
        protected Container $container,
        protected AuthenticationService $authenticationService,
        protected ChangePasswordService $changePasswordService,
        protected ConfigService $configService,
        protected InputValidationService $inputValidationService,
        protected MessageService $messageService,
        protected RenderService $renderer,
        protected ResponseEmitter $responseEmitter,
    ) {
        $this->init();
    }

    /**
     * Initialize the action.
     */
    public function init(): void {}

    /**
     * Get custom parameters for the action.
     *
     * @param Request $request The current request
     *
     * @return array<string, mixed> Custom parameters
     */
    protected function customParams(Request $request): array
    {
        return [];
    }

    /**
     * Get the template name for rendering.
     *
     * @return string The template name
     */
    protected function getTemplateName(): string
    {
        return 'ChangePassword.latte';
    }

    /**
     * Get the return page after successful password change.
     *
     * @return string The return page URL
     *
     * @uses config actions.change_password.return_page
     */
    protected function getReturnPage(): string
    {
        return $this->configService->get('actions.change_password.return_page');
    }

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
        $params = $this->customParams($request);
        $csrfPassed = $request->getAttribute('passed_csrf', false);

        if (!$csrfPassed)
        {
            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
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

            // Change Password
            //
            $this->changePasswordService->changePassword($request, $user, $filtered['orig_password'], $filtered['password']);

            // Redirect to main sceen
            //
            return $this->responseEmitter->buildRedirect(
                $this->getReturnPage(),
                [],
                'success',
                'change_password.success',
            );
        }
        catch (ValidationException $e)
        {
            $this->messageService->addErrors($e->getErrors());

            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
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

        return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
    }
}
