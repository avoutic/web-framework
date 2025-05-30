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
     */
    public function __construct(
        private StoredValueRepository $repository,
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
     * Get all stored values for the current module.
     *
     * @param string $module The module name for which values are stored
     *
     * @return array<string> An associative array of stored values
     */
    public function getValues(string $module): array
    {
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
     * @param string $name    The name of the value to retrieve (with module prefix)
     * @param string $default The default value to return if not found
     *
     * @return string The stored value or the default value
     */
    public function getValue(string $name, string $default = ''): string
    {
        [$module, $key] = $this->splitName($name);

        $storedValue = $this->repository->getValue($module, $key);

        if ($storedValue === null)
        {
            return $default;
        }

        return $storedValue->getValue();
    }

    /**
     * Set a stored value.
     *
     * @param string $name  The name of the value to set (with module prefix)
     * @param string $value The value to store
     */
    public function setValue(string $name, string $value): void
    {
        [$module, $key] = $this->splitName($name);

        $storedValue = $this->repository->getValue($module, $key);

        if ($storedValue === null)
        {
            $storedValue = new StoredValue();
            $storedValue->setModule($module);
            $storedValue->setName($key);
        }

        $storedValue->setValue($value);
        $this->repository->save($storedValue);
    }

    /**
     * Delete a stored value.
     *
     * @param string $name The name of the value to delete (with module prefix)
     */
    public function deleteValue(string $name): void
    {
        [$module, $key] = $this->splitName($name);

        $storedValue = $this->repository->getValue($module, $key);

        if ($storedValue !== null)
        {
            $this->repository->delete($storedValue);
        }
    }
}
