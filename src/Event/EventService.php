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
     * @param Event                                $event     Event to register
     * @param array<callable|EventListener<Event>> $listeners Listeners for this event
     */
    public function registerEvent(
        Event $event,
        array $listeners,
    ): void {
        $eventData = new EventData();

        $eventData->listeners = $listeners;

        $this->setEventData($event, $eventData);
    }

    private function getEventData(Event $event): ?EventData
    {
        $className = $event::class;

        return $this->registry[$className] ?? null;
    }

    private function setEventData(Event $event, EventData $eventData): void
    {
        $className = $event::class;

        $this->registry[$className] = $eventData;
    }

    public function dispatch(Event $event): void
    {
        $eventData = $this->getEventData($event);

        if (!$eventData)
        {
            return;
        }

        foreach ($eventData->listeners as $listener)
        {
            if ($listener instanceof QueuedEventListener)
            {
                $job = new EventJob(get_class($listener), $event);
                $this->queueService->dispatch($job, $listener->getQueueName());
            }
            elseif ($listener instanceof EventListener)
            {
                $listener->handle($event);
            }
            elseif (is_callable($listener))
            {
                $listener($event);
            }
            else
            {
                throw new \RuntimeException('Unknown listener type');
            }
        }
    }

    /**
     * @param Event                         $event    Event to register
     * @param callable|EventListener<Event> $listener Listener to add
     */
    public function addListener(Event $event, callable|EventListener $listener): void
    {
        $eventData = $this->getEventData($event);

        if (!$eventData)
        {
            throw new \RuntimeException('Event '.$event::class.' not found');
        }

        $eventData->listeners[] = $listener;

        $this->setEventData($event, $eventData);
    }

    /**
     * @param Event                                $event     Event to register
     * @param array<callable|EventListener<Event>> $listeners Listeners for this event
     */
    public function setListeners(Event $event, array $listeners): void
    {
        $eventData = $this->getEventData($event);

        if (!$eventData)
        {
            throw new \RuntimeException('Event '.$event::class.' not found');
        }

        $eventData->listeners = $listeners;

        $this->setEventData($event, $eventData);
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
