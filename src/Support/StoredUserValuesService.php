<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Support;

use WebFramework\Entity\StoredUserValue;
use WebFramework\Repository\StoredUserValueRepository;

/**
 * Service class for managing stored user-specific configuration values.
 */
class StoredUserValuesService
{
    /**
     * @param StoredUserValueRepository $repository The StoredUserValue repository
     */
    public function __construct(
        private StoredUserValueRepository $repository,
    ) {}

    /**
     * Split a name into module and name.
     *
     * @param string $name The name to split
     *
     * @return array<string> The module and name
     */
    private function splitName(string $name): array
    {
        $parts = explode('.', $name);

        if (count($parts) !== 2)
        {
            throw new \InvalidArgumentException('Invalid prefixed name: '.$name);
        }

        return $parts;
    }

    /**
     * Get all stored values for the current user and module.
     *
     * @param int    $userId The user ID
     * @param string $module The module name
     *
     * @return array<string> An associative array of stored values
     */
    public function getValues(int $userId, string $module): array
    {
        $values = $this->repository->getValuesByUserAndModule($userId, $module);

        $info = [];

        foreach ($values as $value)
        {
            $info[$value->getName()] = $value->getValue();
        }

        return $info;
    }

    /**
     * Check if a specific value exists.
     *
     * @param int    $userId The user ID
     * @param string $name   The name of the value to check (with module prefix)
     *
     * @return bool True if the value exists, false otherwise
     */
    public function valueExists(int $userId, string $name): bool
    {
        [$module, $key] = $this->splitName($name);

        return $this->repository->getValue($userId, $module, $key) !== null;
    }

    /**
     * Get a specific stored value.
     *
     * @param int    $userId  The user ID
     * @param string $name    The name of the value to retrieve (with module prefix)
     * @param string $default The default value to return if not found
     *
     * @return string The stored value or the default value
     */
    public function getValue(int $userId, string $name, string $default = ''): string
    {
        [$module, $key] = $this->splitName($name);

        $storedValue = $this->repository->getValue($userId, $module, $key);

        if ($storedValue === null)
        {
            return $default;
        }

        return $storedValue->getValue();
    }

    /**
     * Set a stored value.
     *
     * @param int    $userId The user ID
     * @param string $name   The name of the value to set (with module prefix)
     * @param string $value  The value to store
     */
    public function setValue(int $userId, string $name, string $value): void
    {
        [$module, $key] = $this->splitName($name);

        $storedValue = $this->repository->getValue($userId, $module, $key);

        if ($storedValue === null)
        {
            $storedValue = new StoredUserValue();
            $storedValue->setUserId($userId);
            $storedValue->setModule($module);
            $storedValue->setName($key);
        }

        $storedValue->setValue($value);
        $this->repository->save($storedValue);
    }

    /**
     * Delete a stored value.
     *
     * @param int    $userId The user ID
     * @param string $name   The name of the value to delete (with module prefix)
     */
    public function deleteValue(int $userId, string $name): void
    {
        [$module, $key] = $this->splitName($name);

        $storedValue = $this->repository->getValue($userId, $module, $key);

        if ($storedValue !== null)
        {
            $this->repository->delete($storedValue);
        }
    }
}
