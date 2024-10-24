<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

use WebFramework\Security\ProtectService;
use WebFramework\Translation\TranslationService;

/**
 * Class MessageService.
 *
 * Manages messages for the application, including translation and URL encoding.
 */
class MessageService
{
    /** @var array<array{mtype: string, message: string, extra_message: string}> */
    private array $messages = [];

    /**
     * MessageService constructor.
     *
     * @param ProtectService     $protectService     Service for protecting sensitive data
     * @param TranslationService $translationService Service for handling translations
     */
    public function __construct(
        private ProtectService $protectService,
        private TranslationService $translationService,
    ) {}

    /**
     * Get all stored messages.
     *
     * @return array<array{mtype: string, message: string, extra_message: string}>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Add a new message.
     *
     * @param string       $type         The message type
     * @param string       $message      The message content or translation key
     * @param string       $extraMessage Additional message content or translation key
     * @param array<mixed> $params       Parameters for translation
     */
    public function add(string $type, string $message, string $extraMessage = '', array $params = []): void
    {
        // Check if it is a translation key
        if (!str_contains($message, ' ') && str_contains($message, '.'))
        {
            $parts = explode('.', $message);

            if (count($parts) !== 2)
            {
                throw new \InvalidArgumentException('Invalid message format. Expected "category.tag".');
            }

            if (!strlen($extraMessage) && $this->translationService->tagExists($parts[0], "{$parts[1]}_extra"))
            {
                $extraMessage = "{$parts[0]}.{$parts[1]}_extra";
            }

            $message = __($parts[0], $parts[1], $params);
        }

        if (strlen($extraMessage) && !str_contains($extraMessage, ' '))
        {
            $parts = explode('.', $extraMessage);

            if (count($parts) !== 2)
            {
                throw new \InvalidArgumentException('Invalid message format. Expected "category.tag".');
            }

            $extraMessage = __($parts[0], $parts[1], $params);
        }

        $this->messages[] = [
            'mtype' => $type,
            'message' => ucfirst($message),
            'extra_message' => ucfirst($extraMessage),
        ];
    }

    /**
     * Add a message from a URL-encoded string.
     *
     * @param string $data The URL-encoded message data
     */
    public function addFromUrl(string $data): void
    {
        $msg = $this->protectService->unpackArray($data);

        if (!is_array($msg))
        {
            return;
        }

        $this->add(
            $msg['mtype'],
            $msg['message'],
            $msg['extra_message'],
        );
    }

    /**
     * Get a URL-encoded string representation of a message.
     *
     * @param string $mtype        The message type
     * @param string $message      The message content
     * @param string $extraMessage Additional message content
     * @param bool   $includeKey   Whether to include the 'msg=' key in the output
     *
     * @return string The URL-encoded message string
     */
    public function getForUrl(string $mtype, string $message, string $extraMessage = '', bool $includeKey = true): string
    {
        $msg = ['mtype' => $mtype, 'message' => $message, 'extra_message' => $extraMessage];

        return ($includeKey ? 'msg=' : '').$this->protectService->packArray($msg);
    }

    /**
     * Add multiple error messages.
     *
     * @param array<mixed> $errors An array of error messages
     */
    public function addErrors(array $errors): void
    {
        foreach ($errors as $key => $value)
        {
            if (is_string($key))
            {
                $this->addErrors($value);

                continue;
            }

            $message = $value['message'];
            $extraMessage = $value['extra_message'] ?? '';
            $params = $value['params'] ?? [];

            $this->add(
                'error',
                $message,
                $extraMessage,
                $params,
            );
        }
    }
}
