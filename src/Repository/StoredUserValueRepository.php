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

use WebFramework\Core\EntityCollection;
use WebFramework\Core\RepositoryCore;
use WebFramework\Entity\StoredUserValue;

/**
 * Repository class for StoredUserValue entities.
 *
 * @extends RepositoryCore<StoredUserValue>
 */
class StoredUserValueRepository extends RepositoryCore
{
    /** @var class-string<StoredUserValue> */
    protected static string $entityClass = StoredUserValue::class;

    /**
     * Get all stored values for a specific user and module.
     *
     * @param int    $userId The user ID
     * @param string $module The module name
     *
     * @return EntityCollection<StoredUserValue> A collection of stored values
     */
    public function getValuesByUserAndModule(int $userId, string $module): EntityCollection
    {
        return $this->getObjects(0, -1, ['user_id' => $userId, 'module' => $module], 'name');
    }

    /**
     * Get a specific stored value for a user.
     *
     * @param int    $userId The user ID
     * @param string $module The module name
     * @param string $name   The value name
     *
     * @return null|StoredUserValue The stored value if found, null otherwise
     */
    public function getValue(int $userId, string $module, string $name): ?StoredUserValue
    {
        return $this->getObject([
            'user_id' => $userId,
            'module' => $module,
            'name' => $name,
        ]);
    }
}
