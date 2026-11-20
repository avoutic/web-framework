<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Exception;

/**
 * The kind of database error, as classified by the database driver.
 */
enum DatabaseErrorType: string
{
    /** A unique or primary key constraint was violated. */
    case DuplicateKey = 'duplicate_key';

    /** Waiting for a lock timed out. */
    case LockTimeout = 'lock_timeout';

    /** The transaction was chosen as the victim of a deadlock. */
    case Deadlock = 'deadlock';

    /** Any other error. */
    case Other = 'other';
}
