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
use WebFramework\Exception\InvalidCaptchaException;
use WebFramework\Exception\PasswordMismatchException;
use WebFramework\Exception\UsernameUnavailableException;
use WebFramework\Exception\WeakPasswordException;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\RegisterService;
use WebFramework\Validation\CustomBoolValidator;
use WebFramework\Validation\EmailValidator;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\PasswordValidator;
use WebFramework\Validation\UsernameValidator;

class Register
{
    public function __construct(
        protected Container $container,
        protected AuthenticationService $authenticationService,
        protected CaptchaService $captchaService,
        protected ConfigService $configService,
        protected InputValidationService $inputValidationService,
        protected MessageService $messageService,
        protected RegisterService $registerService,
        protected RenderService $renderer,
        protected ResponseEmitter $responseEmitter,
    ) {
        $this->init();
    }

    public function init(): void {}

    /**
     * @return array<mixed>
     */
    protected function getAfterVerifyData(Request $request): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function customPreparePageContent(Request $request): array
    {
        return [];
    }

    protected function customValueCheck(Request $request): bool
    {
        return true;
    }

    protected function customFinalizeCreate(Request $request, User $user): void {}

    protected function getTemplateName(): string
    {
        return 'Register.latte';
    }

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        // Check if already logged in
        //
        if ($this->authenticationService->isAuthenticated())
        {
            // Redirect to default page
            //
            $returnPage = $this->configService->get('actions.login.default_return_page');

            return $this->responseEmitter->buildRedirect(
                $returnPage,
                [],
                'info',
                'login.already_authenticated',
            );
        }

        $uniqueIdentifier = $this->configService->get('authenticator.unique_identifier');
        $recaptchaSiteKey = $this->configService->get('security.recaptcha.site_key');

        $params = [
            'recaptcha_site_key' => $recaptchaSiteKey,
            'username' => $request->getParam('username', ''),
            'password' => $request->getParam('password', ''),
            'password2' => $request->getParam('password2', ''),
            'email' => $request->getParam('email', ''),
            'accept_terms' => $request->getParam('accept_terms', false),
        ];

        $customParams = $this->customPreparePageContent($request);
        $params = array_replace_recursive($params, $customParams);

        // Check if this is a true attempt
        //
        if (!$request->getAttribute('passed_csrf'))
        {
            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        try
        {
            $usernameValidator = new UsernameValidator();
            if ($uniqueIdentifier == 'username')
            {
                $usernameValidator->required();
            }

            // Validate input
            //
            $filtered = $this->inputValidationService->validate(
                [
                    'username' => $usernameValidator,
                    'email' => (new EmailValidator())->required(),
                    'password' => new PasswordValidator(),
                    'password2' => new PasswordValidator('password verification'),
                    'accept_terms' => (new CustomBoolValidator('term acceptance'))->required(),
                ],
                $request->getParams(),
            );

            $validCaptcha = $this->captchaService->hasValidCaptcha($request);

            $username = ($uniqueIdentifier == 'email') ? $filtered['email'] : $filtered['username'];
            $this->registerService->validate($username, $filtered['email'], $filtered['password'], $filtered['password2'], $validCaptcha);

            if ($this->customValueCheck($request))
            {
                $afterVerifyParams = $this->getAfterVerifyData($request);

                $user = $this->registerService->register($username, $filtered['email'], $filtered['password'], $afterVerifyParams);

                return $this->responseEmitter->buildRedirect(
                    $this->configService->get('actions.send_verify.after_verify_page'),
                    [],
                    'success',
                    'register.verification_sent',
                );
            }
        }
        catch (PasswordMismatchException $e)
        {
            $this->messageService->add('error', 'register.password_mismatch');
        }
        catch (WeakPasswordException $e)
        {
            $this->messageService->add('error', 'register.weak_password');
        }
        catch (InvalidCaptchaException $e)
        {
            $this->messageService->add('error', 'register.captcha_incorrect');
        }
        catch (UsernameUnavailableException $e)
        {
            $message = ($uniqueIdentifier == 'email') ? 'register.mail_exists' : 'register.username_exists';

            $this->messageService->add('error', $message);
        }

        return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
    }
}
