<?php

namespace Tests\Unit\EventService;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Container\ContainerInterface as Container;
use Tests\Support\TestEvent;
use Tests\Support\TestEventListener;
use Tests\Support\TestEventListener2;
use Tests\Support\TestQueuedEventListener;
use WebFramework\Event\EventService;

/**
 * @internal
 *
 * @covers \WebFramework\Event\EventService
 */
final class RegisterTest extends Unit
{
    public function testNoDefaultListeners()
    {
        $eventService = $this->make(EventService::class);

        $listeners = $eventService->getListeners(TestEvent::class);

        verify($listeners)->empty();
    }

    public function testRegisterListener()
    {
        $eventService = $this->make(EventService::class);

        $eventService->registerEvent(TestEvent::class, [
            TestEventListener::class,
        ]);

        $listeners = $eventService->getListeners(TestEvent::class);

        verify($listeners)->equals([TestEventListener::class]);
    }

    public function testAlreadyRegistered()
    {
        $eventService = $this->make(EventService::class);

        $eventService->registerEvent(TestEvent::class, [TestEventListener::class]);

        verify(function () use ($eventService) {
            $eventService->registerEvent(TestEvent::class, [TestEventListener::class]);
        })->callableThrows(\RuntimeException::class, 'Event '.TestEvent::class.' already registered');
    }

    public function testRegisterMultipleListeners()
    {
        $eventService = $this->make(EventService::class);

        $eventService->registerEvent(TestEvent::class, [
            TestEventListener::class,
            TestQueuedEventListener::class,
        ]);

        $listeners = $eventService->getListeners(TestEvent::class);

        verify($listeners)->equals([
            TestEventListener::class,
            TestQueuedEventListener::class,
        ]);
    }

    public function testAddListener()
    {
        $eventService = $this->make(EventService::class);

        $eventService->registerEvent(TestEvent::class, [TestEventListener::class]);

        $eventService->addListener(TestEvent::class, TestEventListener2::class);

        $listeners = $eventService->getListeners(TestEvent::class);

        verify($listeners)->equals([
            TestEventListener::class,
            TestEventListener2::class,
        ]);
    }

    public function testAddListenerToNonExistentEvent()
    {
        $eventService = $this->make(EventService::class);

        verify(function () use ($eventService) {
            $eventService->addListener(TestEvent::class, TestEventListener::class);
        })->callableThrows(\RuntimeException::class, 'Event '.TestEvent::class.' not found');
    }

    public function testReplaceListeners()
    {
        $eventService = $this->make(EventService::class);

        $eventService->registerEvent(TestEvent::class, [TestEventListener::class]);

        $eventService->setListeners(TestEvent::class, [TestEventListener2::class]);

        $listeners = $eventService->getListeners(TestEvent::class);

        verify($listeners)->equals([TestEventListener2::class]);
    }

    public function testReplaceListenersWithNonExistentEvent()
    {
        $eventService = $this->make(EventService::class);

        verify(function () use ($eventService) {
            $eventService->setListeners(TestEvent::class, [TestEventListener2::class]);
        })->callableThrows(\RuntimeException::class, 'Event '.TestEvent::class.' not found');
    }

    public function testGetListenerByClass()
    {
        $eventService = $this->make(
            EventService::class,
            [
                'container' => $this->makeEmpty(
                    Container::class,
                    [
                        'get' => Expected::once($this->makeEmpty(TestEventListener::class)),
                    ],
                ),
            ],
        );

        $eventService->registerEvent(TestEvent::class, [TestEventListener::class]);
        $listener = $eventService->getListenerByClass(TestEventListener::class);

        verify($listener)->instanceOf(TestEventListener::class);
    }

    public function testGetListenerByClassWithNonExistentListener()
    {
        $eventService = $this->make(
            EventService::class,
            [
                'container' => $this->makeEmpty(
                    Container::class,
                    [
                        'get' => Expected::once(null),
                    ],
                ),
            ],
        );

        verify(function () use ($eventService) {
            $eventService->getListenerByClass(TestEventListener::class);
        })->callableThrows(\RuntimeException::class, 'Listener '.TestEventListener::class.' not found');
    }

    public function testGetListenerByClassWithNonEventListener()
    {
        $eventService = $this->make(
            EventService::class,
            [
                'container' => $this->makeEmpty(
                    Container::class,
                    [
                        'get' => Expected::once($this->makeEmpty(TestEvent::class)),
                    ],
                ),
            ],
        );

        $eventService->registerEvent(TestEvent::class, [TestEvent::class]);

        verify(function () use ($eventService) {
            $eventService->getListenerByClass(TestEvent::class);
        })->callableThrows(\RuntimeException::class, 'Listener '.TestEvent::class.' is not an EventListener');
    }
}
