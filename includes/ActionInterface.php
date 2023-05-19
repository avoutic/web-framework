<?php

namespace WebFramework\Core;

interface ActionInterface
{
    /**
     * @return array<string>
     */
    public function get_permissions(): array;

    /**
     * @return array<string, string>
     */
    public function get_filter(): array;
}
