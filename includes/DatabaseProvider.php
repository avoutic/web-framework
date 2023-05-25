<?php

namespace WebFramework\Core;

class DatabaseProvider
{
    private ?Database $database = null;

    public function set(Database $database): void
    {
        $this->database = $database;
    }

    public function get(): Database|null
    {
        return $this->database;
    }
}
