<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use WebFramework\Core\Database;
use WebFramework\Core\DatabaseResultWrapper;
use WebFramework\Repository\BlacklistEntryRepository;
use WebFramework\Security\DatabaseBlacklistService;

/**
 * @internal
 *
 * @coversNothing
 */
final class DatabaseBlacklistServiceTest extends Unit
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
                $this->make(
                    BlacklistEntryRepository::class,
                    [],
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
                $this->make(
                    BlacklistEntryRepository::class,
                    [],
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
