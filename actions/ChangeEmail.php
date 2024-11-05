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

/**
 * Class ChangeEmail.
 *
 * This action handles the process of changing a user's email address.
 */
class ChangeEmail
{
    /**
     * ChangeEmail constructor.
     *
     * @param Container              $container              The dependency injection container
     * @param AuthenticationService  $authenticationService  The authentication service
     * @param ConfigService          $configService          The configuration service
     * @param InputValidationService $inputValidationService The input validation service
     * @param MessageService         $messageService         The message service
     * @param RenderService          $renderer               The render service
     * @param ResponseEmitter        $responseEmitter        The response emitter
     * @param ChangeEmailService     $changeEmailService     The change email service
     */
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

    /**
     * Initialize the action.
     */
    public function init(): void {}

    /**
     * Get custom parameters for the action.
     *
     * @param Request $request The current request
     *
     * @return array<string, mixed> Custom parameters
     */
    protected function customParams(Request $request): array
    {
        return [];
    }

    /**
     * Get the template name for rendering.
     *
     * @return string The template name
     */
    protected function getTemplateName(): string
    {
        return 'ChangeEmail.latte';
    }

    /**
     * Get the return page after successful email change.
     *
     * @return string The return page URL
     *
     * @uses config actions.change_email.return_page
     */
    protected function getReturnPage(): string
    {
        return $this->configService->get('actions.change_email.return_page');
    }

    /**
     * Handle the change email request.
     *
     * @param Request               $request   The current request
     * @param Response              $response  The response object
     * @param array<string, string> $routeArgs Route arguments
     *
     * @return ResponseInterface The response
     *
     * @throws ValidationException     If the input validation fails
     * @throws DuplicateEmailException If the new email is already in use
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
