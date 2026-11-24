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
use WebFramework\Core\RuntimeEnvironment;

class ChannelManager
{
    /** @var array<string, LoggerInterface> */
    private array $resolvedChannels = [];

    /**
     * @param array<string, string> $channelConfig map of channel name to container id
     */
    public function __construct(
        private Container $container,
        private RuntimeEnvironment $runtimeEnvironment,
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

                $path = $configuration['path'];
                // If path is relative (doesn't start with /), base it on app directory
                if (!str_starts_with($path, '/'))
                {
                    $path = $this->runtimeEnvironment->getAppDir().'/'.$path;
                }

                $handler = new StreamHandler($path, $configuration['level'] ?? Level::Debug);

                $logger = new Logger($channel);
                $logger->pushHandler($handler);

                return $logger;
            }

            throw new \InvalidArgumentException("Unknown channel type {$configuration['type']} for {$channel}");
        }

        // Without a line in the configuration, the channel uses the container entry with
        // its name: channels.<name>
        //
        $containerId = is_string($configuration) ? $configuration : "channels.{$channel}";

        if ($this->container->has($containerId))
        {
            $candidate = $this->container->get($containerId);
            if ($candidate instanceof LoggerInterface)
            {
                return $candidate;
            }
        }

        // Logging to a channel without a logger is not an error, but it is likely a
        // mistake, such as a typo in the name of the channel. Say so once, on the
        // default channel
        //
        if ($channel !== 'default')
        {
            $this->getDefaultLogger()->warning('Log channel has no logger, its messages are discarded', [
                'channel' => $channel,
                'container_id' => $containerId,
            ]);
        }

        return new NullLogger();
    }
}
