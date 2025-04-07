# Queueing System

The WebFramework provides a flexible queueing system for handling background jobs. This system allows you to process tasks asynchronously, improving application responsiveness.

## Core Components

### QueueService

The `QueueService` is the central manager for all queues in the application. It provides methods to:

- Register queues
- Dispatch jobs
- Get queue statistics
- Register job handlers
- Process jobs

### Queue Interface

The `Queue` interface defines the contract that all queue implementations must follow:

~~~php
interface Queue
{
    public function dispatch(Job $job, int $delay = 0): void;
    public function count(): int;
    public function popJob(): ?Job;
    public function getName(): string;
    public function clear(): void;
}
~~~

### Job Interface

Jobs are simple data containers that implement the `Job` interface. They should contain only the data needed to perform the task, not the logic.

### JobHandler Interface

Job handlers implement the `JobHandler` interface and contain the actual logic for processing jobs:

~~~php
interface JobHandler
{
    public function handle(Job $job): bool;
}
~~~

## Usage

### Registering a Queue

~~~php
$queueService->register('email', $emailQueue, true); // true makes it the default queue
~~~

### Creating a Job

~~~php
class SendEmailJob implements Job
{
    public function __construct(
        private string $to,
        private string $subject,
        private string $body
    ) {}
}
~~~

### Creating a Job Handler

~~~php
/**
 * @implements JobHandler<SendMailJob>
 */
class SendEmailJobHandler implements JobHandler
{
    public function handle(Job $job): bool
    {
        if (!$job instanceof SendEmailJob) {
            return false;
        }

        // Send email logic here
        return true;
    }
}
~~~

### Registering a Job Handler

~~~php
$queueService->registerJobHandler(SendEmailJob::class, SendEmailJobHandler::class);
~~~

### Dispatching a Job

~~~php
$job = new SendEmailJob('user@example.com', 'Hello', 'Welcome!');
$queueService->dispatch($job); // Uses default queue
$queueService->dispatch($job, 'email', 60); // Uses 'email' queue with 60 second delay
~~~

### Running a Queue Worker

Queue workers can be started using the console command:

~~~bash
php console.php queue:worker email --max-jobs=100 --max-runtime=3600
~~~

Options:
- `--max-jobs`: Maximum number of jobs to process before stopping
- `--max-runtime`: Maximum runtime in seconds before stopping

## Built-in Implementations

### MemoryQueue

The `MemoryQueue` is a simple in-memory queue implementation suitable for development and testing. It is not persistent and will lose all data when the server restarts.

### RedisQueue

The `RedisQueue` is implemented in the web-framework-redis module. Persistency depends on your configuration of Redis, but it's shareable between multiple instantiations and / or servers.

## Best Practices

1. Keep jobs small and focused on a single task
2. Use job handlers for the actual processing logic
3. Implement proper error handling in job handlers
4. Use appropriate queue names for different types of jobs
5. Consider using delays for non-urgent tasks
6. Monitor queue sizes and processing times
7. Implement proper logging in job handlers 