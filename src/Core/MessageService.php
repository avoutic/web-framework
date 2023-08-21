<?php

namespace WebFramework\Core;

use WebFramework\Security\ProtectService;
use WebFramework\Translation\TranslationService;

class MessageService
{
    /** @var array<array{mtype: string, message: string, extra_message: string}> */
    private array $messages = [];

    public function __construct(
        private ProtectService $protectService,
        private TranslationService $translationService,
    ) {
    }

    /**
     * @return array<array{mtype: string, message: string, extra_message: string}>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param array<mixed> $params
     */
    public function add(string $type, string $message, string $extraMessage = '', array $params = []): void
    {
        // Check if it is a translation key
        //
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

    public function getForUrl(string $mtype, string $message, string $extraMessage = '', bool $includeKey = true): string
    {
        $msg = ['mtype' => $mtype, 'message' => $message, 'extra_message' => $extraMessage];

        return ($includeKey ? 'msg=' : '').$this->protectService->packArray($msg);
    }

    /**
     * @param array<mixed> $errors
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
