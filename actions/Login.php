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

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Config\ConfigService;
use WebFramework\Entity\User;
use WebFramework\Exception\InvalidPasswordException;
use WebFramework\Exception\UserVerificationRequiredException;
use WebFramework\Exception\ValidationException;
use WebFramework\Http\ResponseEmitter;
use WebFramework\Presentation\MessageService;
use WebFramework\Presentation\RenderService;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\Extension\LoginExtensionInterface;
use WebFramework\Security\LoginService;
use WebFramework\Security\UserVerificationService;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\Validator\EmailValidator;
use WebFramework\Validation\Validator\PasswordValidator;
use WebFramework\Validation\Validator\UsernameValidator;

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
     * @param AuthenticationService   $authenticationService   The authentication service
     * @param ConfigService           $configService           The configuration service
     * @param InputValidationService  $inputValidationService  The input validation service
     * @param LoginExtensionInterface $loginExtension          The login extension
     * @param LoginService            $loginService            The login service
     * @param MessageService          $messageService          The message service
     * @param RenderService           $renderer                The render service
     * @param ResponseEmitter         $responseEmitter         The response emitter
     * @param UserVerificationService $userVerificationService The user verification service
     */
    public function __construct(
        private AuthenticationService $authenticationService,
        private ConfigService $configService,
        private InputValidationService $inputValidationService,
        private LoginExtensionInterface $loginExtension,
        private LoginService $loginService,
        private MessageService $messageService,
        private RenderService $renderer,
        private ResponseEmitter $responseEmitter,
        private UserVerificationService $userVerificationService,
        private string $templateName,
    ) {}

    /**
     * Handle the login request.
     *
     * @param Request               $request   The current request
     * @param Response              $response  The response object
     * @param array<string, string> $routeArgs Route arguments
     *
     * @return ResponseInterface The response
     *
     * @uses config authenticator.unique_identifier
     * @uses config actions.login.default_return_page
     * @uses config actions.verify.location
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        $uniqueIdentifier = $this->configService->get('authenticator.unique_identifier');

        // Check if already logged in and redirect immediately
        //
        if ($this->authenticationService->isAuthenticated())
        {
            return $this->redirect($request, 'info', 'login.already_authenticated');
        }

        $params = [
            'returnPage' => $this->getReturnPage($request),
            'returnQuery' => $this->getReturnQuery($request),
            'username' => $request->getParam('username', ''),
        ];

        $customParams = $this->loginExtension->getCustomParams($request);
        $params = array_replace_recursive($params, $customParams);

        // Check if this is a login attempt
        //
        if (!$request->getAttribute('passed_csrf'))
        {
            return $this->renderer->render($request, $response, $this->templateName, $params);
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

            if ($this->loginExtension->preValidate($request))
            {
                $user = $this->loginService->validate($request, $filtered['username'], $filtered['password']);

                if ($this->loginExtension->customValueCheck($request, $user))
                {
                    // Authenticate user
                    //
                    $this->loginService->authenticate($request, $user, $filtered['password']);

                    return $this->redirect($request, 'success', 'login.success');
                }
            }
        }
        catch (InvalidPasswordException $e)
        {
            $this->messageService->add('error', 'login.username_mismatch');
        }
        catch (UserVerificationRequiredException $e)
        {
            $afterVerifyData = [
                'return_page' => $this->getReturnPage($request),
                'return_query' => $this->getReturnQuery($request),
            ];

            $guid = $this->userVerificationService->sendVerifyMail($e->getUser(), 'login', $afterVerifyData);

            return $this->responseEmitter->buildQueryRedirect(
                $this->configService->get('actions.verify.location'),
                [],
                [
                    'guid' => $guid,
                ],
                'success',
                'verify.code_sent',
            );
        }
        catch (ValidationException $e)
        {
            $this->messageService->addErrors($e->getErrors());
        }

        return $this->renderer->render($request, $response, $this->templateName, $params);
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
    private function getReturnPage(Request $request): string
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
    private function getReturnQuery(Request $request): array
    {
        $returnQuery = [];
        $returnQueryStr = $request->getParam('return_query', '');
        parse_str($returnQueryStr, $returnQuery);

        return $returnQuery;
    }

    /**
     * Build a redirect response.
     *
     * @param Request $request     The current request
     * @param string  $messageType The type of message to display
     * @param string  $message     The message to display
     *
     * @return ResponseInterface The redirect response
     */
    private function redirect(Request $request, string $messageType, string $message): ResponseInterface
    {
        return $this->responseEmitter->buildQueryRedirect(
            $this->getReturnPage($request),
            [],
            $this->getReturnQuery($request),
            $messageType,
            $message,
        );
    }
}
