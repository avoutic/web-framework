<?php

namespace WebFramework\Validation;

class CustomNumberValidator extends CustomValidator
{
    public function __construct(
        string $name,
    ) {
        parent::__construct($name);

        $this->filter('\d+')->default(null);
    }

    public function getTyped(string $value): mixed
    {
        if (!strlen($value))
        {
            return null;
        }

        return (int) $value;
    }
}
