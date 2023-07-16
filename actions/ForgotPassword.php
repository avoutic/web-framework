<?php

namespace WebFramework\Actions;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Core\MessageService;
use WebFramework\Core\RenderService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Core\UserPasswordService;
use WebFramework\Core\ValidatorService;
use WebFramework\Repository\UserRepository;

class ForgotPassword
{
    public function __construct(
        protected Container $container,
        protected ConfigService $configService,
        protected MessageService $messageService,
        protected RenderService $renderer,
        protected ResponseEmitter $responseEmitter,
        protected UserPasswordService $userPasswordService,
        protected UserRepository $userRepository,
        protected ValidatorService $validatorService,
    ) {
    }

    protected function getTemplateName(): string
    {
        return 'ForgotPassword.latte';
    }

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): Response
    {
        $uniqueIdentifier = $this->configService->get('authenticator.unique_identifier');

        $filtered = $this->validatorService->getFilteredParams($request, [
            'username' => ($uniqueIdentifier === 'email') ? FORMAT_EMAIL : FORMAT_USERNAME,
        ]);

        $params = [
            'core' => [
                'title' => 'Forgot password',
            ],
        ];

        if (!$request->getAttribute('passed_csrf'))
        {
            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        if (!strlen($filtered['username']))
        {
            $this->messageService->add('error', 'Please enter a username');

            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        // Retrieve user
        //
        $user = $this->userRepository->getUserByUsername($filtered['username']);

        if ($user !== null)
        {
            $this->userPasswordService->sendPasswordResetMail($user);
        }

        // Redirect to main sceen
        //
        $baseUrl = $this->configService->get('base_url');
        $loginPage = $this->configService->get('actions.login.location');
        $message = $this->messageService->getForUrl('success', 'Reset link mailed to registered email account.');

        return $this->responseEmitter->redirect("{$baseUrl}{$loginPage}?{$message}");
    }
}
