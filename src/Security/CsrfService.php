<?php

namespace WebFramework\Security;

use Odan\Session\SessionInterface;

class CsrfService
{
    public function __construct(
        private RandomProvider $randomProvider,
        private SessionInterface $browserSession,
    ) {
    }

    private function storeNewToken(): void
    {
        $this->browserSession->set('csrf_token', $this->randomProvider->getRandom(16));
    }

    protected function getStoredToken(): string
    {
        return $this->browserSession->get('csrf_token');
    }

    protected function isValidTokenStored(): bool
    {
        $token = $this->browserSession->get('csrf_token');

        return ($token !== null && strlen($token) == 16);
    }

    public function getToken(): string
    {
        if (!$this->isValidTokenStored())
        {
            $this->storeNewToken();
        }

        $token = $this->getStoredToken();

        $xor = $this->randomProvider->getRandom(16);
        for ($i = 0; $i < 16; $i++)
        {
            $token[$i] = chr(ord($xor[$i]) ^ ord($token[$i]));
        }

        return bin2hex($xor).bin2hex($token);
    }

    public function validateToken(string $token): bool
    {
        if (!$this->isValidTokenStored())
        {
            return false;
        }

        $check = $this->getStoredToken();
        $value = $token;
        if (strlen($value) != 16 * 4 || strlen($check) != 16)
        {
            return false;
        }

        $xor = pack('H*', substr($value, 0, 16 * 2));
        $token = pack('H*', substr($value, 16 * 2, 16 * 2));

        // Slow compare (time-constant)
        $diff = 0;
        for ($i = 0; $i < 16; $i++)
        {
            $token[$i] = chr(ord($xor[$i]) ^ ord($token[$i]));
            $diff |= ord($token[$i]) ^ ord($check[$i]);
        }

        return ($diff === 0);
    }
}
