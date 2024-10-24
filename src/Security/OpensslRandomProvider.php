<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Security;

/**
 * Class OpensslRandomProvider.
 *
 * Implements the RandomProvider interface using OpenSSL for random number generation.
 */
class OpensslRandomProvider implements RandomProvider
{
    /**
     * Get a random string of bytes.
     *
     * @param int $length The number of random bytes to generate
     *
     * @return string The generated random bytes
     */
    public function getRandom(int $length): string
    {
        return openssl_random_pseudo_bytes($length);
    }
}
