<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Event;

use Psr\Container\ContainerInterface as Container;
use WebFramework\Job\EventJob;
use WebFramework\Queue\QueueService;

class EventService
{
    /** @var array<string, EventData> */
    private array $registry = [];

    public function __construct(
        private Container $container,
        private QueueService $queueService,
    ) {}

    /**
     * @param class-string<Event>                       $eventClass Event to register
     * @param array<class-string<EventListener<Event>>> $listeners  Listeners for this event
     */
    public function registerEvent(
        string $eventClass,
        array $listeners,
    ): void {
        if (isset($this->registry[$eventClass]))
        {
            throw new \RuntimeException('Event '.$eventClass.' already registered');
        }

        $eventData = new EventData();

        $eventData->listeners = $listeners;

        $this->setEventData($eventClass, $eventData);
    }

    /**
     * @param class-string<Event> $eventClass
     */
    private function getEventData(string $eventClass): ?EventData
    {
        return $this->registry[$eventClass] ?? null;
    }

    /**
     * @param class-string<Event> $eventClass
     */
    private function setEventData(string $eventClass, EventData $eventData): void
    {
        $this->registry[$eventClass] = $eventData;
    }

    public function dispatch(Event $event): void
    {
        $eventData = $this->getEventData(get_class($event));

        if (!$eventData)
        {
            return;
        }

        foreach ($eventData->listeners as $listenerClass)
        {
            $listener = $this->getListenerByClass($listenerClass);

            if ($listener instanceof QueuedEventListener)
            {
                $job = new EventJob(get_class($listener), $event);
                $this->queueService->dispatch($job, $listener->getQueueName());
            }
            else
            {
                $listener->handle($event);
            }
        }
    }

    /**
     * @param class-string<Event>                $eventClass    Event to register
     * @param class-string<EventListener<Event>> $listenerClass Listener to add
     */
    public function addListener(string $eventClass, string $listenerClass): void
    {
        $eventData = $this->getEventData($eventClass);

        if (!$eventData)
        {
            throw new \RuntimeException('Event '.$eventClass.' not found');
        }

        $eventData->listeners[] = $listenerClass;

        $this->setEventData($eventClass, $eventData);
    }

    /**
     * @param class-string<Event>                       $eventClass Event to register
     * @param array<class-string<EventListener<Event>>> $listeners  Listeners for this event
     */
    public function setListeners(string $eventClass, array $listeners): void
    {
        $eventData = $this->getEventData($eventClass);

        if (!$eventData)
        {
            throw new \RuntimeException('Event '.$eventClass.' not found');
        }

        $eventData->listeners = $listeners;

        $this->setEventData($eventClass, $eventData);
    }

    /**
     * @param class-string<Event> $eventClass
     *
     * @return array<class-string<EventListener<Event>>>
     */
    public function getListeners(string $eventClass): array
    {
        $eventData = $this->getEventData($eventClass);

        return $eventData->listeners ?? [];
    }

    /**
     * @return EventListener<Event>
     */
    public function getListenerByClass(string $listenerClass): EventListener
    {
        $listener = $this->container->get($listenerClass);

        if (!$listener)
        {
            throw new \RuntimeException('Listener '.$listenerClass.' not found');
        }

        if (!$listener instanceof EventListener)
        {
            throw new \RuntimeException('Listener '.$listenerClass.' is not an EventListener');
        }

        return $listener;
    }
}
