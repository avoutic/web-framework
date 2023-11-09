<?php

namespace WebFramework\Security;

use WebFramework\Core\Database;
use WebFramework\Entity\User;
use WebFramework\Repository\RightRepository;
use WebFramework\Repository\UserRightRepository;

class UserRightService
{
    public function __construct(
        private Database $database,
        private RightRepository $rightRepository,
        private UserRightRepository $userRightRepository,
    ) {
    }

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
     * @return array<string>
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
