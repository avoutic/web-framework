<?php

namespace WebFramework\Validation;

class EmailValidator extends CustomValidator
{
    public function __construct(
        string $name = 'email',
    ) {
        parent::__construct($name);

        $this->filter(FORMAT_EMAIL)->maxLength(255);
    }
}
