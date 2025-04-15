<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Support;

use WebFramework\Security\RandomProvider;

/**
 * Provider for UUIDs.
 *
 * Generates RFC 4122 compliant Version 4 UUIDs
 */
class UuidProvider
{
    public function __construct(
        private RandomProvider $randomProvider,
    ) {}

    public function generate(): string
    {
        $data = $this->randomProvider->getRandom(16);

        if (strlen($data) !== 16)
        {
            throw new \RuntimeException('Invalid random data length');
        }

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0F | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80);

        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
