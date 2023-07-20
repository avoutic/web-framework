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
use WebFramework\Core\ValidatorService;
use WebFramework\Exception\ValidationException;
use WebFramework\Repository\UserRepository;
use WebFramework\Validation\EmailValidator;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\UsernameValidator;

class ForgotPassword
{
    public function __construct(
        protected Container $container,
        protected ConfigService $configService,
        protected InputValidationService $inputValidationService,
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
                ['username' => $validator],
                $request->getParams(),
            );
        }
        catch (ValidationException $e)
        {
            $this->messageService->addErrors($e->getErrors());

            return $this->renderer->render($request, $response, $this->getTemplateName(), []);
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
        return $this->responseEmitter->buildRedirect(
            $this->configService->get('actions.login.location'),
            [],
            'success',
            'forgot_password.reset_link_mailed',
        );
    }
}
