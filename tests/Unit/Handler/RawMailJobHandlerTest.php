<?php

namespace Tests\Unit\Handler;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Tests\Support\StaticArrayJob;
use WebFramework\Exception\InvalidJobException;
use WebFramework\Exception\JobExecutionException;
use WebFramework\Handler\RawMailJobHandler;
use WebFramework\Job\RawMailJob;
use WebFramework\Mail\MailBackend;

/**
 * @internal
 *
 * @covers \WebFramework\Handler\RawMailJobHandler
 */
final class RawMailJobHandlerTest extends Unit
{
    public function testHandleValidRawMailJob()
    {
        $job = new RawMailJob(
            'sender@example.com',
            'recipient@example.com',
            'Test Subject',
            'Test message body'
        );
        $job->setJobId('job-123');

        $mailBackend = $this->makeEmpty(MailBackend::class, [
            'sendRawMail' => Expected::once(function ($from, $recipient, $title, $message) {
                verify($from)->equals('sender@example.com');
                verify($recipient)->equals('recipient@example.com');
                verify($title)->equals('Test Subject');
                verify($message)->equals('Test message body');

                return true;
            }),
        ]);

        $logger = $this->makeEmpty(LoggerInterface::class);

        $handler = new RawMailJobHandler($mailBackend, $logger);
        $handler->handle($job);
    }

    public function testHandleThrowsExceptionForInvalidJobType()
    {
        $invalidJob = new StaticArrayJob('test');
        $invalidJob->setJobId('job-123');

        $mailBackend = $this->makeEmpty(MailBackend::class);
        $logger = $this->makeEmpty(LoggerInterface::class, [
            'error' => Expected::once(),
        ]);

        $handler = new RawMailJobHandler($mailBackend, $logger);

        verify(function () use ($handler, $invalidJob) {
            $handler->handle($invalidJob);
        })->callableThrows(InvalidJobException::class);
    }

    public function testHandleThrowsExceptionWhenMailBackendReturnsErrorString()
    {
        $job = new RawMailJob(
            'sender@example.com',
            'recipient@example.com',
            'Test Subject',
            'Test message body'
        );
        $job->setJobId('job-123');

        $mailBackend = $this->makeEmpty(MailBackend::class, [
            'sendRawMail' => Expected::once('SMTP connection failed'),
        ]);

        $logger = $this->makeEmpty(LoggerInterface::class, [
            'error' => Expected::once(),
        ]);

        $handler = new RawMailJobHandler($mailBackend, $logger);

        verify(function () use ($handler, $job) {
            $handler->handle($job);
        })->callableThrows(JobExecutionException::class);
    }

    public function testHandleThrowsExceptionWhenMailBackendReturnsFalse()
    {
        $job = new RawMailJob(
            'sender@example.com',
            'recipient@example.com',
            'Test Subject',
            'Test message body'
        );
        $job->setJobId('job-123');

        $mailBackend = $this->makeEmpty(MailBackend::class, [
            'sendRawMail' => Expected::once(false),
        ]);

        $logger = $this->makeEmpty(LoggerInterface::class, [
            'error' => Expected::once(),
        ]);

        $handler = new RawMailJobHandler($mailBackend, $logger);

        verify(function () use ($handler, $job) {
            $handler->handle($job);
        })->callableThrows(JobExecutionException::class);
    }
}
