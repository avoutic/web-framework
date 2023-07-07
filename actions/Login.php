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
use WebFramework\Core\UserCodeService;
use WebFramework\Core\UserEmailService;
use WebFramework\Core\UserPasswordService;
use WebFramework\Core\ValidatorService;
use WebFramework\Entity\User;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\BlacklistService;

class Login
{
    public function __construct(
        protected Container $container,
        protected AuthenticationService $authenticationService,
        protected BlacklistService $blacklistService,
        protected ConfigService $configService,
        protected MessageService $messageService,
        protected RecaptchaFactory $recaptchaFactory,
        protected RenderService $renderer,
        protected ResponseEmitter $responseEmitter,
        protected UserCodeService $userCodeService,
        protected UserEmailService $userEmailService,
        protected UserPasswordService $userPasswordService,
        protected UserRepository $userRepository,
        protected ValidatorService $validatorService,
    ) {
    }

    protected function customValueCheck(User $user): bool
    {
        return true;
    }

    protected function getTemplateName(): string
    {
        return 'login.latte';
    }

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): Response
    {
        $uniqueIdentifier = $this->configService->get('authenticator.unique_identifier');
        $bruteforceProtection = $this->configService->get('actions.login.bruteforce_protection');

        $recaptchaSiteKey = '';
        $recaptchaSecretKey = '';

        if ($bruteforceProtection)
        {
            $recaptchaSiteKey = $this->configService->get('security.recaptcha.site_key');
            $recaptchaSecretKey = $this->configService->get('security.recaptcha.secret_key');
        }

        $baseUrl = $this->configService->get('base_url');

        ['raw' => $raw, 'filtered' => $filtered] = $this->validatorService->getParams($request, [
            'return_page' => FORMAT_RETURN_PAGE,
            'return_query' => FORMAT_RETURN_QUERY,
            'username' => ($uniqueIdentifier === 'email') ? FORMAT_EMAIL : FORMAT_USERNAME,
            'password' => FORMAT_PASSWORD,
            'g-recaptcha-response' => '.*',
        ]);

        $returnPage = $filtered['return_page'];
        $returnQuery = $filtered['return_query'].(strlen($filtered['return_query']) ? '&' : '');

        if (!strlen($returnPage) || substr($returnPage, 0, 2) == '//')
        {
            $returnPage = $this->configService->get('actions.login.default_return_page');
        }

        if (substr($returnPage, 0, 1) != '/')
        {
            $returnPage = '/'.$returnPage;
        }

        $params = [
            'core' => [
                'title' => 'Login',
            ],
            'return_page' => $returnPage,
            'return_query' => $filtered['return_query'],
            'username' => $raw['username'],
            'recaptcha_needed' => false,
            'recaptcha_site_key' => $recaptchaSiteKey,
        ];

        // Check if already logged in and redirect immediately
        //
        if ($this->authenticationService->isAuthenticated())
        {
            $message = $this->messageService->getForUrl('info', 'Already logged in');

            return $this->responseEmitter->redirect("{$baseUrl}{$returnPage}?{$returnQuery}{$message}");
        }

        // Check if this is a login attempt
        //
        if (!$request->getAttribute('passed_csrf'))
        {
            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        $errors = false;

        // Check if username and password are present
        //
        if (!strlen($filtered['username']))
        {
            $errors = true;

            if ($uniqueIdentifier == 'email')
            {
                $this->messageService->add('error', 'Please enter a valid email.');
            }
            else
            {
                $this->messageService->add('error', 'Please enter a valid username.');
            }
        }

        if (!strlen($filtered['password']))
        {
            $errors = true;
            $this->messageService->add('error', 'Please enter your password.');
        }

        if ($errors)
        {
            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        // Log in user
        //
        $user = $this->userRepository->getUserByUsername($filtered['username']);
        if ($user === null)
        {
            if ($uniqueIdentifier == 'email')
            {
                $this->messageService->add('error', 'E-mail and password do not match.', 'Please check if you entered the e-mail and/or password correctly.');
            }
            else
            {
                $this->messageService->add('error', 'Username and password do not match.', 'Please check if you entered the username and/or password correctly.');
            }

            $this->blacklistService->addEntry($request->getAttribute('ip'), null, 'unknown-username');

            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        if ($user->getFailedLogin() > 5 && $bruteforceProtection)
        {
            $recaptchaResponse = $filtered['g-recaptcha-response'];
            $params['recaptcha_needed'] = true;

            if (!strlen($recaptchaResponse))
            {
                $this->messageService->add('error', 'CAPTCHA required', 'Due to possible brute force attacks on this username, filling in a CAPTCHA is required for checking the password!');

                return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
            }

            $recaptcha = $this->recaptchaFactory->getRecaptcha();
            $result = $recaptcha->verifyResponse($recaptchaResponse);

            if ($result != true)
            {
                $this->messageService->add('error', 'The CAPTCHA code entered was incorrect.');

                return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
            }
        }

        if (!$this->userPasswordService->checkPassword($user, $filtered['password']))
        {
            $this->messageService->add('error', 'Username and password do not match.', 'Please check if you entered the username and/or password correctly.');
            $this->blacklistService->addEntry($request->getAttribute('ip'), null, 'wrong-password');

            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        if ($this->customValueCheck($user) !== true)
        {
            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        // Check if verified
        //
        if (!$user->isVerified())
        {
            $code = $this->userCodeService->generate($user, 'send_verify');

            $sendVerifyPage = $this->configService->get('actions.login.send_verify_page');
            $message = $this->messageService->getForUrl('error', 'Account not yet verified.', 'Account is not yet verified. Please check your mailbox for the verification e-mail and go to the presented link.');

            return $this->responseEmitter->redirect("{$baseUrl}{$sendVerifyPage}?code={$code}&{$message}");
        }

        // Log in user
        //
        $this->authenticationService->authenticate($user);
        $message = $this->messageService->getForUrl('success', 'Login successful.');

        return $this->responseEmitter->redirect("{$baseUrl}{$returnPage}?{$returnQuery}{$message}");
    }
}
