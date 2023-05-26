<?php

namespace WebFramework\Core;

interface SanityCheckInterface
{
    /**
     * @param array<mixed> $config
     */
    public function setConfig(array $config): void;

    public function allowFixing(): void;

    public function performChecks(): bool;

    public function setVerbose(): void;
}
