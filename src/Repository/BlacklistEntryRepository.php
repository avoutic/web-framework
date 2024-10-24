<?php

namespace WebFramework\Repository;

use WebFramework\Core\RepositoryCore;
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
