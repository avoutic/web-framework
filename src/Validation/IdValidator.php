<?php

namespace WebFramework\Validation;

class IdValidator extends CustomValidator
{
    public function __construct(
        string $name,
    ) {
        parent::__construct($name);

        $this->filter(FORMAT_ID)->default(null);
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
