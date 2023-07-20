<?php

namespace WebFramework\Validation;

class UsernameValidator extends CustomValidator
{
    public function __construct(
        string $name = 'username',
        bool $required = true,
        int $maxLength = 255,
    ) {
        parent::__construct($name, FORMAT_USERNAME, $required, $maxLength);
    }
}
