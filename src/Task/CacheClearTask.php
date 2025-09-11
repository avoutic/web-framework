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
use WebFramework\Core\Cache;

/**
 * Class CacheClearTask.
 *
 * This task is responsible for clearing the entire cache.
 */
class CacheClearTask extends ConsoleTask
{
    /**
     * CacheClearTask constructor.
     *
     * @param Cache    $cache        The cache service
     * @param resource $outputStream The output stream
     */
    public function __construct(
        private BootstrapService $bootstrapService,
        private Cache $cache,
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
        return 'cache:clear';
    }

    public function getDescription(): string
    {
        return 'Clear the entire cache';
    }

    public function getUsage(): string
    {
        return <<<'EOF'
        Clear the entire cache.

        Usage:
        framework cache:clear
        EOF;
    }

    public function execute(): void
    {
        $this->bootstrapService->skipSanityChecks();
        $this->bootstrapService->bootstrap();

        $this->cache->flush();
        $this->write('Cache cleared successfully.'.PHP_EOL);
    }

    public function handlesOwnBootstrapping(): bool
    {
        return true;
    }
}
