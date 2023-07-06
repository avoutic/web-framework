<?php

namespace WebFramework\SanityCheck;

abstract class Base implements SanityCheckInterface
{
    protected bool $allowFixing = false;
    protected bool $verbose = false;

    /** @var array<mixed> */
    protected array $config = [];

    abstract public function performChecks(): bool;

    /**
     *  @param array<mixed> $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function allowFixing(): void
    {
        $this->allowFixing = true;
    }

    public function setVerbose(): void
    {
        $this->verbose = true;
    }

    protected function addOutput(string $str): void
    {
        if ($this->verbose)
        {
            echo $str;
        }
    }
}
