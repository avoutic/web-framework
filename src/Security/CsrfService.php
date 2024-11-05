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

use Odan\Session\SessionInterface;

/**
 * Class CsrfService.
 *
 * Handles CSRF (Cross-Site Request Forgery) protection.
 */
class CsrfService
{
    /**
     * CsrfService constructor.
     *
     * @param RandomProvider   $randomProvider The random provider service
     * @param SessionInterface $browserSession The browser session service
     */
    public function __construct(
        private RandomProvider $randomProvider,
        private SessionInterface $browserSession,
    ) {}

    /**
     * Store a new CSRF token in the session.
     */
    private function storeNewToken(): void
    {
        $this->browserSession->set('csrf_token', $this->randomProvider->getRandom(16));
    }

    /**
     * Get the stored CSRF token.
     *
     * @return string The stored CSRF token
     */
    public function getStoredToken(): string
    {
        return $this->browserSession->get('csrf_token');
    }

    /**
     * Check if a valid CSRF token is stored in the session.
     *
     * @return bool True if a valid token is stored, false otherwise
     */
    public function isValidTokenStored(): bool
    {
        $token = $this->browserSession->get('csrf_token');

        return ($token !== null && strlen($token) == 16);
    }

    /**
     * Get a new or existing CSRF token.
     *
     * @return string The CSRF token
     */
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

    /**
     * Validate a CSRF token.
     *
     * @param string $token The token to validate
     *
     * @return bool True if the token is valid, false otherwise
     */
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
