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
use WebFramework\Exception\CodeVerificationException;

/**
 * Handles generation and verification of user-specific codes.
 */
class UserCodeService
{
    /**
     * UserCodeService constructor.
     *
     * @param LoggerInterface $logger         The logger service
     * @param ProtectService  $protectService The protect service
     */
    public function __construct(
        private LoggerInterface $logger,
        private ProtectService $protectService,
    ) {}

    /**
     * Generate a code for a user.
     *
     * @param User         $user   The user to generate the code for
     * @param string       $action The action associated with the code
     * @param array<mixed> $params Additional parameters to include in the code
     *
     * @return string The generated code
     */
    public function generate(User $user, string $action, array $params = []): string
    {
        $msg = [
            'user_id' => $user->getId(),
            'action' => $action,
            'params' => $params,
            'timestamp' => Carbon::now()->getTimestamp(),
        ];

        return $this->protectService->packArray($msg);
    }

    /**
     * Verify a user code.
     *
     * @param string $packedCode The packed code to verify
     * @param string $action     The expected action
     * @param int    $validity   The validity period of the code in seconds
     *
     * @return array{user_id: int, action: string, params: array<mixed>, timestamp: int} The unpacked code data
     *
     * @throws CodeVerificationException If the code is invalid or expired
     */
    public function verify(string $packedCode, string $action, int $validity): array
    {
        if (!strlen($packedCode))
        {
            $this->logger->debug('Empty code received');

            throw new CodeVerificationException();
        }

        $data = $this->protectService->unpackArray($packedCode);
        if (!is_array($data)
            || !isset($data['user_id'])
            || !isset($data['action'])
            || !isset($data['params'])
            || !isset($data['timestamp'])
            || !is_int($data['user_id'])
            || !is_string($data['action'])
            || !is_array($data['params'])
            || !is_int($data['timestamp'])
        ) {
            $this->logger->debug('Invalid code received', ['packed_code' => $packedCode]);

            throw new CodeVerificationException();
        }

        if ($data['action'] !== $action)
        {
            $this->logger->error('Invalid action received in packed code', ['user_id' => $data['user_id'], 'action' => $action]);

            throw new CodeVerificationException();
        }

        $timestamp = new Carbon($data['timestamp']);
        if ($timestamp->addSeconds($validity)->lt(Carbon::now()))
        {
            $this->logger->debug('Received expired code', ['user_id' => $data['user_id'], 'timestamp' => $data['timestamp'], 'validity' => $validity]);

            throw new CodeVerificationException();
        }

        return $data;
    }
}
