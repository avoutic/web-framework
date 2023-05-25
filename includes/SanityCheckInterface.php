<?php

namespace WebFramework\Core;

interface SanityCheckInterface
{
    /**
     * @param array<mixed> $config
     */
    public function set_config(array $config): void;

    public function allow_fixing(): void;

    public function perform_checks(): bool;

    public function set_verbose(): void;
}
