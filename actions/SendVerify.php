<?php

namespace WebFramework\Actions;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Core\MessageService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Core\UserCodeService;
use WebFramework\Core\UserEmailService;
use WebFramework\Core\ValidatorService;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Repository\UserRepository;

class SendVerify
{
    public function __construct(
        protected Container $container,
        protected ConfigService $configService,
        protected MessageService $messageService,
        protected ResponseEmitter $responseEmitter,
        protected UserCodeService $userCodeService,
        protected UserEmailService $userEmailService,
        protected UserRepository $userRepository,
        protected ValidatorService $validatorService,
    ) {
    }

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): Response
    {
        $filtered = $this->validatorService->getFilteredParams($request, [
            'code' => '.*',
        ]);

        $baseUrl = $this->configService->get('base_url');

        try
        {
            ['user_id' => $codeUserId, 'params' => $verifyParams] = $this->userCodeService->verify(
                $filtered['code'],
                validity: 24 * 60 * 60,
                action: 'send_verify',
            );
        }
        catch (CodeVerificationException $e)
        {
            $loginPage = $this->configService->get('actions.login.location');
            $message = $this->messageService->getForUrl('error', 'Verification link expired', 'Please login again to request a new one.');

            return $this->responseEmitter->redirect("{$baseUrl}{$loginPage}?{$message}");
        }

        // Check user status
        //
        $user = $this->userRepository->getObjectById($codeUserId);
        if ($user !== null && !$user->isVerified())
        {
            $this->userEmailService->sendVerifyMail($user, $verifyParams);
        }

        // Redirect to main sceen
        //
        $afterVerifyPage = $this->configService->get('actions.send_verify.after_verify_page');
        $message = $this->messageService->getForUrl('success', 'Verification mail sent', 'Verification mail is sent (if not already verified). Please check your mailbox and follow the instructions.');

        return $this->responseEmitter->redirect("{$baseUrl}{$afterVerifyPage}?{$message}");
    }
}
