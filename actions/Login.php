<?php

/**
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

/**
 * Class Login.
 *
 * This action handles the user login process.
 */
class Login
{
    /**
     * Login constructor.
     *
     * @param Container               $container               The dependency injection container
     * @param AuthenticationService   $authenticationService   The authentication service
     * @param CaptchaService          $captchaService          The captcha service
     * @param ConfigService           $configService           The configuration service
     * @param InputValidationService  $inputValidationService  The input validation service
     * @param LoginService            $loginService            The login service
     * @param MessageService          $messageService          The message service
     * @param RenderService           $renderer                The render service
     * @param ResponseEmitter         $responseEmitter         The response emitter
     * @param UserVerificationService $userVerificationService The user verification service
     */
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

    /**
     * Initialize the action.
     */
    public function init(): void {}

    /**
     * Perform custom value checks.
     *
     * @param User $user The user to check
     *
     * @return bool True if the checks pass, false otherwise
     */
    protected function customValueCheck(User $user): bool
    {
        return true;
    }

    /**
     * Get the template name for rendering.
     *
     * @return string The template name
     */
    protected function getTemplateName(): string
    {
        return 'Login.latte';
    }

    /**
     * Handle the login request.
     *
     * @param Request               $request   The current request
     * @param Response              $response  The response object
     * @param array<string, string> $routeArgs Route arguments
     *
     * @return ResponseInterface The response
     *
     * @throws ValidationException               If the input validation fails
     * @throws CaptchaRequiredException          If a captcha is required but not provided
     * @throws InvalidCaptchaException           If the provided captcha is invalid
     * @throws InvalidPasswordException          If the provided password is incorrect
     * @throws UserVerificationRequiredException If the user needs to verify their account
     *
     * @uses config authenticator.unique_identifier
     * @uses config actions.login.default_return_page
     * @uses config security.recaptcha.site_key
     * @uses config actions.send_verify.after_verify_page
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

    /**
     * Get the return page after successful login.
     *
     * @param Request $request The current request
     *
     * @return string The return page URL
     *
     * @uses config actions.login.default_return_page
     */
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
     * Get the return query parameters.
     *
     * @param Request $request The current request
     *
     * @return array<mixed> The return query parameters
     */
    protected function getReturnQuery(Request $request): array
    {
        $returnQuery = [];
        $returnQueryStr = $request->getParam('return_query', '');
        parse_str($returnQueryStr, $returnQuery);

        return $returnQuery;
    }

    /**
     * Build the success redirect response.
     *
     * @param Request $request     The current request
     * @param string  $messageType The type of message to display
     * @param string  $message     The message to display
     *
     * @return ResponseInterface The redirect response
     */
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
