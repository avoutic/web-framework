<?php

namespace WebFramework\Validation;

class UsernameValidator implements Validator
{
    public function __construct(
        private string $name = 'username',
        private bool $required = true,
        private int $maxLength = 255,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRules(): array
    {
        $rules = [];

        $rules[] = new FilterRule(FORMAT_USERNAME);
        $rules[] = new MaxLengthRule($this->maxLength);

        return $rules;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
