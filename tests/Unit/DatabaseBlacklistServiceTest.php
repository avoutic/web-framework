<?php

namespace Tests\Unit;

use WebFramework\Core\Database;
use WebFramework\Core\DatabaseResultWrapper;
use WebFramework\Core\Security\DatabaseBlacklistService;

/**
 * @internal
 *
 * @coversNothing
 */
final class DatabaseBlacklistServiceTest extends \Codeception\Test\Unit
{
    public function testIsBlacklistedNoTotal()
    {
        $instance = $this->construct(
            DatabaseBlacklistService::class,
            [
                $this->makeEmpty(
                    Database::class,
                    [
                        'query' => $this->makeEmpty(
                            DatabaseResultWrapper::class,
                            [
                                'fields' => [
                                    'total' => 0,
                                ],
                            ]
                        ),
                    ],
                ),
                [
                    'trigger_period' => 100,
                    'threshold' => 10,
                ],
            ]
        );

        verify($instance->is_blacklisted('ip1', null))
            ->equals(false);
    }

    public function testIsBlacklistedOverThreshold()
    {
        $instance = $this->construct(
            DatabaseBlacklistService::class,
            [
                $this->makeEmpty(
                    Database::class,
                    [
                        'query' => $this->makeEmpty(
                            DatabaseResultWrapper::class,
                            [
                                'fields' => [
                                    'total' => 20,
                                ],
                            ]
                        ),
                    ],
                ),
                [
                    'trigger_period' => 100,
                    'threshold' => 10,
                ],
            ]
        );

        verify($instance->is_blacklisted('ip1', null))
            ->equals(true);
    }
}
