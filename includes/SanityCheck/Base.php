<?php

namespace WebFramework\Core\SanityCheck;

use WebFramework\Core\SanityCheckInterface;

abstract class Base implements SanityCheckInterface
{
    protected bool $allow_fixing = false;
    protected bool $verbose = false;

    abstract public function perform_checks(): bool;

    public function allow_fixing(): void
    {
        $this->allow_fixing = true;
    }

    public function set_verbose(): void
    {
        $this->verbose = true;
    }

    protected function add_output(string $str): void
    {
        if ($this->verbose)
        {
            echo $str;
        }
    }
}
