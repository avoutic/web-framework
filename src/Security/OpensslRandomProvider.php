<?php

namespace WebFramework\Security;

class OpensslRandomProvider implements RandomProvider
{
    public function getRandom(int $length): string
    {
        return openssl_random_pseudo_bytes($length);
    }
}
