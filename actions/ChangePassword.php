<?php

namespace WebFramework\Actions;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Core\MessageService;
use WebFramework\Core\RenderService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Exception\InvalidPasswordException;
use WebFramework\Exception\PasswordMismatchException;
use WebFramework\Exception\ValidationException;
use WebFramework\Exception\WeakPasswordException;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\ChangePasswordService;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\PasswordValidator;

class ChangePassword
{
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
    }

    /**
     * @return array<string, mixed>
     */
    protected function customParams(Request $request): array
    {
        return [];
    }

    protected function getTemplateName(): string
    {
        return 'ChangePassword.latte';
    }

    protected function getReturnPage(): string
    {
        return $this->configService->get('actions.change_password.return_page');
    }

    /**
     * @param array<string, string> $routeArgs
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
            $this->changePasswordService->changePassword($user, $filtered['orig_password'], $filtered['password']);

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
