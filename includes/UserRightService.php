<?php

namespace WebFramework\Core;

use WebFramework\Entity\User;
use WebFramework\Repository\RightRepository;
use WebFramework\Repository\UserRightRepository;

class UserRightService
{
    public function __construct(
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
}
