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
     * @param ProtectService $protectService The protect service
     */
    public function __construct(
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
            'timestamp' => time(),
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
            throw new CodeVerificationException();
        }

        if ($data['action'] !== $action)
        {
            throw new CodeVerificationException();
        }

        if ($data['timestamp'] + $validity < time())
        {
            throw new CodeVerificationException();
        }

        return $data;
    }
}
