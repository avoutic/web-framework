<?php

namespace WebFramework\Core;

interface SanityCheckInterface
{
    public function allow_fixing(): void;

    public function perform_checks(): bool;

    public function set_verbose(): void;
}
