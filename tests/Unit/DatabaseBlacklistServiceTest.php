<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use WebFramework\Repository\BlacklistEntryRepository;
use WebFramework\Repository\RepositoryQuery;
use WebFramework\Security\DatabaseBlacklistService;

/**
 * @internal
 *
 * @covers \WebFramework\Security\DatabaseBlacklistService
 */
final class DatabaseBlacklistServiceTest extends Unit
{
    public function testIsBlacklistedNoTotal()
    {
        $instance = $this->construct(
            DatabaseBlacklistService::class,
            [
                $this->makeEmpty(
                    BlacklistEntryRepository::class,
                    [
                        'query' => $this->makeEmpty(
                            RepositoryQuery::class,
                            [
                                'where' => $this->makeEmpty(
                                    RepositoryQuery::class,
                                    [
                                        'sum' => 9,
                                    ]
                                ),
                            ]
                        ),
                    ]
                ),
                $this->makeEmpty(LoggerInterface::class),
                'storePeriod' => 1000,
                'triggerPeriod' => 100,
                'threshold' => 10,
            ]
        );

        verify($instance->isBlacklisted('ip1', null))
            ->equals(false)
        ;
    }

    public function testIsBlacklistedOverThreshold()
    {
        $instance = $this->construct(
            DatabaseBlacklistService::class,
            [
                $this->make(
                    BlacklistEntryRepository::class,
                    [
                        'query' => $this->makeEmpty(
                            RepositoryQuery::class,
                            [
                                'where' => $this->makeEmpty(
                                    RepositoryQuery::class,
                                    [
                                        'sum' => 11,
                                    ]
                                ),
                            ]
                        ),
                    ],
                ),
                $this->makeEmpty(LoggerInterface::class),
                'storePeriod' => 1000,
                'triggerPeriod' => 100,
                'threshold' => 10,
            ]
        );

        verify($instance->isBlacklisted('ip1', null))
            ->equals(true)
        ;
    }
}
