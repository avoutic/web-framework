<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;

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
     * @param string                                                                       $message   The error message
     * @param string                                                                       $errorType The type of error
     * @param array{title: string, low_info_message: string, message: string, hash:string} $debugInfo Additional debug information
     */
    public function report(string $message, string $errorType, array $debugInfo): void
    {
        // Make sure we are not spamming the same error en masse
        $cacheId = "errors[{$debugInfo['hash']}]";
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

        $title = $debugInfo['title'];
        if ($cached['count'] > 3)
        {
            $title = "[{$cached['count']} times]: {$title}";
        }

        $this->logger->error('Sending error report', ['title' => $title, 'message' => $debugInfo['message']]);

        $this->mailService->sendRawMail(
            null,
            $this->assertRecipient,
            $title,
            $debugInfo['message']
        );
    }
}
