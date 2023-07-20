<?php

namespace WebFramework\Validation;

class EmailValidator extends CustomValidator
{
    public function __construct(
        string $name = 'email',
        bool $required = true,
        int $maxLength = 255,
    ) {
        parent::__construct($name, FORMAT_EMAIL, $required, $maxLength);
    }
}
