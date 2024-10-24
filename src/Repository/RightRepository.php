<?php

namespace WebFramework\Repository;

use WebFramework\Core\RepositoryCore;
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
        return $this->getObject(['short_name' => $shortName]);
    }
}
