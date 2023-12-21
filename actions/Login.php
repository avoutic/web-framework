<?php

namespace WebFramework\Actions;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Core\CaptchaService;
use WebFramework\Core\ConfigService;
use WebFramework\Core\MessageService;
use WebFramework\Core\RenderService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Entity\User;
use WebFramework\Exception\CaptchaRequiredException;
use WebFramework\Exception\InvalidCaptchaException;
use WebFramework\Exception\InvalidPasswordException;
use WebFramework\Exception\UserVerificationRequiredException;
use WebFramework\Exception\ValidationException;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\LoginService;
use WebFramework\Security\UserVerificationService;
use WebFramework\Validation\EmailValidator;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\PasswordValidator;
use WebFramework\Validation\UsernameValidator;

class Login
{
    public function __construct(
        protected Container $container,
        protected AuthenticationService $authenticationService,
        protected CaptchaService $captchaService,
        protected ConfigService $configService,
        protected InputValidationService $inputValidationService,
        protected LoginService $loginService,
        protected MessageService $messageService,
        protected RenderService $renderer,
        protected ResponseEmitter $responseEmitter,
        protected UserVerificationService $userVerificationService,
    ) {
        $this->init();
    }

    public function init(): void
    {
    }

    protected function customValueCheck(User $user): bool
    {
        return true;
    }

    protected function getTemplateName(): string
    {
        return 'Login.latte';
    }

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        $uniqueIdentifier = $this->configService->get('authenticator.unique_identifier');

        // Check if already logged in and redirect immediately
        //
        if ($this->authenticationService->isAuthenticated())
        {
            return $this->successRedirect($request, 'info', 'login.already_authenticated');
        }

        $params = [
            'returnPage' => $this->getReturnPage($request),
            'returnQuery' => $this->getReturnQuery($request),
            'username' => $request->getParam('username', ''),
            'recaptchaNeeded' => false,
        ];

        // Check if this is a login attempt
        //
        if (!$request->getAttribute('passed_csrf'))
        {
            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        try
        {
            $validator = ($uniqueIdentifier === 'email') ? new EmailValidator() : new UsernameValidator();

            // Validate input
            //
            $filtered = $this->inputValidationService->validate(
                [
                    'username' => $validator->required(),
                    'password' => new PasswordValidator(),
                ],
                $request->getParams(),
            );

            $validCaptcha = $this->captchaService->hasValidCaptcha($request);

            $user = $this->loginService->validate($request, $filtered['username'], $filtered['password'], $validCaptcha);

            if ($this->customValueCheck($user))
            {
                // Authenticate user
                //
                $this->loginService->authenticate($user, $filtered['password']);

                return $this->successRedirect($request, 'success', 'login.success');
            }
        }
        catch (CaptchaRequiredException $e)
        {
            $this->messageService->add('error', 'login.captcha_required');

            $params['recaptchaNeeded'] = true;
            $params['recaptchaSiteKey'] = $this->configService->get('security.recaptcha.site_key');
        }
        catch (InvalidCaptchaException $e)
        {
            $this->messageService->add('error', 'login.captcha_incorrect');

            $params['recaptchaNeeded'] = true;
            $params['recaptchaSiteKey'] = $this->configService->get('security.recaptcha.site_key');
        }
        catch (InvalidPasswordException $e)
        {
            $this->messageService->add('error', 'login.username_mismatch');
        }
        catch (UserVerificationRequiredException $e)
        {
            $this->userVerificationService->sendVerifyMail($e->getUser());

            return $this->responseEmitter->buildRedirect(
                $this->configService->get('actions.send_verify.after_verify_page'),
                [],
                'success',
                'verify.mail_sent',
            );
        }
        catch (ValidationException $e)
        {
            $this->messageService->addErrors($e->getErrors());
        }

        return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
    }

    protected function getReturnPage(Request $request): string
    {
        $returnPage = $request->getParam('return_page', '');

        if (!strlen($returnPage) || substr($returnPage, 0, 2) == '//'
            || !preg_match('/^'.FORMAT_RETURN_PAGE.'$/', $returnPage))
        {
            $returnPage = $this->configService->get('actions.login.default_return_page');
        }

        if (substr($returnPage, 0, 1) != '/')
        {
            $returnPage = '/'.$returnPage;
        }

        return $returnPage;
    }

    /**
     * @return array<mixed>
     */
    protected function getReturnQuery(Request $request): array
    {
        $returnQuery = [];
        $returnQueryStr = $request->getParam('return_query', '');
        parse_str($returnQueryStr, $returnQuery);

        return $returnQuery;
    }

    protected function successRedirect(Request $request, string $messageType, string $message): ResponseInterface
    {
        return $this->responseEmitter->buildQueryRedirect(
            $this->getReturnPage($request),
            [],
            $this->getReturnQuery($request),
            'success',
            'login.success',
        );
    }
}
