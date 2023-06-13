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
use WebFramework\Core\UserEmailService;
use WebFramework\Core\ValidatorService;
use WebFramework\Exception\DuplicateEmailException;

class ChangeEmail
{
    public function __construct(
        protected Container $container,
        protected ConfigService $configService,
        protected MessageService $messageService,
        protected RenderService $renderer,
        protected ResponseEmitter $responseEmitter,
        protected UserEmailService $userEmailService,
        protected ValidatorService $validatorService,
    ) {
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

        ['raw' => $raw, 'filtered' => $filtered] = $this->validatorService->getParams($request, [
            'email' => FORMAT_EMAIL,
        ]);

        $params = [
            'core' => [
                'title' => 'Change email address',
            ],
            'email' => $raw['email'],
        ];

        // Check if this is a true attempt
        //
        if (!$request->getAttribute('passed_csrf'))
        {
            return $this->renderer->render($request, $response, 'change_email.latte', $params);
        }

        $errors = false;

        // Check if email address is present
        //
        if (!strlen($filtered['email']))
        {
            $errors = true;
            $this->messageService->add('error', 'Please enter a correct e-mail address.', 'E-mail addresses can contain letters, digits, hyphens, underscores, dots and at\'s.');
        }

        // Send verification mail
        //
        try
        {
            $this->userEmailService->sendChangeEmailVerify($user, $filtered['email']);
        }
        catch (DuplicateEmailException $e)
        {
            $errors = true;
            $this->messageService->add('error', 'E-mail address is already in use in another account.', 'The e-mail address is already in use and cannot be re-used in this account. Please choose another address.');
        }

        if ($errors)
        {
            return $this->renderer->render($request, $response, 'change_email.latte', $params);
        }

        // Redirect to verification request screen
        //
        $baseUrl = $this->configService->get('base_url');
        $returnPage = $this->configService->get('actions.change_email.return_page');

        $message = $this->messageService->getForUrl('success', 'Verification mail has been sent.', 'A verification mail has been sent. Please wait for the e-mail in your inbox and follow the instructions.');

        return $this->responseEmitter->redirect("{$baseUrl}{$returnPage}?{$message}");
    }
}
