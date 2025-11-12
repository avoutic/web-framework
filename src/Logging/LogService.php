<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Logging;

/**
 * @codeCoverageIgnore
 */
class LogService
{
    public function __construct(
        private ChannelManager $channels,
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function log(string $channel, string $level, string|\Stringable $message, array $context = []): void
    {
        $this->channels->get($channel)->log($level, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function emergency(string $channel, string|\Stringable $message, array $context = []): void
    {
        $this->log($channel, 'emergency', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function alert(string $channel, string|\Stringable $message, array $context = []): void
    {
        $this->log($channel, 'alert', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function critical(string $channel, string|\Stringable $message, array $context = []): void
    {
        $this->log($channel, 'critical', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $channel, string|\Stringable $message, array $context = []): void
    {
        $this->log($channel, 'error', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function warning(string $channel, string|\Stringable $message, array $context = []): void
    {
        $this->log($channel, 'warning', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function notice(string $channel, string|\Stringable $message, array $context = []): void
    {
        $this->log($channel, 'notice', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function info(string $channel, string|\Stringable $message, array $context = []): void
    {
        $this->log($channel, 'info', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function debug(string $channel, string|\Stringable $message, array $context = []): void
    {
        $this->log($channel, 'debug', $message, $context);
    }
}
