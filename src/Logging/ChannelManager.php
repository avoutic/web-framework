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

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface as Container;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ChannelManager
{
    /** @var array<string, LoggerInterface> */
    private array $resolvedChannels = [];

    /**
     * @param array<string, string> $channelConfig map of channel name to container id
     */
    public function __construct(
        private Container $container,
        private array $channelConfig = [],
    ) {}

    public function get(string $channel = 'default'): LoggerInterface
    {
        if (!isset($this->resolvedChannels[$channel]))
        {
            $this->resolvedChannels[$channel] = $this->resolveChannel($channel);
        }

        return $this->resolvedChannels[$channel];
    }

    public function getDefaultLogger(): LoggerInterface
    {
        return $this->get('default');
    }

    public function getExceptionLogger(): LoggerInterface
    {
        return $this->get('exception');
    }

    private function resolveChannel(string $channel): LoggerInterface
    {
        $configuration = $this->channelConfig[$channel] ?? null;

        if (is_array($configuration))
        {
            if (!isset($configuration['type']))
            {
                throw new \InvalidArgumentException("Channel configuration for {$channel} is missing a type");
            }

            if ($configuration['type'] === 'file')
            {
                if (!isset($configuration['path']))
                {
                    throw new \InvalidArgumentException("Channel configuration for {$channel} is missing a path");
                }

                $handler = new StreamHandler($configuration['path'], $configuration['level'] ?? Level::Debug);

                $logger = new Logger($channel);
                $logger->pushHandler($handler);

                return $logger;
            }

            throw new \InvalidArgumentException("Unknown channel type {$configuration['type']} for {$channel}");
        }

        if (is_string($configuration) && $this->container->has($configuration))
        {
            $candidate = $this->container->get($configuration);
            if ($candidate instanceof LoggerInterface)
            {
                return $candidate;
            }
        }

        return new NullLogger();
    }
}
