<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
