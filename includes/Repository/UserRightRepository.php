<?php

namespace WebFramework\Repository;

use WebFramework\Core\RepositoryCore;
use WebFramework\Entity\UserRight;

/**
 * @extends RepositoryCore<UserRight>
 */
class UserRightRepository extends RepositoryCore
{
    protected static string $entityClass = UserRight::class;
}
