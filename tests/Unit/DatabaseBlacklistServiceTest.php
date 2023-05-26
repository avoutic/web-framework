<?php

namespace Tests\Unit;

use WebFramework\Core\Database;
use WebFramework\Core\DatabaseResultWrapper;
use WebFramework\Security\DatabaseBlacklistService;

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
                'storePeriod' => 1000,
                'triggerPeriod' => 100,
                'threshold' => 10,
            ]
        );

        verify($instance->isBlacklisted('ip1', null))
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
                'storePeriod' => 1000,
                'triggerPeriod' => 100,
                'threshold' => 10,
            ]
        );

        verify($instance->isBlacklisted('ip1', null))
            ->equals(true);
    }
}
