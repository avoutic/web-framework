<?php

namespace WebFramework\Core;

use WebFramework\Security\ProtectService;

class MessageService
{
    /** @var array<array{mtype: string, message: string, extra_message: string}> */
    private array $messages = [];

    public function __construct(
        private ProtectService $protectService,
    ) {
    }

    /**
     * @return array<array{mtype: string, message: string, extra_message: string}>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function add(string $type, string $message, string $extraMessage = ''): void
    {
        $this->messages[] = [
            'mtype' => $type,
            'message' => $message,
            'extra_message' => $extraMessage,
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

    public function getForUrl(string $mtype, string $message, string $extraMessage = ''): string
    {
        $msg = ['mtype' => $mtype, 'message' => $message, 'extra_message' => $extraMessage];

        return 'msg='.$this->protectService->packArray($msg);
    }
}
