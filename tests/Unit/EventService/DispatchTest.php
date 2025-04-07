<?php

namespace Tests\Unit\EventService;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Tests\Support\TestEvent;
use Tests\Support\TestEventListener;
use Tests\Support\TestEventListener2;
use Tests\Support\TestQueuedEventListener;
use WebFramework\Event\EventService;
use WebFramework\Queue\QueueService;

/**
 * @internal
 *
 * @coversNothing
 */
final class DispatchTest extends Unit
{
    public function testDispatchSingleListener()
    {
        $testListener = $this->makeEmpty(
            TestEventListener::class,
            [
                'handle' => Expected::once(true),
            ],
        );

        $eventService = $this->make(
            EventService::class,
            [
                'getListenerByClass' => Expected::once($testListener),
            ],
        );

        $testEvent = $this->makeEmpty(TestEvent::class);

        $eventService->registerEvent(get_class($testEvent), [get_class($testListener)]);

        $eventService->dispatch($testEvent);
    }

    public function testDispatchMultipleListeners()
    {
        $testListener1 = $this->makeEmpty(
            TestEventListener::class,
            [
                'handle' => Expected::once(true),
            ],
        );

        $testListener2 = $this->makeEmpty(
            TestEventListener2::class,
            [
                'handle' => Expected::once(true),
            ],
        );

        $eventService = $this->make(
            EventService::class,
            [
                'getListenerByClass' => Expected::exactly(
                    2,
                    function ($listenerClass) use ($testListener1, $testListener2) {
                        if ($listenerClass === get_class($testListener1))
                        {
                            return $testListener1;
                        }

                        if ($listenerClass === get_class($testListener2))
                        {
                            return $testListener2;
                        }

                        return null;
                    },
                ),
            ],
        );

        $testEvent = $this->makeEmpty(TestEvent::class);

        $eventService->registerEvent(get_class($testEvent), [get_class($testListener1), get_class($testListener2)]);

        $eventService->dispatch($testEvent);
    }

    public function testDispatchQueuedListener()
    {
        $testListener = $this->makeEmpty(
            TestQueuedEventListener::class,
            [
                'handle' => Expected::never(),
            ],
        );

        $eventService = $this->make(
            EventService::class,
            [
                'queueService' => $this->makeEmpty(
                    QueueService::class,
                    [
                        'dispatch' => Expected::once(),
                    ],
                ),
                'getListenerByClass' => Expected::once($testListener),
            ],
        );

        $testEvent = $this->makeEmpty(TestEvent::class);

        $eventService->registerEvent(get_class($testEvent), [get_class($testListener)]);

        $eventService->dispatch($testEvent);
    }
}
