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
use WebFramework\Repository\VerificationCodeRepository;

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
     * @param BootstrapService           $bootstrapService           The bootstrap service
     * @param VerificationCodeRepository $verificationCodeRepository The verification code repository
     * @param resource                   $outputStream               The output stream
     */
    public function __construct(
        private BootstrapService $bootstrapService,
        private VerificationCodeRepository $verificationCodeRepository,
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

        $now = Carbon::now();
        $sevenDaysAgo = Carbon::now()->subDays(7);

        // Delete expired codes and codes that have been used for more than 7 days
        $this->verificationCodeRepository
            ->query([
                'OR' => [
                    'expires_at' => ['<', $now->getTimestamp()],
                    [
                        'used_at' => [
                            ['IS NOT', null],
                            ['<', $sevenDaysAgo->getTimestamp()],
                        ],
                    ],
                ],
            ])
            ->delete()
        ;

        $this->write('Cleaned up verification code(s).'.PHP_EOL);
    }

    public function handlesOwnBootstrapping(): bool
    {
        return true;
    }
}
