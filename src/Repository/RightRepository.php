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

use WebFramework\Entity\Right;

/**
 * Repository class for Right entities.
 *
 * @extends RepositoryCore<Right>
 */
class RightRepository extends RepositoryCore
{
    /** @var class-string<Right> The entity class associated with this repository */
    protected static string $entityClass = Right::class;

    /**
     * Get a Right entity by its short name.
     *
     * @param string $shortName The short name of the right
     *
     * @return null|Right The Right entity if found, null otherwise
     */
    public function getRightByShortName(string $shortName): ?Right
    {
        return $this->findOneBy([
            'short_name' => $shortName,
        ]);
    }
}
