<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use WebFramework\Mail\LoggingMailService;
use WebFramework\Mail\MailBackend;
use WebFramework\Mail\MailService;

/**
 * @internal
 *
 * @covers \WebFramework\Mail\LoggingMailService
 */
final class LoggingMailServiceTest extends Unit
{
    public function testImplementsServiceAndBackend()
    {
        $service = new LoggingMailService($this->makeEmpty(LoggerInterface::class));

        verify($service)->instanceOf(MailService::class);
        verify($service)->instanceOf(MailBackend::class);
    }

    public function testRawMailIsLoggedWithItsContent()
    {
        $logger = $this->makeEmpty(LoggerInterface::class, [
            'info' => Expected::once(function ($message, $context) {
                verify($message)->equals('Mail not sent, logged instead');
                verify($context)->same([
                    'from' => 'sender@example.com',
                    'recipient' => 'user@example.com',
                    'title' => 'Your code',
                    'message' => 'The code is 123456',
                ]);
            }),
        ]);

        $service = new LoggingMailService($logger);

        verify($service->sendRawMail('sender@example.com', 'user@example.com', 'Your code', 'The code is 123456'))->true();
    }

    public function testTemplateMailIsLoggedWithItsVariables()
    {
        $logger = $this->makeEmpty(LoggerInterface::class, [
            'info' => Expected::once(function ($message, $context) {
                verify($message)->equals('Template mail not sent, logged instead');
                verify($context)->same([
                    'template_id' => 'email-verification-code',
                    'from' => null,
                    'recipient' => 'user@example.com',
                    'template_variables' => ['code' => '123456'],
                ]);
            }),
        ]);

        $service = new LoggingMailService($logger);

        verify($service->sendTemplateMail('email-verification-code', null, 'user@example.com', ['code' => '123456']))->true();
    }
}
