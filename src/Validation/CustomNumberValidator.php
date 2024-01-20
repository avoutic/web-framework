<?php

namespace WebFramework\Validation;

class CustomNumberValidator extends CustomValidator
{
    private ?int $minValue = null;
    private ?int $maxValue = null;

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
            return $this->getDefault();
        }

        return (int) $value;
    }

    public function getRules(): array
    {
        $rules = parent::getRules();

        if ($this->minValue !== null)
        {
            $rules[] = new MinValueRule($this->minValue);
        }

        if ($this->maxValue !== null)
        {
            $rules[] = new MaxValueRule($this->maxValue);
        }

        return $rules;
    }

    public function minValue(int $value): self
    {
        $this->minValue = $value;

        return $this;
    }

    public function maxValue(int $value): self
    {
        $this->maxValue = $value;

        return $this;
    }
}
