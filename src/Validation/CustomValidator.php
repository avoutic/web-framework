<?php

namespace WebFramework\Validation;

class CustomValidator implements Validator
{
    public function __construct(
        private string $name,
        private ?string $filter = null,
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

        if ($this->filter !== null)
        {
            $rules[] = new FilterRule($this->filter);
        }

        $rules[] = new MaxLengthRule($this->maxLength);

        return $rules;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
