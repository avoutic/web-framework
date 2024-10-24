<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Security;

use WebFramework\Core\Database;
use WebFramework\Entity\User;
use WebFramework\Repository\RightRepository;
use WebFramework\Repository\UserRightRepository;

/**
 * Manages user rights and permissions.
 */
class UserRightService
{
    /**
     * UserRightService constructor.
     *
     * @param Database            $database            The database service
     * @param RightRepository     $rightRepository     The right repository
     * @param UserRightRepository $userRightRepository The user right repository
     */
    public function __construct(
        private Database $database,
        private RightRepository $rightRepository,
        private UserRightRepository $userRightRepository,
    ) {}

    /**
     * Add a right to a user.
     *
     * @param User   $user      The user to add the right to
     * @param string $shortName The short name of the right to add
     *
     * @throws \InvalidArgumentException If the right is unknown
     */
    public function addRight(User $user, string $shortName): void
    {
        $right = $this->rightRepository->getRightByShortName($shortName);

        if ($right === null)
        {
            throw new \InvalidArgumentException('Right unknown');
        }

        $userRight = $this->userRightRepository->getObject([
            'user_id' => $user->getId(),
            'right_id' => $right->getId(),
        ]);

        if ($userRight !== null)
        {
            return;
        }

        $this->userRightRepository->create([
            'user_id' => $user->getId(),
            'right_id' => $right->getId(),
        ]);
    }

    /**
     * Delete a right from a user.
     *
     * @param User   $user      The user to remove the right from
     * @param string $shortName The short name of the right to remove
     *
     * @throws \InvalidArgumentException If the right is unknown
     */
    public function deleteRight(User $user, string $shortName): void
    {
        $right = $this->rightRepository->getRightByShortName($shortName);

        if ($right === null)
        {
            throw new \InvalidArgumentException('Right unknown');
        }

        $userRight = $this->userRightRepository->getObject([
            'user_id' => $user->getId(),
            'right_id' => $right->getId(),
        ]);

        if ($userRight === null)
        {
            return;
        }

        $this->userRightRepository->delete($userRight);
    }

    /**
     * Check if a user has a specific right.
     *
     * @param User   $user      The user to check
     * @param string $shortName The short name of the right to check for
     *
     * @return bool True if the user has the right, false otherwise
     */
    public function hasRight(User $user, string $shortName): bool
    {
        $right = $this->rightRepository->getRightByShortName($shortName);

        if ($right === null)
        {
            return false;
        }

        $userRight = $this->userRightRepository->getObject([
            'user_id' => $user->getId(),
            'right_id' => $right->getId(),
        ]);

        return ($userRight !== null);
    }

    /**
     * Get all rights for a user.
     *
     * @param User $user The user to get rights for
     *
     * @return array<string> An array of right short names
     */
    public function getRights(User $user): array
    {
        $query = <<<'SQL'
        SELECT short_name
        FROM rights AS r,
             user_rights AS ur
        WHERE ur.user_id = ? AND
              ur.right_id = r.id
        ORDER BY r.short_name
SQL;

        $params = [$user->getId()];

        $result = $this->database->query($query, $params, 'Failed to retrieve rights');

        $data = [];

        foreach ($result as $row)
        {
            $data[] = $row['short_name'];
        }

        return $data;
    }
}
