<?php

namespace WebFramework\Actions;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Core\MessageService;
use WebFramework\Core\RecaptchaFactory;
use WebFramework\Core\RenderService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Core\UserEmailService;
use WebFramework\Core\UserService;
use WebFramework\Core\ValidatorService;
use WebFramework\Entity\User;
use WebFramework\Security\AuthenticationService;

class Register
{
    public function __construct(
        protected Container $container,
        protected AuthenticationService $authenticationService,
        protected ConfigService $configService,
        protected MessageService $messageService,
        protected RecaptchaFactory $recaptchaFactory,
        protected RenderService $renderer,
        protected ResponseEmitter $responseEmitter,
        protected UserEmailService $userEmailService,
        protected UserService $userService,
        protected ValidatorService $validatorService,
    ) {
    }

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

    protected function customFinalizeCreate(Request $request, User $user): void
    {
    }

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
        $recaptchaSecretKey = $this->configService->get('security.recaptcha.secret_key');

        ['raw' => $raw, 'filtered' => $filtered] = $this->validatorService->getParams(
            $request,
            [
                'username' => FORMAT_USERNAME,
                'password' => FORMAT_PASSWORD,
                'password2' => FORMAT_PASSWORD,
                'email' => FORMAT_EMAIL,
                'accept_terms' => '0|1',
            ]
        );

        $username = ($uniqueIdentifier == 'email') ? $filtered['email'] : $filtered['username'];

        $params = [
            'core' => [
                'title' => 'Register new account',
            ],
            'username' => $raw['username'],
            'password' => $filtered['password'],
            'password2' => $filtered['password2'],
            'email' => $raw['email'],
            'accept_terms' => $filtered['accept_terms'],
            'recaptcha_site_key' => $recaptchaSiteKey,
        ];

        $customParams = $this->customPreparePageContent($request);
        $params = array_replace_recursive($params, $customParams);

        // Check if this is a true attempt
        //
        if (!$request->getAttribute('passed_csrf'))
        {
            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        $errors = false;

        if (strlen($filtered['password']) && strlen($filtered['password2'])
            && $filtered['password'] !== $filtered['password2'])
        {
            $errors = true;
            $this->messageService->add('error', 'register.password_mismatch');
        }

        if (strlen($filtered['password']) < 8)
        {
            $errors = true;
            $this->messageService->add('error', 'register.weak_password');
        }

        if ($filtered['accept_terms'] != 1)
        {
            $errors = true;
            $this->messageService->add('error', 'register.accept_terms');
        }

        if ($this->customValueCheck($request) !== true)
        {
            $errors = true;
        }

        $recaptchaResponse = $request->getParam('g-recaptcha-response', '');

        if (!strlen($recaptchaResponse))
        {
            $errors = true;
            $this->messageService->add('error', 'register.captcha_required');
        }
        else
        {
            $recaptcha = $this->recaptchaFactory->getRecaptcha();
            $result = $recaptcha->verifyResponse($recaptchaResponse);

            if ($result != true)
            {
                $this->messageService->add('error', 'register.captcha_incorrect');
                $errors = true;
            }
        }

        if ($errors)
        {
            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        // Check if identifier already exists
        //
        if (!$this->userService->isUsernameAvailable($username))
        {
            if ($uniqueIdentifier == 'email')
            {
                $this->messageService->add('error', 'register.email_exists');
            }
            else
            {
                $this->messageService->add('error', 'register.username_exists');
            }

            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        // Add account
        //
        $user = $this->userService->createUser($username, $filtered['password'], $filtered['email'], time());

        $this->customFinalizeCreate($request, $user);

        return $this->postCreateActions($request, $user);
    }

    protected function postCreateActions(Request $request, User $user): ResponseInterface
    {
        $verifyParams = $this->getAfterVerifyData($request);
        $this->userEmailService->sendVerifyMail($user, $verifyParams);

        // Redirect to verification request screen
        //
        return $this->responseEmitter->buildRedirect(
            $this->configService->get('actions.send_verify.after_verify_page'),
            [],
            'success',
            'register.verification_sent',
        );
    }
}
