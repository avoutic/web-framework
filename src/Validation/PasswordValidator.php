<?php

namespace WebFramework\Validation;

class PasswordValidator extends CustomValidator
{
    public function __construct(
        string $name = 'password',
    ) {
        parent::__construct($name);

        $this->required();
    }
}
