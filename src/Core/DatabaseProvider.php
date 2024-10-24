<?php

namespace WebFramework\Core;

/**
 * Class DatabaseProvider.
 *
 * Provides a way to set and retrieve a Database instance.
 * This class acts as a simple service locator for the Database interface.
 */
class DatabaseProvider
{
    /** @var null|Database The stored Database instance */
    private ?Database $database = null;

    /**
     * Set the Database instance.
     *
     * @param Database $database The Database instance to store
     */
    public function set(Database $database): void
    {
        $this->database = $database;
    }

    /**
     * Get the stored Database instance.
     *
     * @return null|Database The stored Database instance, or null if not set
     */
    public function get(): ?Database
    {
        return $this->database;
    }
}
