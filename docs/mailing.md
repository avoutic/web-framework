# Mailing System

The WebFramework provides a flexible mailing system that supports both synchronous and asynchronous email sending. The system is designed to separate the queuing mechanism from the actual email backend, allowing you to easily switch between different email providers without changing your application code.

## Core Components

### MailService Interface

The `MailService` interface defines the contract for mail service implementations. It provides two methods for sending emails:

~~~php
<?php

interface MailService
{
    public function sendRawMail(?string $from, string $recipient, string $title, string $message): bool|string;
    public function sendTemplateMail(string $templateId, ?string $from, string $recipient, array $templateVariables): bool|string;
}
~~~

### MailBackend Interface

The `MailBackend` interface has the same contract as `MailService` but represents the actual email sending backend (e.g., Postmark, SMTP). This interface is used by queue handlers to send emails after they've been queued.

~~~php
<?php

interface MailBackend
{
    public function sendRawMail(?string $from, string $recipient, string $title, string $message): bool|string;
    public function sendTemplateMail(string $templateId, ?string $from, string $recipient, array $templateVariables): bool|string;
}
~~~

## Built-in Implementations

### NullMailService

The `NullMailService` is a no-op implementation that implements both `MailService` and `MailBackend`. It's useful for testing or when email functionality is not required. All methods return `true` without actually sending any emails.

### QueuedMailService

The `QueuedMailService` implements `MailService` and queues email jobs instead of sending them immediately. This allows you to send emails asynchronously, reducing latency in your main application thread.

When using `QueuedMailService`, emails are dispatched to the queue system and processed by job handlers that use `MailBackend` to actually send the emails.

## Configuration

### Synchronous Email Sending

To send emails synchronously (immediately), bind `MailService` to your backend implementation:

~~~php
<?php

use WebFramework\Mail\MailService;
use YourModule\PostmarkMailService;

return [
    MailService::class => DI\autowire(PostmarkMailService::class),
];
~~~

### Asynchronous Email Sending

To send emails asynchronously (queued), configure both `MailService` and `MailBackend`:

~~~php
<?php

use WebFramework\Mail\MailService;
use WebFramework\Mail\MailBackend;
use WebFramework\Mail\QueuedMailService;
use YourModule\PostmarkMailService;

return [
    // Queue emails instead of sending immediately
    MailService::class => DI\autowire(QueuedMailService::class),
    
    // Backend used by queue handlers to actually send emails
    MailBackend::class => DI\autowire(PostmarkMailService::class),
];
~~~

**Important**: When using `QueuedMailService`, you must also bind `MailBackend` to your actual email backend implementation. The queue handlers will use `MailBackend` to send emails, preventing an infinite queuing loop.

### Queue Worker Setup

For asynchronous email sending to work, you need to run a queue worker:

~~~bash
php scripts/framework.php queue:worker default
~~~

By default, the mail job handlers (`RawMailJobHandler` and `TemplateMailJobHandler`) are automatically registered when the `QueueService` is instantiated in the `definitions.php` file. If you want to register your own job handlers, you can do so by registering them with the `QueueService` in your application definitions. Use DI\decorate to register your own job handlers in addition to the default ones, use DI\autowire to register your own job handlers and override the default ones.

~~~php
<?php

use WebFramework\Queue\QueueService;

return [
    QueueService::class => DI\decorate(function (QueueService $queueService) {
        $queueService->registerJobHandler(MyCustomJob::class, MyCustomJobHandler::class);

        return $queueService;
    }),
]
~~~

## Usage

### Injecting MailService

Inject `MailService` into your classes via constructor injection:

~~~php
<?php

use WebFramework\Mail\MailService;

class MyService
{
    public function __construct(
        private MailService $mailService,
    ) {}
    
    public function sendWelcomeEmail(string $email): void
    {
        $this->mailService->sendRawMail(
            null, // Use default sender
            $email,
            'Welcome!',
            'Thank you for joining us.'
        );
    }
}
~~~

### Sending Raw Emails

Use `sendRawMail()` to send emails with plain text or HTML content:

~~~php
<?php

$result = $this->mailService->sendRawMail(
    'sender@example.com',  // From address (null to use default)
    'recipient@example.com', // Recipient
    'Email Subject',         // Subject
    '<h1>Hello</h1><p>This is the email body.</p>' // Message body
);

if ($result !== true) {
    // Handle error - $result contains error message
    error_log('Failed to send email: ' . $result);
}
~~~

### Sending Template Emails

Use `sendTemplateMail()` to send emails using templates configured in your email provider:

~~~php
<?php

$result = $this->mailService->sendTemplateMail(
    'welcome-email',              // Template ID
    'sender@example.com',         // From address (null to use default)
    'recipient@example.com',     // Recipient
    [                             // Template variables
        'username' => 'John Doe',
        'action_url' => 'https://example.com/verify',
    ]
);

if ($result !== true) {
    // Handle error
    error_log('Failed to send template email: ' . $result);
}
~~~

### UserMailer Helper

The framework provides a `UserMailer` helper class for common user-related emails:

~~~php
<?php

use WebFramework\Mail\UserMailer;

class MyService
{
    public function __construct(
        private UserMailer $userMailer,
    ) {}
    
    public function sendVerificationEmail(User $user, string $verifyUrl): void
    {
        $this->userMailer->emailVerificationLink($user->getEmail(), [
            'verify_url' => $verifyUrl,
            'user' => [
                'username' => $user->getUsername(),
            ],
        ]);
    }
}
~~~

Available `UserMailer` methods:
- `emailVerificationLink()` - Send email verification link
- `changeEmailVerificationLink()` - Send change email verification link
- `passwordReset()` - Send password reset email
- `newPassword()` - Send new password email

## Template Configuration

You can override default template IDs used by `UserMailer` in your configuration:

For available configuration options and default settings, see `config/base_config.php`.

~~~php
<?php

return [
    'user_mailer' => [
        'template_overrides' => [
            'email-verification-code' => 'my-custom-verification-template',
            'change-email-verification-code' => 'my-custom-change-email-template',
            'password-reset' => 'my-custom-password-reset-template',
            'new-password' => 'my-custom-new-password-template',
        ],
    ],
];
~~~

## Default Sender Configuration

Configure the default sender email address:

For available configuration options and default settings, see `config/base_config.php`.

~~~php
<?php

return [
    'sender_core' => [
        'default_sender' => 'noreply@example.com',
    ],
];
~~~

## Error Handling

Both `sendRawMail()` and `sendTemplateMail()` return:
- `true` if the email was sent successfully (or queued successfully when using `QueuedMailService`)
- A string containing an error message if sending failed

Always check the return value:

~~~php
<?php

$result = $this->mailService->sendRawMail(null, $email, $subject, $body);

if ($result !== true) {
    // Log error or handle failure
    $this->logger->error('Email send failed', ['error' => $result]);
    return false;
}
~~~

## Best Practices

1. **Use QueuedMailService for Production**: Asynchronous email sending reduces latency and improves user experience. Always use `QueuedMailService` in production environments.

2. **Separate MailService and MailBackend**: When using async sending, keep `MailService` bound to `QueuedMailService` and `MailBackend` bound to your actual email provider. This prevents infinite queuing loops.

3. **Handle Errors Gracefully**: Always check return values and handle errors appropriately. Failed emails should be logged for debugging.

4. **Use Templates When Possible**: Template emails are easier to manage and update than raw HTML emails. They also support better personalization.

5. **Monitor Queue Workers**: Ensure queue workers are running when using `QueuedMailService`. Emails won't be sent until workers process the queue.

6. **Test with NullMailService**: Use `NullMailService` in test environments to avoid sending actual emails during testing.

## Integration with Queue System

The mailing system integrates seamlessly with the queue system. When using `QueuedMailService`:

1. Emails are dispatched as `RawMailJob` or `TemplateMailJob` to the queue
2. Queue workers process these jobs using registered handlers
3. Handlers use `MailBackend` to actually send emails
4. Failed jobs are retried according to queue configuration

For more information on the queue system, see the [Queueing documentation](./queueing.md).

