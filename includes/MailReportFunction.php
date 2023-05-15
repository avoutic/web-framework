<?php

namespace WebFramework\Core;

class MailReportFunction implements ReportFunction
{
    public function __construct(
        protected CacheService $cache,
        protected MailService $mail_service,
        protected string $assert_recipient,
    ) {
    }

    /**
     * @param array{title: string, message: string, low_info_message: string, hash:string} $debug_info
     */
    public function report(string $message, string $error_type, array $debug_info): void
    {
        // Make sure we are not spamming the same error en masse
        //
        $cache_id = "errors[{$debug_info['hash']}]";
        $cached = $this->cache->get($cache_id);

        if ($cached === false)
        {
            $cached = [
                'count' => 1,
                'last_timestamp' => time(),
            ];
        }
        else
        {
            $cached['count']++;
            $cached['last_timestamp'] = time();
        }

        $this->cache->set($cache_id, $cached, time() + 10 * 60);

        // More than 3 in the last 10 minutes, update timestamp, and skip mail
        //
        if ($cached['count'] > 3 && $cached['count'] % 25 !== 0)
        {
            return;
        }

        $title = $debug_info['title'];
        if ($cached['count'] % 25 === 0)
        {
            $title = "[{$cached['count']} times]: {$title}";
        }

        $this->mail_service->send_raw(
            $this->assert_recipient,
            $title,
            $debug_info['message']
        );
    }
}
