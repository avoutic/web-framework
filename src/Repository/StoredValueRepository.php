<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Repository;

use WebFramework\Entity\EntityCollection;
use WebFramework\Entity\StoredValue;

/**
 * Repository class for StoredValue entities.
 *
 * @extends RepositoryCore<StoredValue>
 */
class StoredValueRepository extends RepositoryCore
{
    /** @var class-string<StoredValue> */
    protected static string $entityClass = StoredValue::class;

    /**
     * Get all stored values for a specific module.
     *
     * @param string $module The module name
     *
     * @return EntityCollection<StoredValue> A collection of StoredValue entities
     */
    public function getValuesByModule(string $module): EntityCollection
    {
        return $this->query()
            ->where([
                'module' => $module,
            ])
            ->orderByAsc('name')
            ->execute()
        ;
    }

    /**
     * Get a specific stored value by module and name.
     *
     * @param string $module The module name
     * @param string $name   The value name
     *
     * @return null|StoredValue The StoredValue entity if found, null otherwise
     */
    public function getValue(string $module, string $name): ?StoredValue
    {
        return $this->query()
            ->where([
                'module' => $module,
                'name' => $name,
            ])
            ->getOne()
        ;
    }
}
