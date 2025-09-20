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

use WebFramework\Entity\BlacklistEntry;

/**
 * Repository class for BlacklistEntry entities.
 *
 * @extends RepositoryCore<BlacklistEntry>
 */
class BlacklistEntryRepository extends RepositoryCore
{
    /** @var class-string<BlacklistEntry> The entity class associated with this repository */
    protected static string $entityClass = BlacklistEntry::class;
}
