<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Diagnostics;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use WebFramework\Cache\Cache;
use WebFramework\Mail\MailService;
use WebFramework\Support\ErrorReport;

/**
 * Class MailReportFunction.
 *
 * Implements the ReportFunction interface to send error reports via email.
 */
class MailReportFunction implements ReportFunction
{
    /**
     * MailReportFunction constructor.
     *
     * @param Cache           $cache           The cache service for storing error occurrence information
     * @param LoggerInterface $logger          The logger
     * @param MailService     $mailService     The mail service for sending error reports
     * @param string          $assertRecipient The email address to send error reports to
     */
    public function __construct(
        private Cache $cache,
        private LoggerInterface $logger,
        private MailService $mailService,
        private string $assertRecipient,
    ) {}

    /**
     * Report an error or issue.
     *
     * This method implements the report method from the ReportFunction interface.
     * It sends error reports via email, with rate limiting to prevent excessive emails for the same error.
     *
     * @param string      $message     The error message
     * @param string      $errorType   The type of error
     * @param ErrorReport $errorReport The error report
     */
    public function report(string $message, string $errorType, ErrorReport $errorReport): void
    {
        // Make sure we are not spamming the same error en masse
        $cacheId = "errors[{$errorReport->getHash()}]";
        $cached = $this->cache->get($cacheId);

        if ($cached === false)
        {
            $cached = [
                'count' => 1,
            ];
        }
        else
        {
            $cached['count']++;
        }

        $this->cache->set($cacheId, $cached, Carbon::now()->addMinutes(10)->getTimestamp());

        // If more than 3 occurred in the last 10 minutes, only send out mail sporadically
        if ($cached['count'] > 1000 && $cached['count'] % 1000 !== 0)
        {
            $this->logger->warning('Skipping error report due to rate limiting', ['count' => $cached['count']]);

            return;
        }

        if ($cached['count'] > 100 && $cached['count'] % 100 !== 0)
        {
            $this->logger->warning('Skipping error report due to rate limiting', ['count' => $cached['count']]);

            return;
        }

        if ($cached['count'] > 3 && $cached['count'] % 25 !== 0)
        {
            $this->logger->warning('Skipping error report due to rate limiting', ['count' => $cached['count']]);

            return;
        }

        $title = $errorReport->getTitle();
        if ($cached['count'] > 3)
        {
            $title = "[{$cached['count']} times]: {$title}";
        }

        $this->logger->info('Sending error report', ['title' => $title]);

        $this->mailService->sendRawMail(
            null,
            $this->assertRecipient,
            $title,
            $errorReport->toString(),
        );
    }
}
