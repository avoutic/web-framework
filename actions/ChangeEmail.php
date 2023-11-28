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
use WebFramework\Exception\DuplicateEmailException;
use WebFramework\Exception\ValidationException;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\ChangeEmailService;
use WebFramework\Validation\EmailValidator;
use WebFramework\Validation\InputValidationService;

class ChangeEmail
{
    public function __construct(
        protected Container $container,
        protected AuthenticationService $authenticationService,
        protected ConfigService $configService,
        protected InputValidationService $inputValidationService,
        protected MessageService $messageService,
        protected RenderService $renderer,
        protected ResponseEmitter $responseEmitter,
        protected ChangeEmailService $changeEmailService,
    ) {
        $this->init();
    }

    public function init(): void
    {
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
        return 'ChangeEmail.latte';
    }

    protected function getReturnPage(): string
    {
        return $this->configService->get('actions.change_email.return_page');
    }

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        $user = $this->authenticationService->getAuthenticatedUser();

        $params = [
            'email' => $request->getParam('email', ''),
        ];

        $customParams = $this->customParams($request);
        $params = array_replace_recursive($params, $customParams);

        // Check if this is a true attempt
        //
        if (!$request->getAttribute('passed_csrf'))
        {
            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        try
        {
            // Validate input
            //
            $filtered = $this->inputValidationService->validate(
                [
                    'email' => (new EmailValidator())->required(),
                ],
                $request->getParams(),
            );

            // Send verification mail
            //
            $this->changeEmailService->sendChangeEmailVerify($user, $filtered['email']);

            // Redirect to verification request screen
            //
            return $this->responseEmitter->buildRedirect(
                $this->getReturnPage(),
                [],
                'success',
                'change_email.verification_sent',
            );
        }
        catch (ValidationException $e)
        {
            $this->messageService->addErrors($e->getErrors());
        }
        catch (DuplicateEmailException $e)
        {
            $this->messageService->add('error', 'change_email.duplicate');
        }

        return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
    }
}
