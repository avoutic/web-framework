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

use Carbon\Carbon;
use WebFramework\Core\BootstrapService;
use WebFramework\Database\Database;

/**
 * Class CleanupVerificationCodesTask.
 *
 * This task is responsible for cleaning up expired and used verification codes.
 */
class CleanupVerificationCodesTask extends ConsoleTask
{
    /**
     * CleanupVerificationCodesTask constructor.
     *
     * @param BootstrapService $bootstrapService The bootstrap service
     * @param Database         $database         The database service
     * @param resource         $outputStream     The output stream
     */
    public function __construct(
        private BootstrapService $bootstrapService,
        private Database $database,
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
        return 'verification-codes:cleanup';
    }

    public function getDescription(): string
    {
        return 'Clean up expired and used verification codes';
    }

    public function getUsage(): string
    {
        return <<<'EOF'
        Clean up expired and used verification codes from the database.

        Usage:
        framework verification-codes:cleanup
        EOF;
    }

    public function execute(): void
    {
        $this->bootstrapService->skipSanityChecks();
        $this->bootstrapService->bootstrap();

        $now = Carbon::now()->getTimestamp();
        $sevenDaysAgo = Carbon::now()->subDays(7)->getTimestamp();

        // Delete expired codes and codes that have been used for more than 7 days
        $query = <<<'SQL'
        DELETE FROM verification_codes
        WHERE expires_at < ? OR
              (used_at IS NOT NULL AND used_at < ?)
SQL;

        $this->database->query($query, [$now, $sevenDaysAgo], 'Failed to cleanup verification codes');

        $this->write('Cleaned up verification code(s).'.PHP_EOL);
    }

    public function handlesOwnBootstrapping(): bool
    {
        return true;
    }
}
