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
use WebFramework\Core\UserPasswordService;
use WebFramework\Entity\User;
use WebFramework\Exception\InvalidPasswordException;
use WebFramework\Exception\ValidationException;
use WebFramework\Exception\WeakPasswordException;
use WebFramework\Security\AuthenticationService;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\PasswordValidator;

class ChangePassword
{
    public function __construct(
        protected Container $container,
        protected AuthenticationService $authenticationService,
        protected ConfigService $configService,
        protected InputValidationService $inputValidationService,
        protected MessageService $messageService,
        protected RenderService $renderer,
        protected ResponseEmitter $responseEmitter,
        protected UserPasswordService $userPasswordService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    protected function customParams(Request $request): array
    {
        return [];
    }

    protected function customFinalizeChange(Request $request, User $user): void
    {
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
                    'password' => new PasswordValidator('New password'),
                    'password2' => new PasswordValidator('Password verification'),
                ],
                $request->getParams(),
            );
        }
        catch (ValidationException $e)
        {
            $this->messageService->addErrors($e->getErrors());

            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        $errors = false;

        if (strlen($filtered['password']) && strlen($filtered['password2'])
            && $filtered['password'] !== $filtered['password2'])
        {
            $errors = true;
            $this->messageService->add('error', 'register.password_mismatch');
        }

        if ($errors)
        {
            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        try
        {
            $this->userPasswordService->changePassword($user, $filtered['orig_password'], $filtered['password']);
        }
        catch (InvalidPasswordException $e)
        {
            $errors = true;
            $this->messageService->add('error', 'change_password.invalid');
        }
        catch (WeakPasswordException $e)
        {
            $errors = true;
            $this->messageService->add('error', 'change_password.weak');
        }

        if ($errors)
        {
            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        $this->customFinalizeChange($request, $user);

        // Invalidate old sessions
        //
        $this->authenticationService->invalidateSessions($user->getId());
        $this->authenticationService->authenticate($user);

        // Redirect to main sceen
        //
        return $this->responseEmitter->buildRedirect(
            $this->getReturnPage(),
            [],
            'success',
            'change_password.success',
        );
    }
}
