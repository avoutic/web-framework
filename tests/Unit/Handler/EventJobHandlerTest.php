<?php

namespace Tests\Unit\Handler;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Tests\Support\StaticArrayJob;
use Tests\Support\TestEvent;
use Tests\Support\TestEventListener;
use WebFramework\Event\Event;
use WebFramework\Event\EventService;
use WebFramework\Exception\InvalidJobException;
use WebFramework\Handler\EventJobHandler;
use WebFramework\Job\EventJob;

/**
 * @internal
 *
 * @covers \WebFramework\Handler\EventJobHandler
 */
final class EventJobHandlerTest extends Unit
{
    public function testHandleValidEventJob()
    {
        $event = new TestEvent('test-event');
        $job = new EventJob(TestEventListener::class, $event);
        $job->setJobId('job-123');

        $listener = $this->makeEmpty(TestEventListener::class, [
            'handle' => Expected::once(function (Event $receivedEvent) use ($event) {
                verify($receivedEvent)->equals($event);

                return true;
            }),
        ]);

        $eventService = $this->makeEmpty(EventService::class, [
            'getListenerByClass' => Expected::once(function ($listenerClass) use ($listener) {
                verify($listenerClass)->equals(TestEventListener::class);

                return $listener;
            }),
        ]);

        $logger = $this->makeEmpty(LoggerInterface::class, [
            'debug' => Expected::once(),
        ]);

        $handler = new EventJobHandler($eventService, $logger);
        $handler->handle($job);
    }

    public function testHandleThrowsExceptionForInvalidJobType()
    {
        $invalidJob = new StaticArrayJob('test');
        $invalidJob->setJobId('job-123');

        $eventService = $this->makeEmpty(EventService::class);
        $logger = $this->makeEmpty(LoggerInterface::class, [
            'error' => Expected::once(),
        ]);

        $handler = new EventJobHandler($eventService, $logger);

        verify(function () use ($handler, $invalidJob) {
            $handler->handle($invalidJob);
        })->callableThrows(InvalidJobException::class);
    }
}
