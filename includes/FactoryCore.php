<?php

namespace WebFramework\Core;

class FactoryCore extends FrameworkCore
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return array<string>
     */
    public function __serialize(): array
    {
        return [];
    }

    /**
     * @param array<string> $data
     */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
    }
}
