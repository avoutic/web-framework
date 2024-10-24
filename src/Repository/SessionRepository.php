<?php

namespace WebFramework\Repository;

use WebFramework\Core\RepositoryCore;
use WebFramework\Entity\Session;

/**
 * Repository class for Session entities.
 *
 * @extends RepositoryCore<Session>
 */
class SessionRepository extends RepositoryCore
{
    /** @var class-string<Session> The entity class associated with this repository */
    protected static string $entityClass = Session::class;
}
