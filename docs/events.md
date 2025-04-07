# Event System

The WebFramework provides a robust event system that allows for decoupled communication between different parts of your application. This system follows the observer pattern and supports both synchronous and asynchronous event handling.

## Core Components

### EventService

The `EventService` is the central manager for all events in the application. It provides methods to:

- Register events and their listeners
- Dispatch events
- Manage event listeners
- Handle queued event listeners

### Event Interface

Events are simple data containers that implement the `Event` interface. They should contain only the data needed for the event, not the logic:

~~~php
interface Event {}
~~~

### EventListener Interface

Event listeners implement the `EventListener` interface and contain the actual logic for handling events:

~~~php
interface EventListener
{
    public function handle(Event $event): bool;
}
~~~

### QueuedEventListener

The `QueuedEventListener` abstract class provides a base for event listeners that should be processed asynchronously:

~~~php
abstract class QueuedEventListener implements EventListener
{
    protected string $queueName = 'default';

    public function getQueueName(): string
    {
        return $this->queueName;
    }
}
~~~

## Usage

### Creating an Event

An Event should only contain data and no logic. All logic related to an Event should be in a separate EventListener.

~~~php
class UserRegisteredEvent implements Event
{
    public function __construct(
        private int $userId,
        private string $email
    ) {}

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
~~~

### Creating a Synchronous Event Listener

~~~php
/**
 * @implements EventListener<UserRegisteredEvent>
 */
class SendWelcomeEmailListener implements EventListener
{
    public function handle(Event $event): bool
    {
        if (!$event instanceof UserRegisteredEvent) {
            return false;
        }

        // Send welcome email logic here
        return true;
    }
}
~~~

### Creating a Queued Event Listener

~~~php
/**
 * @extends QueuedEventListener<UserRegisteredEvent>
 */
class ProcessUserDataListener extends QueuedEventListener
{
    protected string $queueName = 'user-processing';

    public function handle(Event $event): bool
    {
        if (!$event instanceof UserRegisteredEvent) {
            return false;
        }

        // Heavy processing logic here
        return true;
    }
}
~~~

### Registering Events and Listeners

~~~php
// Register an event with its listeners
$eventService->registerEvent(
    UserRegisteredEvent::class,
    [
        SendWelcomeEmailListener::class,
        ProcessUserDataListener::class,
    ]
);

// Add a new listener to an existing event
$eventService->addListener(
    UserRegisteredEvent::class,
    NewFeatureListener::class
);
~~~

### Dispatching Events

~~~php
$event = new UserRegisteredEvent(123, 'user@example.com');
$eventService->dispatch($event);
~~~

### Queued Events

Queued events will only be handled if you have a QueueWorker active and registered the EventJobHandler to handle EventJobs, e.g. in the definition of the QueueService.

~~~php
$queueService->registerJobHandler(EventJob::class, EventJobHandler::class);
~~~

### Framework Events

WebFramework itself will send out the following events:

- UserLoggedIn
- UserPasswordChanged
- UserVerified
- UserEmailChanged

## Best Practices

1. Keep events focused on a single state change
2. Use descriptive event names that reflect past tense (e.g., `UserRegisteredEvent`)
3. Include only necessary data in events
4. Use queued listeners for time-consuming operations
5. Implement proper error handling in listeners
6. Consider using different queues for different types of listeners
7. Monitor event processing and queue sizes
8. Implement proper logging in event listeners 