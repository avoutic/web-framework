<?php

namespace WebFramework\Actions;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
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
        return 'register_account.latte';
    }

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): Response
    {
        $baseUrl = $this->configService->get('base_url');

        // Check if already logged in
        //
        if ($this->authenticationService->isAuthenticated())
        {
            // Redirect to default page
            //
            $returnPage = $this->configService->get('actions.login.default_return_page');
            $message = $this->messageService->getForUrl('info', 'Already logged in');

            return $this->responseEmitter->redirect("{$baseUrl}{$returnPage}?{$message}");
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
                'g-recaptcha-response' => '.*',
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

        // Check if required values are present
        //
        if ($uniqueIdentifier == 'username' && !strlen($filtered['username']))
        {
            $errors = true;
            $this->messageService->add('error', 'Please enter a correct username', 'Usernames can contain letters, digits and underscores.');
        }

        if (!strlen($filtered['password']))
        {
            $errors = true;
            $this->messageService->add('error', 'Please enter a password', 'Passwords can contain any printable character.');
        }

        if (!strlen($filtered['password2']))
        {
            $errors = true;
            $this->messageService->add('error', 'Please enter the password verification', 'Password verification should match password.');
        }

        if (strlen($filtered['password']) && strlen($filtered['password2'])
            && $filtered['password'] !== $filtered['password2'])
        {
            $errors = true;
            $this->messageService->add('error', 'Passwords don\'t match', 'Password and password verification should be the same.');
        }

        if (strlen($filtered['password']) < 8)
        {
            $errors = true;
            $this->messageService->add('error', 'Password is too weak', 'Use at least 8 characters.');
        }

        if (!strlen($filtered['email']))
        {
            $errors = true;
            $this->messageService->add('error', 'Please enter a correct e-mail address', 'E-mail addresses can contain letters, digits, hyphens, underscores, dots and at\'s.');
        }

        if ($filtered['accept_terms'] != 1)
        {
            $errors = true;
            $this->messageService->add('error', 'Please accept our Terms', 'To register for our site you need to accept our Privacy Policy and our Terms of Service.');
        }

        if ($this->customValueCheck($request) !== true)
        {
            $errors = true;
        }

        $recaptchaResponse = $filtered['g-recaptcha-response'];

        if (!strlen($recaptchaResponse))
        {
            $errors = true;
            $this->messageService->add('error', 'CAPTCHA required', 'To prevent bots registering account en masse, filling in a CAPTCHA is required!');
        }
        else
        {
            $recaptcha = $this->recaptchaFactory->getRecaptcha();
            $result = $recaptcha->verifyResponse($recaptchaResponse);

            if ($result != true)
            {
                $this->messageService->add('error', 'The CAPTCHA code entered was incorrect.');
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
                $this->messageService->add('error', 'E-mail already registered.', 'An account has already been registered with this e-mail address. Forgot your password?');
            }
            else
            {
                $this->messageService->add('error', 'Username already exists.', 'This username has already been taken. Please enter another username.');
            }

            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        // Add account
        //
        $user = $this->userService->createUser($username, $filtered['password'], $filtered['email'], time());

        $this->customFinalizeCreate($request, $user);

        return $this->postCreateActions($request, $user);
    }

    protected function postCreateActions(Request $request, User $user): Response
    {
        $verifyParams = $this->getAfterVerifyData($request);
        $this->userEmailService->sendVerifyMail($user, $verifyParams);

        // Redirect to verification request screen
        //
        $baseUrl = $this->configService->get('base_url');
        $afterVerifyPage = $this->configService->get('actions.send_verify.after_verify_page');
        $message = $this->messageService->getForUrl('success', 'Verification mail sent', 'Verification mail is sent (if not already verified). Please check your mailbox and follow the instructions.');

        return $this->responseEmitter->redirect("{$baseUrl}{$afterVerifyPage}?{$message}");
    }
}
