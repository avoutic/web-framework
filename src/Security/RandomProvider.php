<?php

namespace WebFramework\Security;

/**
 * Interface RandomProvider.
 *
 * Defines the contract for random number generation services.
 */
interface RandomProvider
{
    /**
     * Get a random string of bytes.
     *
     * @param int $length The number of random bytes to generate
     *
     * @return string The generated random bytes
     */
    public function getRandom(int $length): string;
}
