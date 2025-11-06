<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Task;

use WebFramework\Config\ConfigService;
use WebFramework\Core\BootstrapService;

/**
 * Task for displaying the loaded configuration.
 */
class ConfigShowTask extends ConsoleTask
{
    private ?string $configPath = null;

    /**
     * ConfigShowTask constructor.
     *
     * @param BootstrapService $bootstrapService The bootstrap service
     * @param ConfigService    $configService    The configuration service
     * @param resource         $outputStream     The output stream
     */
    public function __construct(
        private BootstrapService $bootstrapService,
        private ConfigService $configService,
        private $outputStream = STDOUT
    ) {}

    /**
     * Set the configuration path to display.
     *
     * @param string $path The dot-notation path to the configuration node
     */
    public function setConfigPath(string $path): void
    {
        $this->configPath = $path;
    }

    /**
     * Write a message to the output stream.
     *
     * @param string $message The message to write
     */
    private function write(string $message): void
    {
        fwrite($this->outputStream, $message);
    }

    public function getCommand(): string
    {
        return 'config:show';
    }

    public function getDescription(): string
    {
        return 'Display the loaded configuration';
    }

    public function getUsage(): string
    {
        return <<<'EOF'
        Show the currently loaded configuration.

        Usage:
        framework config:show [path]

        Examples:
        framework config:show
        framework config:show sender_core
        framework config:show sender_core.default_sender
        EOF;
    }

    public function getArguments(): array
    {
        return [
            new TaskArgument('path', 'Dot-notation path to a specific configuration node (e.g., sender_core or sender_core.default_sender)', false, [$this, 'setConfigPath']),
        ];
    }

    public function execute(): void
    {
        $this->bootstrapService->skipSanityChecks();
        $this->bootstrapService->bootstrap();

        try
        {
            $config = $this->configPath !== null
                ? $this->configService->get($this->configPath)
                : $this->configService->get();

            $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if ($json === false)
            {
                $this->write('Unable to encode configuration.'.PHP_EOL);

                return;
            }

            $this->write($json.PHP_EOL);
        }
        catch (\InvalidArgumentException $e)
        {
            $this->write('Error: '.$e->getMessage().PHP_EOL);

            return;
        }
    }

    public function handlesOwnBootstrapping(): bool
    {
        return true;
    }
}
