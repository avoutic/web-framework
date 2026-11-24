# Queueing System

The WebFramework provides a flexible queueing system for handling background jobs. This system allows you to process tasks asynchronously, improving application responsiveness.

## Core Components

### QueueService

The `QueueService` is the central manager for all queues in the application. It provides methods to:

- Register queues
- Dispatch jobs
- Get queue job counts
- Register job handlers
- Retrieve job handlers for processing

### Queue Interface

The `Queue` interface defines the contract that all queue implementations must follow:

~~~php
<?php

interface Queue
{
    public function dispatch(Job $job, int $delay = 0, int $maxAttempts = 3): void;
    public function count(): int;
    public function popJob(): ?Job;
    public function getName(): string;
    public function clear(): void;
    public function markJobCompleted(Job $job): void;
    public function markJobFailed(Job $job, ?\Throwable $exception = null): void;
}
~~~

### Job Interface

Jobs are simple data containers that implement the `Job` interface. They should contain only the data needed to perform the task, not the logic.

All jobs must implement the following methods:

~~~php
<?php

interface Job
{
    public function getJobId(): string;
    public function setJobId(string $jobId): void;
    public function getJobName(): string;
}
~~~

The `QueueService` automatically sets the job ID when dispatching. The job name should uniquely identify the type of job.

### JobHandler Interface

Job handlers implement the `JobHandler` interface and contain the actual logic for processing jobs:

~~~php
<?php

interface JobHandler
{
    public function handle(Job $job): void;
}
~~~

The `handle()` method should throw an exception if the job fails to execute. If no exception is thrown, the job is considered successfully processed and will be removed from the queue.

Common exceptions to throw:
- `InvalidJobException` - when the job type doesn't match the handler's expected type
- `JobDataException` - when required job data is missing
- `JobExecutionException` - when the job execution fails (e.g., mail delivery failure)

## Usage

### Registering a Queue

~~~php
<?php

$queueService->register('email', $emailQueue, true); // true makes it the default queue
~~~

### Creating a Job

You can either use public readonly properties or private properties with public getters.

~~~php
<?php

class SendEmailJob implements Job
{
    private string $jobId = '';

    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $body
    ) {}

    public function getJobId(): string
    {
        return $this->jobId;
    }

    public function setJobId(string $jobId): void
    {
        $this->jobId = $jobId;
    }

    public function getJobName(): string
    {
        return 'SendEmailJob';
    }
}
~~~

### Creating a Job Handler

~~~php
<?php

use WebFramework\Exception\InvalidJobException;
use WebFramework\Exception\JobExecutionException;

/**
 * @implements JobHandler<Job>
 */
class SendEmailJobHandler implements JobHandler
{
    public function handle(Job $job): void
    {
        if (!$job instanceof SendEmailJob) {
            /** @var class-string $jobClass */
            $jobClass = get_class($job);
            throw new InvalidJobException(SendEmailJob::class, $jobClass);
        }

        // Send email logic here
        // If sending fails, throw JobExecutionException
        if (!$this->sendEmail($job)) {
            throw new JobExecutionException($job->getJobName(), 'Failed to send email');
        }
    }
}
~~~

### Registering a Job Handler

~~~php
<?php

$queueService->registerJobHandler(SendEmailJob::class, SendEmailJobHandler::class);
~~~

### Dispatching a Job

~~~php
<?php

$job = new SendEmailJob('user@example.com', 'Hello', 'Welcome!');
$queueService->dispatch($job); // Uses default queue
$queueService->dispatch($job, 'email', 60); // Uses 'email' queue with 60 second delay
$queueService->dispatch($job, 'email', 0, 5); // Uses 'email' queue with 5 max retry attempts
~~~

The `dispatch()` method signature is:
- `dispatch(Job $job, string $queue = 'default', int $delay = 0, int $maxAttempts = 3)`

### Running a Queue Worker

Queue workers can be started using the console command:

~~~bash
vendor/bin/framework queue:worker email --max-jobs=100 --max-runtime=3600
~~~

Options:
- `--max-jobs`: Maximum number of jobs to process before stopping
- `--max-runtime`: Maximum runtime in seconds before stopping

The worker will:
- Process jobs from the specified queue
- Automatically handle job failures and retries
- Sleep for 1 second when no jobs are available
- Stop when max-jobs or max-runtime is reached, also when the queue is empty
- Roll back a database transaction that a job handler left open, before continuing with the next job. A handler that returns with a transaction still open counts as failed. Use `Database::transaction()` in your handlers, so a transaction is always committed or rolled back

### Job Events

The worker dispatches an event after every job it handled:

- `WebFramework\Event\JobCompleted`, with the job, the name of the queue and the duration in seconds
- `WebFramework\Event\JobFailed`, with the same, and the class and message of the exception. It is dispatched for every attempt that fails, also when the job will be tried again

Register a listener for them to record metrics or to be told about failures, see [Events](events.md). An event that has no listeners costs nothing.

A listener for these events can be a queued listener. The job of such a listener does not lead to a job event itself, as that would never end. A listener that fails is logged, and does not change what happened to the job.

### Queue Statistics

For the database queue, `WebFramework\Repository\QueueJobRepository` tells you how a queue is doing:

- `countJobsInQueue($queueName)`: the number of jobs that are waiting
- `countDeadLetterJobs($queueName)`: the number of jobs that ran out of attempts, in the dead letter queue `<name>-failed`
- `getOldestPendingTimestamp($queueName)`: when the oldest job that is still waiting was created, or `null` if none is waiting

Use them in a task that watches your queues.

## Built-in Implementations

### MemoryQueue

The `MemoryQueue` is a simple in-memory queue implementation suitable for development and testing. It is not persistent and will lose all data when the server restarts. It does not work across multiple processes or servers.

### DatabaseQueue

The `DatabaseQueue` uses a database table to store jobs, providing persistence and the ability to share jobs across multiple processes and servers. Failed jobs are automatically moved to a dead-letter queue (named `{queueName}-failed`) after exceeding the maximum retry attempts. The queue implements exponential backoff for retries.

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