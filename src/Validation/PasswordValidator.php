<?php

namespace WebFramework\Validation;

class PasswordValidator implements Validator
{
    public function __construct(
        private string $name = 'password',
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRules(): array
    {
        return [];
    }

    public function isRequired(): bool
    {
        return true;
    }
}
