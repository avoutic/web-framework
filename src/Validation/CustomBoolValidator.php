<?php

namespace WebFramework\Validation;

class CustomBoolValidator extends CustomValidator
{
    public function __construct(
        string $name,
    ) {
        parent::__construct($name);

        $this->filter('0|1|true|false');
    }

    public function getTyped(string $value): mixed
    {
        return ($value === '1' || $value === 'true');
    }
}
