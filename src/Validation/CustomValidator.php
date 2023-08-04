<?php

namespace WebFramework\Validation;

class CustomValidator implements Validator
{
    private ?string $filter = null;
    private bool $required = false;
    private ?int $minLength = null;
    private ?int $maxLength = null;
    private mixed $default = '';

    public function __construct(
        private string $name,
    ) {
    }

    public function filter(string $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    public function optional(): self
    {
        $this->required = false;

        return $this;
    }

    public function required(): self
    {
        $this->required = true;

        return $this;
    }

    public function minLength(int $length): self
    {
        $this->minLength = $length;

        return $this;
    }

    public function maxLength(int $length): self
    {
        $this->maxLength = $length;

        return $this;
    }

    public function default(mixed $default): self
    {
        $this->default = $default;

        return $this;
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

        if ($this->minLength !== null)
        {
            $rules[] = new MinLengthRule($this->minLength);
        }

        if ($this->maxLength !== null)
        {
            $rules[] = new MaxLengthRule($this->maxLength);
        }

        return $rules;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getTyped(string $value): mixed
    {
        return $value;
    }
}
