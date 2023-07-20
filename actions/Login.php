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
use WebFramework\Core\UserCodeService;
use WebFramework\Core\UserEmailService;
use WebFramework\Core\UserPasswordService;
use WebFramework\Entity\User;
use WebFramework\Exception\ValidationException;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\BlacklistService;
use WebFramework\Validation\EmailValidator;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\PasswordValidator;
use WebFramework\Validation\UsernameValidator;

class Login
{
    public function __construct(
        protected Container $container,
        protected AuthenticationService $authenticationService,
        protected BlacklistService $blacklistService,
        protected ConfigService $configService,
        protected InputValidationService $inputValidationService,
        protected MessageService $messageService,
        protected RecaptchaFactory $recaptchaFactory,
        protected RenderService $renderer,
        protected ResponseEmitter $responseEmitter,
        protected UserCodeService $userCodeService,
        protected UserEmailService $userEmailService,
        protected UserPasswordService $userPasswordService,
        protected UserRepository $userRepository,
    ) {
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
        $bruteforceProtection = $this->configService->get('actions.login.bruteforce_protection');

        $recaptchaSiteKey = '';
        $recaptchaSecretKey = '';

        if ($bruteforceProtection)
        {
            $recaptchaSiteKey = $this->configService->get('security.recaptcha.site_key');
            $recaptchaSecretKey = $this->configService->get('security.recaptcha.secret_key');
        }

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

        $returnQuery = [];
        $returnQueryStr = $request->getParam('return_query', '');
        parse_str($returnQueryStr, $returnQuery);

        // Check if already logged in and redirect immediately
        //
        if ($this->authenticationService->isAuthenticated())
        {
            return $this->responseEmitter->buildQueryRedirect(
                $returnPage,
                [],
                $returnQuery,
                'info',
                'login.already_authenticated',
            );
        }
        $params = [
            'return_page' => $returnPage,
            'return_query' => $returnQuery,
            'username' => $request->getParam('username', ''),
            'recaptcha_needed' => false,
            'recaptcha_site_key' => $recaptchaSiteKey,
        ];

        // Check if this is a login attempt
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
                    'username' => ($uniqueIdentifier === 'email') ? new EmailValidator() : new UsernameValidator(),
                    'password' => new PasswordValidator(),
                ],
                $request->getParams(),
            );
        }
        catch (ValidationException $e)
        {
            $this->messageService->addErrors($e->getErrors());

            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        // Log in user
        //
        $user = $this->userRepository->getUserByUsername($filtered['username']);
        if ($user === null)
        {
            $this->messageService->add('error', 'login.username_mismatch', 'login.username_mismatch_extra');

            $this->blacklistService->addEntry($request->getAttribute('ip'), null, 'unknown-username');

            return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
        }

        if ($user->getFailedLogin() > 5 && $bruteforceProtection)
        {
            $recaptchaResponse = $request->getParam('g-recaptcha-response', '');
            $params['recaptcha_needed'] = true;

            if (!strlen($recaptchaResponse))
            {
                $this->messageService->add('error', 'login.captcha_required', 'login.captcha_required_extra');

                return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
            }

            $recaptcha = $this->recaptchaFactory->getRecaptcha();
            $result = $recaptcha->verifyResponse($recaptchaResponse);

            if ($result != true)
            {
                $this->messageService->add('error', 'login.captcha_incorrect');

                return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
            }
        }

        if (!$this->userPasswordService->checkPassword($user, $filtered['password']))
        {
            $this->messageService->add('error', 'login.username_mismatch', 'login.username_mismatch_extra');

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

            return $this->responseEmitter->buildQueryRedirect(
                $this->configService->get('actions.login.send_verify_page'),
                [],
                ['code' => $code],
                'error',
                'login.unverified',
                'login.unverified_extra',
            );
        }

        // Log in user
        //
        $this->authenticationService->authenticate($user);

        return $this->responseEmitter->buildQueryRedirect(
            $returnPage,
            [],
            $returnQuery,
            'success',
            'login.success',
        );
    }
}
