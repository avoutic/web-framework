<?php

namespace WebFramework\Repository;

use WebFramework\Core\RepositoryCore;
use WebFramework\Entity\Session;

/**
 * @extends RepositoryCore<Session>
 */
class SessionRepository extends RepositoryCore
{
    protected static string $entityClass = Session::class;
}
