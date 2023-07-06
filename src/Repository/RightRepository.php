<?php

namespace WebFramework\Repository;

use WebFramework\Core\RepositoryCore;
use WebFramework\Entity\Right;

/**
 * @extends RepositoryCore<Right>
 */
class RightRepository extends RepositoryCore
{
    protected static string $entityClass = Right::class;

    public function getRightByShortName(string $shortName): ?Right
    {
        return $this->getObject(['short_name' => $shortName]);
    }
}
