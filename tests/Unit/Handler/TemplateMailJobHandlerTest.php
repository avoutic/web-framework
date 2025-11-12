<?php

namespace Tests\Unit\Handler;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Tests\Support\StaticArrayJob;
use WebFramework\Exception\InvalidJobException;
use WebFramework\Exception\JobDataException;
use WebFramework\Exception\JobExecutionException;
use WebFramework\Handler\TemplateMailJobHandler;
use WebFramework\Job\TemplateMailJob;
use WebFramework\Mail\MailBackend;

/**
 * @internal
 *
 * @covers \WebFramework\Handler\TemplateMailJobHandler
 */
final class TemplateMailJobHandlerTest extends Unit
{
    public function testHandleValidTemplateMailJob()
    {
        $job = new TemplateMailJob(
            'sender@example.com',
            'recipient@example.com',
            'template-123',
            ['name' => 'John', 'code' => 'ABC123']
        );
        $job->setJobId('job-123');

        $mailBackend = $this->makeEmpty(MailBackend::class, [
            'sendTemplateMail' => Expected::once(function ($templateId, $from, $recipient, $templateVariables) {
                verify($templateId)->equals('template-123');
                verify($from)->equals('sender@example.com');
                verify($recipient)->equals('recipient@example.com');
                verify($templateVariables)->equals(['name' => 'John', 'code' => 'ABC123']);

                return true;
            }),
        ]);

        $logger = $this->makeEmpty(LoggerInterface::class);

        $handler = new TemplateMailJobHandler($mailBackend, $logger);
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

        $handler = new TemplateMailJobHandler($mailBackend, $logger);

        verify(function () use ($handler, $invalidJob) {
            $handler->handle($invalidJob);
        })->callableThrows(InvalidJobException::class);
    }

    public function testHandleThrowsExceptionWhenTemplateIdIsNull()
    {
        $job = new TemplateMailJob(
            'sender@example.com',
            'recipient@example.com',
            null,
            []
        );
        $job->setJobId('job-123');

        $mailBackend = $this->makeEmpty(MailBackend::class);
        $logger = $this->makeEmpty(LoggerInterface::class, [
            'error' => Expected::once(),
        ]);

        $handler = new TemplateMailJobHandler($mailBackend, $logger);

        verify(function () use ($handler, $job) {
            $handler->handle($job);
        })->callableThrows(JobDataException::class);
    }

    public function testHandleThrowsExceptionWhenMailBackendReturnsErrorString()
    {
        $job = new TemplateMailJob(
            'sender@example.com',
            'recipient@example.com',
            'template-123',
            []
        );
        $job->setJobId('job-123');

        $mailBackend = $this->makeEmpty(MailBackend::class, [
            'sendTemplateMail' => Expected::once('SMTP connection failed'),
        ]);

        $logger = $this->makeEmpty(LoggerInterface::class, [
            'error' => Expected::once(),
        ]);

        $handler = new TemplateMailJobHandler($mailBackend, $logger);

        verify(function () use ($handler, $job) {
            $handler->handle($job);
        })->callableThrows(JobExecutionException::class);
    }

    public function testHandleThrowsExceptionWhenMailBackendReturnsFalse()
    {
        $job = new TemplateMailJob(
            'sender@example.com',
            'recipient@example.com',
            'template-123',
            []
        );
        $job->setJobId('job-123');

        $mailBackend = $this->makeEmpty(MailBackend::class, [
            'sendTemplateMail' => Expected::once(false),
        ]);

        $logger = $this->makeEmpty(LoggerInterface::class, [
            'error' => Expected::once(),
        ]);

        $handler = new TemplateMailJobHandler($mailBackend, $logger);

        verify(function () use ($handler, $job) {
            $handler->handle($job);
        })->callableThrows(JobExecutionException::class);
    }
}
