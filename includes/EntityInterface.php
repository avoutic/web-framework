<?php

namespace WebFramework\Core;

interface EntityInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
