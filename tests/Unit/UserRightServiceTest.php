<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use WebFramework\Entity\Right;
use WebFramework\Entity\User;
use WebFramework\Entity\UserRight;
use WebFramework\Repository\RightRepository;
use WebFramework\Repository\UserRightRepository;
use WebFramework\Security\UserRightService;

/**
 * @internal
 *
 * @coversNothing
 */
final class UserRightServiceTest extends \Codeception\Test\Unit
{
    public function testAddRightUnkown()
    {
        $instance = $this->make(
            UserRightService::class,
            [
                'rightRepository' => $this->make(
                    RightRepository::class,
                    [
                        'getRightByShortName' => Expected::once(null),
                    ],
                ),
                'userRightRepository' => $this->make(
                    UserRightRepository::class,
                    [
                        'getObject' => Expected::never(),
                        'create' => Expected::never(),
                    ],
                ),
            ],
        );

        $user = $this->make(
            User::class,
        );

        verify(function () use ($instance, $user) {
            $instance->addRight($user, 'NoRight');
        })
            ->callableThrows(\InvalidArgumentException::class, 'Right unknown');
    }

    public function testAddRightNew()
    {
        $instance = $this->make(
            UserRightService::class,
            [
                'rightRepository' => $this->make(
                    RightRepository::class,
                    [
                        'getRightByShortName' => Expected::once($this->make(
                            Right::class,
                            [
                                'id' => 2,
                            ],
                        )),
                    ],
                ),
                'userRightRepository' => $this->make(
                    UserRightRepository::class,
                    [
                        'getObject' => Expected::once(null),
                        'create' => Expected::once($this->makeEmpty(
                            UserRight::class,
                        )),
                    ],
                ),
            ],
        );

        $user = $this->make(
            User::class,
            [
                'id' => 1,
            ],
        );

        verify($instance->addRight($user, 'Right'));
    }

    public function testAddRightDuplicate()
    {
        $instance = $this->make(
            UserRightService::class,
            [
                'rightRepository' => $this->make(
                    RightRepository::class,
                    [
                        'getRightByShortName' => Expected::once($this->make(
                            Right::class,
                            [
                                'id' => 2,
                            ],
                        )),
                    ],
                ),
                'userRightRepository' => $this->make(
                    UserRightRepository::class,
                    [
                        'getObject' => Expected::once($this->makeEmpty(
                            UserRight::class,
                        )),
                        'create' => Expected::never(),
                    ],
                ),
            ],
        );

        $user = $this->make(
            User::class,
            [
                'id' => 1,
            ],
        );

        verify($instance->addRight($user, 'Right'));
    }

    public function testDeleteRightUnkown()
    {
        $instance = $this->make(
            UserRightService::class,
            [
                'rightRepository' => $this->make(
                    RightRepository::class,
                    [
                        'getRightByShortName' => Expected::once(null),
                    ],
                ),
                'userRightRepository' => $this->make(
                    UserRightRepository::class,
                    [
                        'getObject' => Expected::never(),
                        'delete' => Expected::never(),
                    ],
                ),
            ],
        );

        $user = $this->make(
            User::class,
        );

        verify(function () use ($instance, $user) {
            $instance->deleteRight($user, 'NoRight');
        })
            ->callableThrows(\InvalidArgumentException::class, 'Right unknown');
    }

    public function testDeleteRightOwned()
    {
        $instance = $this->make(
            UserRightService::class,
            [
                'rightRepository' => $this->make(
                    RightRepository::class,
                    [
                        'getRightByShortName' => Expected::once($this->make(
                            Right::class,
                            [
                                'id' => 2,
                            ],
                        )),
                    ],
                ),
                'userRightRepository' => $this->make(
                    UserRightRepository::class,
                    [
                        'getObject' => Expected::once($this->makeEmpty(
                            UserRight::class,
                        )),
                        'delete' => Expected::once(),
                    ],
                ),
            ],
        );

        $user = $this->make(
            User::class,
            [
                'id' => 1,
            ],
        );

        verify($instance->deleteRight($user, 'Right'));
    }

    public function testDeleteRightNotOwned()
    {
        $instance = $this->make(
            UserRightService::class,
            [
                'rightRepository' => $this->make(
                    RightRepository::class,
                    [
                        'getRightByShortName' => Expected::once($this->make(
                            Right::class,
                            [
                                'id' => 2,
                            ],
                        )),
                    ],
                ),
                'userRightRepository' => $this->make(
                    UserRightRepository::class,
                    [
                        'getObject' => Expected::once(null),
                        'delete' => Expected::never(),
                    ],
                ),
            ],
        );

        $user = $this->make(
            User::class,
            [
                'id' => 1,
            ],
        );

        verify($instance->deleteRight($user, 'Right'));
    }

    public function testHasRightUnkown()
    {
        $instance = $this->make(
            UserRightService::class,
            [
                'rightRepository' => $this->make(
                    RightRepository::class,
                    [
                        'getRightByShortName' => Expected::once(null),
                    ],
                ),
                'userRightRepository' => $this->make(
                    UserRightRepository::class,
                    [
                        'getObject' => Expected::never(),
                    ],
                ),
            ],
        );

        $user = $this->make(
            User::class,
        );

        verify($instance->hasRight($user, 'NoRight'))
            ->equals(false);
    }

    public function testHasRightOwned()
    {
        $instance = $this->make(
            UserRightService::class,
            [
                'rightRepository' => $this->make(
                    RightRepository::class,
                    [
                        'getRightByShortName' => Expected::once($this->make(
                            Right::class,
                            [
                                'id' => 2,
                            ],
                        )),
                    ],
                ),
                'userRightRepository' => $this->make(
                    UserRightRepository::class,
                    [
                        'getObject' => Expected::once($this->makeEmpty(
                            UserRight::class,
                        )),
                    ],
                ),
            ],
        );

        $user = $this->make(
            User::class,
            [
                'id' => 1,
            ],
        );

        verify($instance->hasRight($user, 'Right'))
            ->equals(true);
    }

    public function testHasRightNotOwned()
    {
        $instance = $this->make(
            UserRightService::class,
            [
                'rightRepository' => $this->make(
                    RightRepository::class,
                    [
                        'getRightByShortName' => Expected::once($this->make(
                            Right::class,
                            [
                                'id' => 2,
                            ],
                        )),
                    ],
                ),
                'userRightRepository' => $this->make(
                    UserRightRepository::class,
                    [
                        'getObject' => Expected::once(null),
                    ],
                ),
            ],
        );

        $user = $this->make(
            User::class,
            [
                'id' => 1,
            ],
        );

        verify($instance->hasRight($user, 'Right'))
            ->equals(false);
    }
}
