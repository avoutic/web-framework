<?php

namespace WebFramework\Core;

class MessageService
{
    /** @var array<array{mtype: string, message: string, extra_message: string}> */
    private array $messages = [];

    public function __construct(
        private Security\ProtectService $protect_service,
    ) {
    }

    /**
     * @return array<array{mtype: string, message: string, extra_message: string}>
     */
    public function get_messages(): array
    {
        return $this->messages;
    }

    public function add(string $type, string $message, string $extra_message): void
    {
        $this->messages[] = [
            'mtype' => $type,
            'message' => $message,
            'extra_message' => $extra_message,
        ];
    }

    public function add_from_url(string $data): void
    {
        $msg = $this->protect_service->unpack_array($data);

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

    public function get_for_url(string $mtype, string $message, string $extra_message = ''): string
    {
        $msg = ['mtype' => $mtype, 'message' => $message, 'extra_message' => $extra_message];

        return 'msg='.$this->protect_service->pack_array($msg);
    }
}
