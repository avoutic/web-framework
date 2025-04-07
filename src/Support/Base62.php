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

class Base62
{
    private string $base = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function encode(int $nr): string
    {
        $str = '';
        $q = $nr;

        while ($q)
        {
            $r = $q % 62;
            $q = floor($q / 62);
            $str = $this->base[$r].$str;
        }

        return $str;
    }

    public function decode(string $encoded): int
    {
        $limit = strlen($encoded);
        $total = 0;

        for ($i = 0; $i < $limit; $i++)
        {
            $res = strpos($this->base, $encoded[$i]);
            if ($res === false)
            {
                throw new \InvalidArgumentException('Encoded string contains unknown characters');
            }

            $total = 62 * $total + $res;
        }

        return $total;
    }
}
