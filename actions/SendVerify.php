<?php

namespace WebFramework\Actions;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
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
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        try
        {
            ['user_id' => $codeUserId, 'params' => $verifyParams] = $this->userCodeService->verify(
                $request->getParam('code', ''),
                validity: 24 * 60 * 60,
                action: 'send_verify',
            );
        }
        catch (CodeVerificationException $e)
        {
            $loginPage = $this->configService->get('actions.login.location');

            return $this->responseEmitter->buildRedirect(
                $loginPage,
                [],
                'error',
                'verify.link_expired',
            );
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

        return $this->responseEmitter->buildRedirect(
            $afterVerifyPage,
            [],
            'success',
            'verify.mail_sent',
        );
    }
}
