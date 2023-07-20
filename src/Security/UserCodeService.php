<?php

namespace WebFramework\Security;

use WebFramework\Entity\User;
use WebFramework\Exception\CodeVerificationException;

class UserCodeService
{
    public function __construct(
        private ProtectService $protectService,
    ) {
    }

    /**
     * @param array<mixed> $params
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
     * @return array{user_id: int, action: string, params: array<mixed>, timestamp: int}
     */
    public function verify(string $packedCode, string $action, int $validity): array
    {
        if (!strlen($packedCode))
        {
            throw new CodeVerificationException();
        }

        // Check correctness of data received
        //
        $data = $this->protectService->unpackArray($packedCode);
        if (!is_array($data))
        {
            throw new CodeVerificationException();
        }

        if (!isset($data['action']) || $data['action'] !== $action)
        {
            throw new CodeVerificationException();
        }

        // Check if expired
        //
        if (!isset($data['timestamp']) || $data['timestamp'] + $validity < time())
        {
            throw new CodeVerificationException();
        }

        return $data;
    }
}
