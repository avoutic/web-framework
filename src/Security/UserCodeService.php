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
use WebFramework\Entity\User;
use WebFramework\Entity\VerificationCode;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Exception\InvalidCodeException;
use WebFramework\Repository\UserRepository;
use WebFramework\Repository\VerificationCodeRepository;

/**
 * Handles generation and verification of user-specific codes.
 */
class UserCodeService
{
    /**
     * UserCodeService constructor.
     *
     * @param LoggerInterface            $logger                     The logger service
     * @param RandomProvider             $randomProvider             The random provider service
     * @param UserRepository             $userRepository             The user repository
     * @param VerificationCodeRepository $verificationCodeRepository The verification code repository
     * @param int                        $codeLength                 The length of verification codes
     * @param int                        $codeExpiryMinutes          The number of minutes until codes expire
     * @param int                        $maxAttempts                The maximum number of verification attempts
     */
    public function __construct(
        private LoggerInterface $logger,
        private RandomProvider $randomProvider,
        private UserRepository $userRepository,
        private VerificationCodeRepository $verificationCodeRepository,
        private int $codeLength,
        private int $codeExpiryMinutes,
        private int $maxAttempts,
    ) {}

    /**
     * Generate a verification code.
     */
    public function generateVerificationCode(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $alphabetLength = strlen($alphabet);
        $randomBytes = $this->randomProvider->getRandom($this->codeLength);
        $code = '';

        for ($i = 0; $i < $this->codeLength; $i++)
        {
            $code .= $alphabet[ord($randomBytes[$i]) % $alphabetLength];
        }

        return $code;
    }

    /**
     * Get the action associated with a verification code.
     *
     * @param string $guid The GUID of the verification code
     *
     * @return null|string The action associated with the verification code, or null if not found
     */
    public function getActionByGuid(string $guid): ?string
    {
        $verificationCode = $this->verificationCodeRepository->getByGuid($guid);
        if ($verificationCode === null)
        {
            return null;
        }

        return $verificationCode->getAction();
    }

    /**
     * Get a verification code that is ready to be redeemed for an action, without consuming it.
     *
     * Redeemable means: the code exists, was entered correctly, has not expired, has not
     * been invalidated or processed, and belongs to the given action. The code is not
     * marked as processed. That is up to the caller, once it has acted on the code.
     *
     * @param string $guid   The GUID of the verification code
     * @param string $action The action the code must belong to
     *
     * @return VerificationCode The redeemable verification code
     *
     * @throws CodeVerificationException If the code is not redeemable for the action
     */
    public function getRedeemableCode(string $guid, string $action): VerificationCode
    {
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

        return $verificationCode;
    }

    /**
     * Generate a verification code entry in the database.
     *
     * @param User         $user     The user to generate the code for
     * @param string       $action   The action associated with the code
     * @param array<mixed> $flowData Additional flow-specific data to store
     *
     * @return array{guid: string, code: string} The GUID and the plain code
     */
    public function generateVerificationCodeEntry(User $user, string $action, array $flowData = []): array
    {
        $code = $this->generateVerificationCode();
        $expiresAt = Carbon::now()->addMinutes($this->codeExpiryMinutes)->getTimestamp();

        $verificationCode = $this->verificationCodeRepository->create([
            'user_id' => $user->getId(),
            'code' => $code,
            'action' => $action,
            'max_attempts' => $this->maxAttempts,
            'expires_at' => $expiresAt,
            'flow_data' => json_encode($flowData),
        ]);

        $this->logger->debug('Generated verification code entry', [
            'guid' => $verificationCode->getGuid(),
            'user_id' => $user->getId(),
            'action' => $action,
        ]);

        return ['guid' => $verificationCode->getGuid(), 'code' => $code];
    }

    /**
     * Verify a code by GUID.
     *
     * @param string $guid   The GUID of the verification code entry
     * @param string $action The expected action
     * @param string $code   The code to verify
     *
     * @return array{user: User, flow_data: array<mixed>} The user and flow data
     *
     * @throws InvalidCodeException      If the code is invalid or max attempts exceeded
     * @throws CodeVerificationException If the verification code entry is invalid or expired
     */
    public function verifyCodeByGuid(string $guid, string $action, string $code): array
    {
        $this->logger->debug('Handling code verification by GUID', ['guid' => $guid, 'action' => $action]);

        $verificationCode = $this->verificationCodeRepository->getActiveByGuid($guid);

        if ($verificationCode === null)
        {
            $this->logger->debug('Verification code not found or inactive', ['guid' => $guid]);

            throw new CodeVerificationException();
        }

        if ($verificationCode->getAction() !== $action)
        {
            $this->logger->error('Invalid action for verification code', [
                'guid' => $guid,
                'expected_action' => $action,
                'actual_action' => $verificationCode->getAction(),
            ]);

            throw new CodeVerificationException();
        }

        // Increment attempts before checking code
        $verificationCode->incrementAttempts();
        $this->verificationCodeRepository->save($verificationCode);

        // Check if max attempts exceeded
        if (!$verificationCode->hasAttemptsRemaining())
        {
            $this->logger->warning('Max attempts exceeded for verification code', [
                'guid' => $guid,
                'attempts' => $verificationCode->getAttempts(),
            ]);

            throw new InvalidCodeException();
        }

        // Verify the code
        if ($verificationCode->getCode() !== $code)
        {
            $this->logger->debug('Invalid code provided', [
                'guid' => $guid,
                'attempts' => $verificationCode->getAttempts(),
            ]);

            throw new InvalidCodeException();
        }

        // Code is valid - mark as correct
        $verificationCode->markAsCorrect();
        $this->verificationCodeRepository->save($verificationCode);

        $user = $this->userRepository->find($verificationCode->getUserId());
        if ($user === null)
        {
            $this->logger->error('User not found for verification code', [
                'guid' => $guid,
                'user_id' => $verificationCode->getUserId(),
            ]);

            throw new CodeVerificationException();
        }

        $this->logger->info('Verification code verified successfully', [
            'guid' => $guid,
            'user_id' => $user->getId(),
            'action' => $action,
        ]);

        return [
            'user' => $user,
            'flow_data' => $verificationCode->getFlowData(),
        ];
    }
}
