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
