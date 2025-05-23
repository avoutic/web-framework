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
     * @param null|int                  $userId     The ID of the user
     * @param null|string               $module     The module name for which values are stored
     */
    public function __construct(
        private StoredUserValueRepository $repository,
        private ?int $userId,
        private ?string $module,
    ) {}

    /**
     * Get all stored values for the current user and module.
     *
     * @param null|int    $userId The user ID
     * @param null|string $module The module name
     *
     * @return array<string> An associative array of stored values
     */
    public function getValues(?int $userId = null, ?string $module = null): array
    {
        $userId = $userId ?? $this->userId;
        $module = $module ?? $this->module;

        if ($userId === null)
        {
            throw new \InvalidArgumentException('userId cannot be null');
        }

        if ($module === null)
        {
            throw new \InvalidArgumentException('module cannot be null');
        }

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
     * @param string      $name   The name of the value to check
     * @param null|int    $userId The user ID
     * @param null|string $module The module name
     *
     * @return bool True if the value exists, false otherwise
     */
    public function valueExists(string $name, ?int $userId = null, ?string $module = null): bool
    {
        $userId = $userId ?? $this->userId;
        $module = $module ?? $this->module;

        if ($userId === null)
        {
            throw new \InvalidArgumentException('userId cannot be null');
        }

        if ($module === null)
        {
            throw new \InvalidArgumentException('module cannot be null');
        }

        return $this->repository->getValue($userId, $module, $name) !== null;
    }

    /**
     * Get a specific stored value.
     *
     * @param string      $name    The name of the value to retrieve
     * @param string      $default The default value to return if not found
     * @param null|int    $userId  The user ID
     * @param null|string $module  The module name
     *
     * @return string The stored value or the default value
     */
    public function getValue(string $name, string $default = '', ?int $userId = null, ?string $module = null): string
    {
        $userId = $userId ?? $this->userId;
        $module = $module ?? $this->module;

        if ($userId === null)
        {
            throw new \InvalidArgumentException('userId cannot be null');
        }

        if ($module === null)
        {
            throw new \InvalidArgumentException('module cannot be null');
        }

        $storedValue = $this->repository->getValue($userId, $module, $name);

        if ($storedValue === null)
        {
            return $default;
        }

        return $storedValue->getValue();
    }

    /**
     * Set a stored value.
     *
     * @param string      $name   The name of the value to set
     * @param string      $value  The value to store
     * @param null|int    $userId The user ID
     * @param null|string $module The module name
     */
    public function setValue(string $name, string $value, ?int $userId, ?string $module): void
    {
        $userId = $userId ?? $this->userId;
        $module = $module ?? $this->module;

        if ($userId === null)
        {
            throw new \InvalidArgumentException('userId cannot be null');
        }

        if ($module === null)
        {
            throw new \InvalidArgumentException('module cannot be null');
        }

        $storedValue = $this->repository->getValue($userId, $module, $name);

        if ($storedValue === null)
        {
            $storedValue = new StoredUserValue();
            $storedValue->setUserId($userId);
            $storedValue->setModule($module);
            $storedValue->setName($name);
        }

        $storedValue->setValue($value);
        $this->repository->save($storedValue);
    }

    /**
     * Delete a stored value.
     *
     * @param string      $name   The name of the value to delete
     * @param null|int    $userId The user ID
     * @param null|string $module The module name
     */
    public function deleteValue(string $name, ?int $userId, ?string $module): void
    {
        $userId = $userId ?? $this->userId;
        $module = $module ?? $this->module;

        if ($userId === null)
        {
            throw new \InvalidArgumentException('userId cannot be null');
        }

        if ($module === null)
        {
            throw new \InvalidArgumentException('module cannot be null');
        }

        $storedValue = $this->repository->getValue($userId, $module, $name);

        if ($storedValue !== null)
        {
            $this->repository->delete($storedValue);
        }
    }
}
