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
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Config\ConfigService;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Exception\InvalidCodeException;
use WebFramework\Http\ResponseEmitter;
use WebFramework\Presentation\MessageService;
use WebFramework\Presentation\RenderService;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\UserCodeService;

/**
 * Class Verify.
 *
 * This action handles the user verification process.
 */
class Verify
{
    /**
     * Verify constructor.
     *
     * @param AuthenticationService $authenticationService The authentication service
     * @param ConfigService         $configService         The configuration service
     * @param MessageService        $messageService        The message service
     * @param ResponseEmitter       $responseEmitter       The response emitter
     * @param UserCodeService       $userCodeService       The user code service
     * @param array<string, string> $templateConfig        The template configuration array
     */
    public function __construct(
        private AuthenticationService $authenticationService,
        private ConfigService $configService,
        private MessageService $messageService,
        private RenderService $renderer,
        private ResponseEmitter $responseEmitter,
        private UserCodeService $userCodeService,
        private array $templateConfig,
    ) {}

    protected function getTemplateName(string $action): string
    {
        if (!isset($this->templateConfig[$action]))
        {
            throw new \RuntimeException('Invalid action');
        }

        return $this->templateConfig[$action];
    }

    /**
     * Handle the verification request.
     *
     * @param Request               $request   The current request
     * @param Response              $response  The response object
     * @param array<string, string> $routeArgs Route arguments
     *
     * @return ResponseInterface The response
     *
     * @throws CodeVerificationException If the verification code is invalid or expired
     *
     * @uses config actions.login.location
     * @uses config actions.login.after_verify_page
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        $guid = $request->getParam('guid');

        if (!$guid)
        {
            throw new HttpNotFoundException($request);
        }

        $action = $this->userCodeService->getActionByGuid($guid);
        if (!$action)
        {
            throw new HttpNotFoundException($request);
        }

        if ($action === 'change_email')
        {
            if (!$this->authenticationService->isAuthenticated())
            {
                throw new HttpUnauthorizedException($request);
            }
        }

        $code = $request->getParam('code', '');

        $params = [
            'guid' => $guid,
            'code' => $code,
        ];

        if (!$request->getAttribute('passed_csrf'))
        {
            return $this->renderer->render($request, $response, $this->getTemplateName($action), $params);
        }

        try
        {
            if (!strlen($code))
            {
                $this->messageService->add('error', 'verify.code_required');

                return $this->renderer->render($request, $response, $this->getTemplateName($action), $params);
            }

            $this->userCodeService->verifyCodeByGuid($guid, $action, $code);

            $redirectConfigValue = match ($action)
            {
                'login' => 'actions.login.after_verify',
                'register' => 'actions.register.after_verify',
                'reset_password' => 'actions.reset_password.after_verify',
                'change_email' => 'actions.change_email.after_verify',
                default => throw new \RuntimeException('Invalid action'),
            };

            $redirectUrl = $this->configService->get($redirectConfigValue);

            return $this->responseEmitter->buildQueryRedirect(
                $redirectUrl,
                [],
                [
                    'guid' => $guid,
                ],
            );
        }
        catch (InvalidCodeException $e)
        {
            $this->messageService->add('error', 'verify.invalid_code');

            return $this->renderer->render($request, $response, $this->getTemplateName($action), $params);
        }
        catch (CodeVerificationException $e)
        {
            return $this->responseEmitter->buildRedirect(
                $this->configService->get('actions.login.location'),
                [],
                'error',
                'verify.code_expired',
            );
        }
    }
}
