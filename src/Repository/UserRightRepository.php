<?php

namespace WebFramework\Repository;

use WebFramework\Core\RepositoryCore;
use WebFramework\Entity\UserRight;

/**
 * Repository class for UserRight entities.
 *
 * @extends RepositoryCore<UserRight>
 */
class UserRightRepository extends RepositoryCore
{
    /** @var class-string<UserRight> The entity class associated with this repository */
    protected static string $entityClass = UserRight::class;
}
