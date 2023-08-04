<?php

namespace WebFramework\Validation;

class UsernameValidator extends CustomValidator
{
    public function __construct(
        string $name = 'username',
    ) {
        parent::__construct($name);

        $this->filter(FORMAT_USERNAME)->maxLength(255)->required();
    }
}
