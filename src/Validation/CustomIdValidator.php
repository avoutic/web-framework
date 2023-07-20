<?php

namespace WebFramework\Validation;

class CustomIdValidator extends CustomValidator
{
    public function __construct(
        string $name,
        bool $required = true,
        protected ?int $default = null,
    ) {
        parent::__construct($name, FORMAT_ID, $required, null);
    }

    public function getDefault(): mixed
    {
        return $this->default;
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
