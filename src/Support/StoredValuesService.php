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

use WebFramework\Entity\StoredValue;
use WebFramework\Repository\StoredValueRepository;

/**
 * Service class for managing stored configuration values.
 */
class StoredValuesService
{
    /**
     * @param StoredValueRepository $repository The StoredValue repository
     * @param null|string           $module     The module name for which values are stored
     */
    public function __construct(
        private StoredValueRepository $repository,
        private ?string $module = null,
    ) {}

    /**
     * Get all stored values for the current module.
     *
     * @param null|string $module The module name for which values are stored
     *
     * @return array<string> An associative array of stored values
     */
    public function getValues(?string $module = null): array
    {
        $module = $module ?? $this->module;

        if ($module === null)
        {
            throw new \InvalidArgumentException('module cannot be null');
        }

        $values = $this->repository->getValuesByModule($module);

        $info = [];

        foreach ($values as $value)
        {
            $info[$value->getName()] = $value->getValue();
        }

        return $info;
    }

    /**
     * Get a specific stored value.
     *
     * @param string      $name    The name of the value to retrieve
     * @param string      $default The default value to return if not found
     * @param null|string $module  The module name for which values are stored
     *
     * @return string The stored value or the default value
     */
    public function getValue(string $name, string $default = '', ?string $module = null): string
    {
        $module = $module ?? $this->module;

        if ($module === null)
        {
            throw new \InvalidArgumentException('module cannot be null');
        }

        $storedValue = $this->repository->getValue($module, $name);

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
     * @param null|string $module The module name for which values are stored
     */
    public function setValue(string $name, string $value, ?string $module = null): void
    {
        $module = $module ?? $this->module;

        if ($module === null)
        {
            throw new \InvalidArgumentException('module cannot be null');
        }

        $storedValue = $this->repository->getValue($module, $name);

        if ($storedValue === null)
        {
            $storedValue = new StoredValue();
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
     * @param null|string $module The module name for which values are stored
     */
    public function deleteValue(string $name, ?string $module = null): void
    {
        $module = $module ?? $this->module;

        if ($module === null)
        {
            throw new \InvalidArgumentException('module cannot be null');
        }

        $storedValue = $this->repository->getValue($module, $name);
        if ($storedValue !== null)
        {
            $this->repository->delete($storedValue);
        }
    }
}
