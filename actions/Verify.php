<?php

namespace WebFramework\Actions;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
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
    public function __invoke(Request $request, Response $response, array $routeArgs): Response
    {
        $filtered = $this->validatorService->getFilteredParams($request, [
            'code' => '.*',
        ]);

        $baseUrl = $this->configService->get('base_url');
        $loginPage = $this->configService->get('actions.login.location');

        try
        {
            ['user_id' => $codeUserId, 'params' => $verifyParams] = $this->userCodeService->verify(
                $filtered['code'],
                validity: 24 * 60 * 60,
                action: 'verify',
            );
        }
        catch (CodeVerificationException $e)
        {
            $message = $this->messageService->getForUrl('error', 'Verification mail expired', 'Please login again to request a new one.');

            return $this->responseEmitter->redirect("{$baseUrl}{$loginPage}?{$message}");
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
        $message = $this->messageService->getForUrl('success', 'Verification succeeded', 'Verification succeeded. You can now use your account.').'&return_page='.urlencode($afterVerifyPage);

        return $this->responseEmitter->redirect("{$baseUrl}{$loginPage}?{$message}");
    }
}
