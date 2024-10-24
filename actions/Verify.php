<?php

namespace WebFramework\Actions;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Security\UserVerificationService;

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
     * @param ConfigService           $configService           The configuration service
     * @param ResponseEmitter         $responseEmitter         The response emitter
     * @param UserVerificationService $userVerificationService The user verification service
     */
    public function __construct(
        protected ConfigService $configService,
        protected ResponseEmitter $responseEmitter,
        protected UserVerificationService $userVerificationService,
    ) {}

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
        $loginPage = $this->configService->get('actions.login.location');

        try
        {
            $this->userVerificationService->handleVerify($request->getParam('code', ''));

            $afterVerifyPage = $this->configService->get('actions.login.after_verify_page');

            return $this->responseEmitter->buildQueryRedirect(
                $loginPage,
                [],
                ['return_page' => $afterVerifyPage],
                'success',
                'verify.success',
            );
        }
        catch (CodeVerificationException $e)
        {
            return $this->responseEmitter->buildRedirect(
                $loginPage,
                [],
                'error',
                'verify.link_expired',
            );
        }
    }
}
