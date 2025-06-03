<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Support;

class ErrorReport
{
    public string $file = 'unknown';
    public int $line = 0;
    public string $message = 'unknown';
    public string $serverName = 'unknown';
    public string $requestSource = 'unknown';
    public string $errorType = 'unknown';
    public string $dbError = 'Not initialized yet';

    /** @var array<string> */
    public array $stack = [];

    /** @var null|array{user_id: int, username: string, email: string} */
    public ?array $auth = null;

    /** @var array<string, mixed> */
    public array $inputs = [];

    /** @var array<array<mixed>> */
    public array $headers = [];

    /** @var array<string, string> */
    public array $serverParams = [];

    public function getTitle(): string
    {
        return "{$this->serverName} - {$this->errorType}: {$this->message}";
    }

    public function getHash(): string
    {
        $key = "{$this->serverName}:{$this->requestSource}:{$this->file}:{$this->line}:{$this->errorType}:{$this->message}";

        return sha1($key);
    }

    /**
     * Convert the report to a string.
     */
    public function toString(): string
    {
        $inputsFmt = print_r($this->inputs, true);
        $authFmt = $this->auth ? print_r($this->auth, true) : "Not authenticated\n";
        $headersFmt = print_r($this->headers, true);
        $serverFmt = print_r($this->serverParams, true);
        $stackFmt = (count($this->stack)) ? implode("\n", $this->stack) : 'No stack trace available';

        return <<<TXT
File: {$this->file}
Line: {$this->line}
ErrorType: {$this->errorType}
Message: {$this->message}

Server: {$this->serverName}
Request: {$this->requestSource}

Condensed backtrace:
{$stackFmt}

Last Database error:
{$this->dbError}

Inputs:
{$inputsFmt}
Auth:
{$authFmt}
Headers:
{$headersFmt}
Server:
{$serverFmt}
TXT;
    }
}
