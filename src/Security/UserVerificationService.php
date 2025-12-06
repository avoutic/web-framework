<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Security;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use Slim\Http\ServerRequest as Request;
use WebFramework\Entity\User;
use WebFramework\Event\EventService;
use WebFramework\Event\UserVerified;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Mail\UserMailer;
use WebFramework\Repository\UserRepository;
use WebFramework\Repository\VerificationCodeRepository;

/**
 * Handles user verification processes.
 */
class UserVerificationService
{
    /**
     * UserVerificationService constructor.
     *
     * @param EventService               $eventService               The event service
     * @param LoggerInterface            $logger                     The logger service
     * @param UserCodeService            $userCodeService            The user code service
     * @param UserMailer                 $userMailer                 The user mailer service
     * @param UserRepository             $userRepository             The user repository
     * @param VerificationCodeRepository $verificationCodeRepository The verification code repository
     * @param int                        $codeExpiryMinutes          The number of minutes until verification codes expire
     */
    public function __construct(
        private EventService $eventService,
        private LoggerInterface $logger,
        private UserCodeService $userCodeService,
        private UserMailer $userMailer,
        private UserRepository $userRepository,
        private VerificationCodeRepository $verificationCodeRepository,
        private int $codeExpiryMinutes,
    ) {}

    /**
     * Send a verification email to a user and return the GUID for passing to the verify action.
     *
     * @param User         $user            The user to send the verification email to
     * @param string       $action          The action to send the verification email for
     * @param array<mixed> $afterVerifyData Additional data to include after verification
     *
     * @return string The GUID for passing to the verify action
     */
    public function sendVerifyMail(User $user, string $action, array $afterVerifyData = []): string
    {
        $this->logger->debug('Sending verification mail', ['user_id' => $user->getId()]);

        ['guid' => $guid, 'code' => $code] = $this->userCodeService->generateVerificationCodeEntry(
            $user,
            $action,
            [
                'after_verify_data' => $afterVerifyData,
            ]
        );

        $this->userMailer->emailVerificationCode(
            $user->getEmail(),
            [
                'user' => $user->toArray(),
                'code' => $code,
                'validity' => $this->codeExpiryMinutes,
            ]
        );

        return $guid;
    }

    /**
     * Handle the data for a verification request.
     *
     * @param Request $request The current request
     * @param string  $guid    The GUID of the verification code
     * @param string  $action  The action to handle the verification data for
     *
     * @return array{user: User, after_verify_data: array<mixed>} The user and after verify data
     *
     * @throws CodeVerificationException If the code is invalid
     */
    public function handleData(Request $request, string $guid, string $action): array
    {
        $this->logger->debug('Handling verification data', ['guid' => $guid]);

        $verificationCode = $this->verificationCodeRepository->getByGuid($guid);

        if ($verificationCode === null)
        {
            throw new CodeVerificationException();
        }

        // Verify the code was actually verified (marked as correct) and hasn't expired
        if (!$verificationCode->isCorrect())
        {
            $this->logger->debug('Verification code not yet correct', ['guid' => $guid]);

            throw new CodeVerificationException();
        }

        if ($verificationCode->isExpired())
        {
            $this->logger->debug('Verification code expired', ['guid' => $guid]);

            throw new CodeVerificationException();
        }

        // Prevent replay attacks - check if code has already been invalidated or processed
        if ($verificationCode->isInvalidated() || $verificationCode->isProcessed())
        {
            $this->logger->warning('Verification code already invalidated or processed (replay attempt)', ['guid' => $guid]);

            throw new CodeVerificationException();
        }

        if ($verificationCode->getAction() !== $action)
        {
            $this->logger->error('Invalid action for verification code', [
                'guid' => $guid,
                'action' => $verificationCode->getAction(),
            ]);

            throw new CodeVerificationException();
        }

        $flowData = $verificationCode->getFlowData();
        $user = $this->userRepository->find($verificationCode->getUserId());

        if ($user === null)
        {
            throw new CodeVerificationException();
        }

        if (!$user->isVerified())
        {
            $this->logger->info('Setting user to verified', ['user_id' => $user->getId()]);

            $user->setVerified(Carbon::now()->getTimestamp());
            $this->userRepository->save($user);

            $this->eventService->dispatch(new UserVerified($request, $user));
        }
        else
        {
            $this->logger->info('Updating user verified at', ['user_id' => $user->getId()]);

            $user->setVerifiedAt(Carbon::now()->getTimestamp());
            $this->userRepository->save($user);
        }

        // Mark as processed to prevent replay attacks
        $verificationCode->markAsProcessed();
        $this->verificationCodeRepository->save($verificationCode);

        return [
            'user' => $user,
            'after_verify_data' => $flowData['after_verify_data'] ?? [],
        ];
    }
}
