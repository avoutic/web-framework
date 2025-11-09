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
use WebFramework\Exception\DuplicateEmailException;
use WebFramework\Exception\ValidationException;
use WebFramework\Http\ResponseEmitter;
use WebFramework\Presentation\MessageService;
use WebFramework\Presentation\RenderService;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\ChangeEmailService;
use WebFramework\Security\Extension\ChangeEmailExtensionInterface;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\Validator\EmailValidator;

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
     * @param AuthenticationService         $authenticationService  The authentication service
     * @param ChangeEmailExtensionInterface $changeEmailExtension   The change email extension
     * @param ChangeEmailService            $changeEmailService     The change email service
     * @param ConfigService                 $configService          The configuration service
     * @param InputValidationService        $inputValidationService The input validation service
     * @param MessageService                $messageService         The message service
     * @param RenderService                 $renderer               The render service
     * @param ResponseEmitter               $responseEmitter        The response emitter
     */
    public function __construct(
        private AuthenticationService $authenticationService,
        private ChangeEmailExtensionInterface $changeEmailExtension,
        private ChangeEmailService $changeEmailService,
        private ConfigService $configService,
        private InputValidationService $inputValidationService,
        private MessageService $messageService,
        private RenderService $renderer,
        private ResponseEmitter $responseEmitter,
        private string $templateName,
    ) {}

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

        $customParams = $this->changeEmailExtension->getCustomParams($request);
        $params = array_replace_recursive($params, $customParams);

        // Check if this is a true attempt
        //
        if (!$request->getAttribute('passed_csrf'))
        {
            return $this->renderer->render($request, $response, $this->templateName, $params);
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

            if ($this->changeEmailExtension->customValueCheck($request, $user))
            {
                // Send verification mail
                //
                $guid = $this->changeEmailService->sendChangeEmailVerify($user, $filtered['email']);

                return $this->responseEmitter->buildQueryRedirect(
                    $this->configService->get('actions.verify.location'),
                    [],
                    [
                        'guid' => $guid,
                    ],
                    'success',
                    'change_email.verification_sent',
                );
            }
        }
        catch (ValidationException $e)
        {
            $this->messageService->addErrors($e->getErrors());
        }
        catch (DuplicateEmailException $e)
        {
            $this->messageService->add('error', 'change_email.duplicate');
        }

        return $this->renderer->render($request, $response, $this->templateName, $params);
    }
}
