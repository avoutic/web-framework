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
<?php

interface Event {}
~~~

### EventListener Interface

Event listeners implement the `EventListener` interface and contain the actual logic for handling events:

~~~php
<?php

interface EventListener
{
    public function handle(Event $event): bool;
}
~~~

### QueuedEventListener

The `QueuedEventListener` abstract class provides a base for event listeners that should be processed asynchronously:

~~~php
<?php

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
<?php

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
<?php

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
<?php

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

Events and their listeners are registered on the `EventService`. Do this in your definitions, with `DI\decorate`. The decorator gets the `EventService` the container created, registers your events on it and returns it:

~~~php
<?php

use WebFramework\Event\EventService;

return [
    EventService::class => DI\decorate(function (EventService $eventService) {
        // Register an event with its listeners
        $eventService->registerEvent(
            UserRegisteredEvent::class,
            [
                SendWelcomeEmailListener::class,
                ProcessUserDataListener::class,
            ]
        );

        return $eventService;
    }),
];
~~~

This way your definitions do not depend on how the `EventService` is constructed. Do not create the `EventService` yourself in a definition: that breaks when its constructor changes.

The same goes for the events of the framework itself, such as `WebFramework\Event\UserLoggedIn`: register them with your listeners to act on them. An event that is dispatched without being registered is ignored.

Two rules to keep in mind:

- `registerEvent()` can be called once per event. It throws an exception when the event is already registered.
- `addListener()` adds a listener to an event that is already registered. It throws an exception when the event is not registered yet.

So when a second definition file, for example a module of your application, wants to listen to an event that is registered elsewhere, it decorates the `EventService` as well and uses `addListener()`:

~~~php
<?php

use WebFramework\Event\EventService;

return [
    EventService::class => DI\decorate(function (EventService $eventService) {
        // Add a new listener to an existing event
        $eventService->addListener(
            UserRegisteredEvent::class,
            NewFeatureListener::class
        );

        return $eventService;
    }),
];
~~~

The decorators run in the order of the definition files, so the file that registers the event has to be listed before the files that add listeners to it.

### Dispatching Events

~~~php
<?php

$event = new UserRegisteredEvent(123, 'user@example.com');
$eventService->dispatch($event);
~~~

## Application Integration

Here are real examples from the WebFramework showing how events are used in practice:

### User Login Events

When a user successfully logs in, the framework dispatches a `UserLoggedIn` event. This happens in `LoginService::completeLogin()`, the single place where a login results in a session:

~~~php
<?php

$this->authenticationService->authenticate($user);
$this->eventService->dispatch(new UserLoggedIn($request, $user));
~~~

Code that needs to log a user in should call `LoginService::completeLogin()` instead of repeating these two calls.

### Password Change Events

When a user changes their password, a `UserPasswordChanged` event is dispatched:

~~~php
<?php

$user->setSolidPassword($newHash);
$this->userRepository->save($user);
$this->eventService->dispatch(new UserPasswordChanged($user));
~~~

### Email Change Events

When a user's email address is changed, a `UserEmailChanged` event is dispatched:

~~~php
<?php

$user->setEmail($email);
$this->userRepository->save($user);
$this->eventService->dispatch(new UserEmailChanged($user));
~~~

### User Verification Events

When a user completes email verification, a `UserVerified` event is dispatched:

~~~php
<?php

$user->setVerified();
$this->userRepository->save($user);
$this->eventService->dispatch(new UserVerified($user));
~~~

### Queue Setup for Asynchronous Events

To use queued event listeners, you must:

1. **Register the EventJobHandler** in your application configuration:

~~~php
<?php

$queueService->registerJobHandler(EventJob::class, EventJobHandler::class);
~~~

2. **Run queue workers** to process the jobs:

~~~bash
vendor/bin/framework queue:worker
~~~

3. **Configure queue names** in your listeners:

~~~php
<?php

class HeavyProcessingListener extends QueuedEventListener
{
    protected string $queueName = 'heavy-processing';
}
~~~

Queued events will only be handled if you have a QueueWorker active and registered the EventJobHandler to handle EventJobs.

### Framework Events

WebFramework itself will send out the following events:

- UserLoggedIn
- UserPasswordChanged
- UserVerified
- UserEmailChanged

## Error Handling & Debugging

### Unregistered Events

If you dispatch an event that hasn't been registered, the EventService will log a debug message and silently continue:

~~~php
// This will log: "Cannot dispatch unregistered event"
$eventService->dispatch(new UnregisteredEvent());
~~~

### Listener Errors

If a listener throws an exception, it will bubble up and stop processing of subsequent listeners. Wrap listener logic in try-catch blocks for graceful error handling:

~~~php
<?php

public function handle(Event $event): bool
{
    try {
        // Your event handling logic
        return true;
    } catch (\Exception $e) {
        $this->logger->error('Event handling failed', ['exception' => $e]);
        return false;
    }
}
~~~

### Debugging

Enable debug logging to see event dispatch activity:
- Synchronous events: "Dispatching non-queued event"
- Asynchronous events: "Dispatching queued event"

## Performance Considerations

### Synchronous vs Asynchronous Processing

- Use synchronous listeners for lightweight operations that must complete immediately
- Use `QueuedEventListener` for heavy operations like email sending, file processing, or external API calls
- Synchronous listeners block the request until completion
- Queued listeners are processed asynchronously by queue workers

### Queue Configuration

For queued events, ensure you have:
1. Queue workers running to process jobs
2. EventJobHandler registered with QueueService
3. Appropriate queue names for different types of processing

## Testing Event-Driven Code

### Testing Event Dispatch

~~~php
<?php

// Mock the EventService to verify events are dispatched
$eventService = $this->createMock(EventService::class);
$eventService->expects($this->once())
    ->method('dispatch')
    ->with($this->isInstanceOf(UserLoggedIn::class));
~~~

### Testing Event Listeners

~~~php
<?php

public function testUserLoggedInListener()
{
    $user = new User();
    $event = new UserLoggedIn($user);
    $listener = new MyEventListener();
    
    $result = $listener->handle($event);
    
    $this->assertTrue($result);
}
~~~

## Best Practices

1. Keep events focused on a single state change
2. Use descriptive event names that reflect past tense (e.g., `UserRegisteredEvent`)
3. Include only necessary data in events
4. Use queued listeners for time-consuming operations
5. Implement proper error handling in listeners
6. Consider using different queues for different types of listeners
7. Monitor event processing and queue sizes
8. Implement proper logging in event listeners   
