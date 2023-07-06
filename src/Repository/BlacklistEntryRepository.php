<?php

namespace WebFramework\Repository;

use WebFramework\Core\RepositoryCore;
use WebFramework\Entity\BlacklistEntry;

/**
 * @extends RepositoryCore<BlacklistEntry>
 */
class BlacklistEntryRepository extends RepositoryCore
{
    protected static string $entityClass = BlacklistEntry::class;
}
