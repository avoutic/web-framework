<?php

namespace WebFramework\Security;

interface RandomProvider
{
    public function getRandom(int $length): string;
}
