<?php

namespace Tests\Unit\Queue;

use Codeception\Test\Unit;
use Psr\Container\ContainerInterface as Container;
use Psr\Log\LoggerInterface;
use Tests\Support\InvalidJobHandler;
use Tests\Support\StaticArrayJob;
use Tests\Support\StaticArrayJobHandler;
use WebFramework\Queue\QueueService;
use WebFramework\Support\UuidProvider;

/**
 * @internal
 *
 * @coversNothing
 */
final class HandlerTest extends Unit
{
    public function testRegisterJobHandler()
    {
        $instance = $this->construct(
            QueueService::class,
            [
                $this->makeEmpty(
                    Container::class,
                    [
                        'get' => function (string $jobClass) {
                            if ($jobClass === StaticArrayJobHandler::class)
                            {
                                return new StaticArrayJobHandler();
                            }

                            return null;
                        },
                    ],
                ),
                $this->makeEmpty(LoggerInterface::class),
                $this->makeEmpty(UuidProvider::class),
            ],
        );

        $instance->registerJobHandler(StaticArrayJob::class, StaticArrayJobHandler::class);

        verify($instance->getJobHandler(new StaticArrayJob('value-1')))
            ->equals(new StaticArrayJobHandler())
        ;
    }

    public function testOverrideJobHandler()
    {
        $instance = $this->make(
            QueueService::class,
            [
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ]
        );

        $instance->registerJobHandler(StaticArrayJob::class, StaticArrayJobHandler::class);

        verify(function () use ($instance) {
            $instance->registerJobHandler(StaticArrayJob::class, StaticArrayJobHandler::class);
        })
            ->callableThrows(\RuntimeException::class, "Handler for '".StaticArrayJob::class."' is already registered")
        ;
    }

    public function testGetJobHandlerWithUnknownJob()
    {
        $instance = $this->make(
            QueueService::class,
            [
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ]
        );

        verify(function () use ($instance) {
            $instance->getJobHandler(new StaticArrayJob('value-1'));
        })
            ->callableThrows(\RuntimeException::class, "No handler registered for '".StaticArrayJob::class."'")
        ;
    }

    public function testGetJobHandlerWithInvalidHandler()
    {
        $instance = $this->make(
            QueueService::class,
            [
                'container' => $this->makeEmpty(
                    Container::class,
                    [
                        'get' => function (string $jobHandlerClass) {
                            if ($jobHandlerClass === InvalidJobHandler::class)
                            {
                                return new InvalidJobHandler();
                            }

                            return null;
                        },
                    ],
                ),
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $instance->registerJobHandler(StaticArrayJob::class, InvalidJobHandler::class);

        verify(function () use ($instance) {
            $instance->getJobHandler(new StaticArrayJob('value-1'));
        })
            ->callableThrows(\RuntimeException::class, "Handler for '".StaticArrayJob::class."' is not a valid job handler")
        ;
    }
}
