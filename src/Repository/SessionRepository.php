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
