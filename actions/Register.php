<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
use WebFramework\Exception\ValidationException;
use WebFramework\Exception\WeakPasswordException;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\RegisterService;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\Validator\CustomBoolValidator;
use WebFramework\Validation\Validator\EmailValidator;
use WebFramework\Validation\Validator\PasswordValidator;
use WebFramework\Validation\Validator\UsernameValidator;

/**
 * Class Register.
 *
 * This action handles the user registration process.
 */
class Register
{
    /**
     * Register constructor.
     *
     * @param Container              $container              The dependency injection container
     * @param AuthenticationService  $authenticationService  The authentication service
     * @param CaptchaService         $captchaService         The captcha service
     * @param ConfigService          $configService          The configuration service
     * @param InputValidationService $inputValidationService The input validation service
     * @param MessageService         $messageService         The message service
     * @param RegisterService        $registerService        The register service
     * @param RenderService          $renderer               The render service
     * @param ResponseEmitter        $responseEmitter        The response emitter
     */
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

    /**
     * Initialize the action.
     */
    public function init(): void {}

    /**
     * Get additional data to be passed after verification.
     *
     * @param Request $request The current request
     *
     * @return array<mixed> Additional data
     */
    protected function getAfterVerifyData(Request $request): array
    {
        return [];
    }

    /**
     * Prepare custom page content.
     *
     * @param Request $request The current request
     *
     * @return array<string, mixed> Custom page content
     */
    protected function customPreparePageContent(Request $request): array
    {
        return [];
    }

    /**
     * Perform custom value checks.
     *
     * @param Request $request The current request
     *
     * @return bool True if the checks pass, false otherwise
     */
    protected function customValueCheck(Request $request): bool
    {
        return true;
    }

    /**
     * Perform custom finalization after user creation.
     *
     * @param Request $request The current request
     * @param User    $user    The newly created user
     */
    protected function customFinalizeCreate(Request $request, User $user): void {}

    /**
     * Get the template name for rendering.
     *
     * @return string The template name
     */
    protected function getTemplateName(): string
    {
        return 'Register.latte';
    }

    /**
     * Handle the registration request.
     *
     * @param Request               $request   The current request
     * @param Response              $response  The response object
     * @param array<string, string> $routeArgs Route arguments
     *
     * @return ResponseInterface The response
     *
     * @throws InvalidCaptchaException      If the provided captcha is invalid
     * @throws PasswordMismatchException    If the provided passwords don't match
     * @throws UsernameUnavailableException If the chosen username is already taken
     * @throws WeakPasswordException        If the provided password is too weak
     *
     * @uses config authenticator.unique_identifier
     * @uses config security.recaptcha.site_key
     * @uses config actions.login.default_return_page
     * @uses config actions.send_verify.after_verify_page
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

                $this->customFinalizeCreate($request, $user);

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
            $message = ($uniqueIdentifier == 'email') ? 'register.email_exists' : 'register.username_exists';

            $this->messageService->add('error', $message);
        }
        catch (ValidationException $e)
        {
            $this->messageService->addErrors($e->getErrors());
            $all = $this->inputValidationService->getAll();

            $params = array_merge($params, $all);
        }

        return $this->renderer->render($request, $response, $this->getTemplateName(), $params);
    }
}
