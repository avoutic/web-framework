<?php

namespace WebFramework\Validation;

class CustomBoolValidator extends CustomValidator
{
    public function __construct(
        string $name,
        bool $required = false,
        protected ?bool $default = null,
    ) {
        parent::__construct($name, '0|1|true|false', $required, null);
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getTyped(string $value): mixed
    {
        return ($value === '1' || $value === 'true');
    }
}
