<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\SanityCheck;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WebFramework\Diagnostics\NullReportFunction;
use WebFramework\Diagnostics\ReportFunction;
use WebFramework\Logging\ChannelManager;

/**
 * Class ErrorReporting.
 *
 * Checks that unhandled exceptions end up somewhere: in the exception log channel, with a
 * report function, or both. Fails when both are null implementations.
 */
class ErrorReporting extends SanityCheckBase
{
    /**
     * ErrorReporting constructor.
     *
     * @param ChannelManager  $channelManager The log channel manager
     * @param LoggerInterface $logger         The logger
     * @param ReportFunction  $reportFunction The configured report function
     */
    public function __construct(
        private ChannelManager $channelManager,
        private LoggerInterface $logger,
        private ReportFunction $reportFunction,
    ) {}

    /**
     * Check that unhandled exceptions are logged or reported.
     *
     * @return bool True if the check passes, false otherwise
     */
    public function performChecks(): bool
    {
        $this->addOutput('Checking that errors are logged or reported:'.PHP_EOL);

        $logged = !$this->channelManager->getExceptionLogger() instanceof NullLogger;
        $reported = !$this->reportFunction instanceof NullReportFunction;

        $this->addOutput(' - Exception log channel: '.($logged ? 'active' : 'discards everything').PHP_EOL);
        $this->addOutput(' - Report function: '.($reported ? 'active' : 'discards everything').PHP_EOL);

        if (!$logged && !$reported)
        {
            $this->logger->emergency('Unhandled exceptions are neither logged nor reported');

            $this->addOutput(' - Unhandled exceptions end up nowhere. Bind the exception log channel or a ReportFunction. Not fixing.'.PHP_EOL);
            $this->addOutput(PHP_EOL.'Breaking off');

            return false;
        }

        $this->addOutput('  Pass'.PHP_EOL.PHP_EOL);

        return true;
    }
}
