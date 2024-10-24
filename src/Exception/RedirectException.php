<?php

namespace WebFramework\Exception;

class RedirectException extends \Exception
{
    public function __construct(
        private string $url,
        string $message = '',
        int $code = 0,
        ?\Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
