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
use WebFramework\Support\StoredUserValuesService;

/**
 * Handles user verification processes.
 */
class UserVerificationService
{
    /**
     * Stored user value that holds the registration after verify data until first verification.
     */
    public const AFTER_VERIFY_DATA_KEY = 'registration.after_verify_data';

    /**
     * UserVerificationService constructor.
     *
     * @param EventService               $eventService               The event service
     * @param LoggerInterface            $logger                     The logger service
     * @param StoredUserValuesService    $storedUserValuesService    The stored user values service
     * @param UserCodeService            $userCodeService            The user code service
     * @param UserMailer                 $userMailer                 The user mailer service
     * @param UserRepository             $userRepository             The user repository
     * @param VerificationCodeRepository $verificationCodeRepository The verification code repository
     * @param int                        $codeExpiryMinutes          The number of minutes until verification codes expire
     */
    public function __construct(
        private EventService $eventService,
        private LoggerInterface $logger,
        private StoredUserValuesService $storedUserValuesService,
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
     * @return array{user: User, after_verify_data: array<mixed>, first_verification: bool, post_verify_data: array<mixed>} The user,
     *                                                                                                                      the after verify data of this code, whether this call verified the user for the first
     *                                                                                                                      time, and the registration after verify data (only filled on first verification)
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

        $firstVerification = !$user->isVerified();
        $postVerifyData = [];

        if ($firstVerification)
        {
            $postVerifyData = $this->takeRegistrationData($user, $action, $flowData['after_verify_data'] ?? []);

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
            'first_verification' => $firstVerification,
            'post_verify_data' => $postVerifyData,
        ];
    }

    /**
     * Get the registration after verify data for a user and remove it from storage.
     *
     * The data is stored with the user at registration. Users registered before that was
     * the case only have it in their register code, so that is the fallback.
     *
     * @param User         $user                The user being verified for the first time
     * @param string       $action              The action of the verification code
     * @param array<mixed> $codeAfterVerifyData The after verify data of the verification code
     *
     * @return array<mixed> The registration after verify data
     */
    private function takeRegistrationData(User $user, string $action, array $codeAfterVerifyData): array
    {
        $fallback = ($action === 'register') ? $codeAfterVerifyData : [];

        if (!$this->storedUserValuesService->valueExists($user->getId(), self::AFTER_VERIFY_DATA_KEY))
        {
            return $fallback;
        }

        $data = json_decode($this->storedUserValuesService->getValue($user->getId(), self::AFTER_VERIFY_DATA_KEY), true);

        $this->storedUserValuesService->deleteValue($user->getId(), self::AFTER_VERIFY_DATA_KEY);

        return is_array($data) ? $data : $fallback;
    }
}
