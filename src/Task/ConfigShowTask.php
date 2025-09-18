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

use WebFramework\Core\BootstrapService;
use WebFramework\Core\ConfigService;

/**
 * Task for displaying the loaded configuration.
 */
class ConfigShowTask extends ConsoleTask
{
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
        framework config:show
        EOF;
    }

    public function execute(): void
    {
        $this->bootstrapService->skipSanityChecks();
        $this->bootstrapService->bootstrap();

        $config = $this->configService->get();

        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false)
        {
            $this->write('Unable to encode configuration.'.PHP_EOL);

            return;
        }

        $this->write($json.PHP_EOL);
    }

    public function handlesOwnBootstrapping(): bool
    {
        return true;
    }
}
