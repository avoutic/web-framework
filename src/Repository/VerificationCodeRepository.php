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

use WebFramework\Entity\VerificationCode;

/**
 * Repository class for VerificationCode entities.
 *
 * @extends RepositoryCore<VerificationCode>
 */
class VerificationCodeRepository extends RepositoryCore
{
    /** @var class-string<VerificationCode> The entity class associated with this repository */
    protected static string $entityClass = VerificationCode::class;

    /**
     * Get a VerificationCode entity by GUID.
     *
     * @param string $guid The GUID to search for
     *
     * @return null|VerificationCode The VerificationCode entity if found, null otherwise
     */
    public function getByGuid(string $guid): ?VerificationCode
    {
        return $this->findOneBy([
            'guid' => $guid,
        ]);
    }

    /**
     * Get an active VerificationCode entity by GUID.
     * Active means: not expired, not correct, not used, and has attempts remaining.
     *
     * @param string $guid The GUID to search for
     *
     * @return null|VerificationCode The VerificationCode entity if found and active, null otherwise
     */
    public function getActiveByGuid(string $guid): ?VerificationCode
    {
        $code = $this->getByGuid($guid);

        if ($code === null)
        {
            return null;
        }

        if ($code->isExpired() || $code->isCorrect() || $code->isInvalidated() || !$code->hasAttemptsRemaining())
        {
            return null;
        }

        return $code;
    }
}
