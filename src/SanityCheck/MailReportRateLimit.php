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
use WebFramework\Cache\Cache;
use WebFramework\Diagnostics\MailReportFunction;
use WebFramework\Diagnostics\ReportFunction;

/**
 * Class MailReportRateLimit.
 *
 * Checks that error reports by mail can be rate limited. MailReportFunction counts errors
 * in the cache. Without a cache that stores values, every error sends a mail.
 */
class MailReportRateLimit extends SanityCheckBase
{
    private const PROBE_KEY = 'sanity_check[mail_report_rate_limit]';

    /**
     * MailReportRateLimit constructor.
     *
     * @param Cache           $cache          The cache service
     * @param LoggerInterface $logger         The logger
     * @param ReportFunction  $reportFunction The configured report function
     */
    public function __construct(
        private Cache $cache,
        private LoggerInterface $logger,
        private ReportFunction $reportFunction,
    ) {}

    /**
     * Check that the cache stores values when error reports are sent by mail.
     *
     * @return bool True if the check passes, false otherwise
     */
    public function performChecks(): bool
    {
        $this->addOutput('Checking rate limiting of error report mails:'.PHP_EOL);

        if (!$this->reportFunction instanceof MailReportFunction)
        {
            $this->addOutput(' - Error reports are not sent by mail'.PHP_EOL.'  Pass'.PHP_EOL.PHP_EOL);

            return true;
        }

        $this->cache->set(self::PROBE_KEY, 'probe', 60);
        $stored = $this->cache->get(self::PROBE_KEY) === 'probe';
        $this->cache->invalidate(self::PROBE_KEY);

        if (!$stored)
        {
            $this->logger->emergency('Error reports are sent by mail, but the cache does not store values, so the mails are not rate limited');

            $this->addOutput(' - The cache does not store values. Every error sends a mail. Bind a working Cache implementation. Not fixing.'.PHP_EOL);
            $this->addOutput(PHP_EOL.'Breaking off');

            return false;
        }

        $this->addOutput(' - The cache stores values'.PHP_EOL.'  Pass'.PHP_EOL.PHP_EOL);

        return true;
    }
}
