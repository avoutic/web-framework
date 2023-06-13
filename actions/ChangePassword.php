<?php

namespace WebFramework\Actions;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpUnauthorizedException;
use WebFramework\Core\ConfigService;
use WebFramework\Core\MessageService;
use WebFramework\Core\RenderService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Core\UserPasswordService;
use WebFramework\Core\ValidatorService;
use WebFramework\Exception\InvalidPasswordException;
use WebFramework\Exception\WeakPasswordException;
use WebFramework\Security\AuthenticationService;

class ChangePassword
{
    public function __construct(
        protected Container $container,
        protected AuthenticationService $authenticationService,
        protected ConfigService $configService,
        protected MessageService $messageService,
        protected RenderService $renderer,
        protected ResponseEmitter $responseEmitter,
        protected UserPasswordService $userPasswordService,
        protected ValidatorService $validatorService,
    ) {
    }

    protected function customFinalizeChange(): void
    {
    }

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): Response
    {
        $user = $request->getAttribute('user');
        if ($user === null)
        {
            throw new HttpUnauthorizedException($request);
        }

        $params = [
            'core' => [
                'title' => 'Change password',
            ],
        ];

        // Check if this is a true attempt
        //
        if (!$request->getAttribute('passed_csrf'))
        {
            return $this->renderer->render($request, $response, 'change_password.latte', $params);
        }

        $filtered = $this->validatorService->getFilteredParams($request, [
            'orig_password' => FORMAT_PASSWORD,
            'password' => FORMAT_PASSWORD,
            'password2' => FORMAT_PASSWORD,
        ]);

        $errors = false;

        // Check if passwords are present
        //
        if (!strlen($filtered['orig_password']))
        {
            $errors = true;
            $this->messageService->add('error', 'Please enter your current password.');
        }

        if (!strlen($filtered['password']))
        {
            $errors = true;
            $this->messageService->add('error', 'Please enter a password.', 'Passwords can contain any printable character.');
        }

        if (!strlen($filtered['password2']))
        {
            $errors = true;
            $this->messageService->add('error', 'Please enter the password verification.', 'Password verification should match your password.');
        }

        if ($filtered['password'] != $filtered['password2'])
        {
            $errors = true;
            $this->messageService->add('error', 'Passwords don\'t match.', 'Password and password verification should be the same.');
        }

        try
        {
            $this->userPasswordService->changePassword($user, $filtered['orig_password'], $filtered['password']);
        }
        catch (InvalidPasswordException $e)
        {
            $errors = true;
            $this->messageService->add('error', 'Original password is incorrect.', 'Please re-enter your password.');
        }
        catch (WeakPasswordException $e)
        {
            $errors = true;
            $this->messageService->add('error', 'New password is too weak.', 'Use at least 8 characters.');
        }

        if ($errors)
        {
            return $this->renderer->render($request, $response, 'change_password.latte', $params);
        }

        $this->customFinalizeChange();

        // Invalidate old sessions
        //
        $this->authenticationService->invalidateSessions($user->getId());
        $this->authenticationService->authenticate($user);

        // Redirect to main sceen
        //
        $baseUrl = $this->configService->get('base_url');
        $returnPage = $this->configService->get('actions.change_password.return_page');

        $message = $this->messageService->getForUrl('success', 'Password changed successfully');

        return $this->responseEmitter->redirect("{$baseUrl}{$returnPage}?{$message}");
    }
}
