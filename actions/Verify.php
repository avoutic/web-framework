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
use WebFramework\Core\ValidatorService;
use WebFramework\Entity\User;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Repository\UserRepository;

class Verify
{
    public function __construct(
        protected Container $container,
        protected ConfigService $configService,
        protected MessageService $messageService,
        protected ResponseEmitter $responseEmitter,
        protected UserCodeService $userCodeService,
        protected UserRepository $userRepository,
        protected ValidatorService $validatorService,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    protected function customAfterVerifyActions(User $user, array $data): void
    {
    }

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        $loginPage = $this->configService->get('actions.login.location');

        try
        {
            ['user_id' => $codeUserId, 'params' => $verifyParams] = $this->userCodeService->verify(
                $request->getParam('code', ''),
                validity: 24 * 60 * 60,
                action: 'verify',
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

        // Check user status
        //
        $user = $this->userRepository->getObjectById($codeUserId);
        if ($user === null)
        {
            throw new \RuntimeException('User not found');
        }

        if (!$user->isVerified())
        {
            $user->setVerified();
            $this->userRepository->save($user);

            $this->customAfterVerifyActions($user, $verifyParams);
        }

        // Redirect to main sceen
        //
        $afterVerifyPage = $this->configService->get('actions.login.after_verify_page');

        return $this->responseEmitter->buildQueryRedirect(
            $loginPage,
            [],
            ['return_page' => $afterVerifyPage],
            'success',
            'verify.success',
        );
    }
}
