<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Presentation\MessageService;
use WebFramework\Translation\TranslationService;

/**
 * @internal
 *
 * @coversNothing
 */
final class MessageServiceTest extends Unit
{
    public function testAddMessage()
    {
        $translationService = $this->makeEmpty(TranslationService::class, [
            'translate' => function ($key, $tag) {
                return "Translated: {$key}.{$tag}";
            },
        ]);

        $service = $this->make(
            MessageService::class,
            [
                'translationService' => $translationService,
            ],
        );

        $service->add('info', 'test.message', '', []);

        $messages = $service->getMessages();
        verify($messages[0]['message'])->equals('Translated: test.message');
    }

    public function testAddMultipleMessages()
    {
        $translationService = $this->makeEmpty(TranslationService::class, [
            'translate' => function ($key, $tag) {
                return "Translated: {$key}.{$tag}";
            },
        ]);

        $service = $this->make(
            MessageService::class,
            [
                'translationService' => $translationService,
            ],
        );

        $service->add('error', 'error.first');
        $service->add('error', 'error.second');
        $service->add('success', 'success.message');

        $messages = $service->getMessages();
        verify($messages[0]['message'])->equals('Translated: error.first');
        verify($messages[1]['message'])->equals('Translated: error.second');
        verify($messages[2]['message'])->equals('Translated: success.message');
    }

    public function testAddErrorsFromArray()
    {
        $translationService = $this->makeEmpty(TranslationService::class, [
            'translate' => function ($key, $tag) {
                return "Translated: {$key}.{$tag}";
            },
        ]);

        $service = $this->make(
            MessageService::class,
            [
                'translationService' => $translationService,
            ],
        );

        $errors = [
            'field1' => [
                ['message' => 'error.required', 'extra_message' => ''],
                ['message' => 'error.invalid', 'extra_message' => ''],
            ],
            'field2' => [
                ['message' => 'error.too_long', 'extra_message' => ''],
            ],
        ];

        $service->addErrors($errors);

        $messages = $service->getMessages();
        verify($messages[0]['message'])->equals('Translated: error.required');
        verify($messages[1]['message'])->equals('Translated: error.invalid');
        verify($messages[2]['message'])->equals('Translated: error.too_long');
    }

    public function testAddMessageWithParameters()
    {
        $translationService = $this->makeEmpty(TranslationService::class, [
            'translate' => function ($key, $tag, $params) {
                return "Message {$key}.{$tag} with param: {$params['name']}";
            },
        ]);

        $service = $this->make(
            MessageService::class,
            [
                'translationService' => $translationService,
            ],
        );

        $service->add('info', 'user.welcome', 'user.welcome_extra', ['name' => 'John']);

        $messages = $service->getMessages();
        verify($messages[0]['message'])->equals('Message user.welcome with param: John');
        verify($messages[0]['extra_message'])->equals('Message user.welcome_extra with param: John');
    }

    public function testGetMessagesEmpty()
    {
        $service = $this->make(
            MessageService::class,
        );

        $messages = $service->getMessages();
        verify($messages)->equals([]);
    }

    public function testAddErrorsFromEmptyArray()
    {
        $service = $this->make(
            MessageService::class,
        );

        $service->addErrors([]);

        $messages = $service->getMessages();
        verify($messages)->equals([]);
    }
}
