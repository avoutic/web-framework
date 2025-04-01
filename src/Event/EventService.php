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

class EventService
{
    /** @var array<string, EventData> */
    private array $registry = [];

    /**
     * @param Event                         $event     Event to register
     * @param array<callable|EventListener> $listeners Listeners for this event
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
            if ($listener instanceof EventListener)
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
     * @param Event                  $event    Event to register
     * @param callable|EventListener $listener Listener to add
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
     * @param Event                         $event     Event to register
     * @param array<callable|EventListener> $listeners Listeners for this event
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
}
