<?php

namespace WebFramework\Core;

class MailReportFunction implements ReportFunction
{
    public function __construct(
        private Cache $cache,
        private MailService $mailService,
        private string $assertRecipient,
    ) {
    }

    /**
     * @param array{title: string, message: string, low_info_message: string, hash:string} $debugInfo
     */
    public function report(string $message, string $errorType, array $debugInfo): void
    {
        // Make sure we are not spamming the same error en masse
        //
        $cacheId = "errors[{$debugInfo['hash']}]";
        $cached = $this->cache->get($cacheId);

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

        $this->cache->set($cacheId, $cached, time() + 10 * 60);

        // If more than 3 occurred in the last 10 minutes, only send out mail sporadically
        //
        if ($cached['count'] > 1000 && $cached['count'] % 1000 !== 0)
        {
            return;
        }

        if ($cached['count'] > 100 && $cached['count'] % 100 !== 0)
        {
            return;
        }

        if ($cached['count'] > 3 && $cached['count'] % 25 !== 0)
        {
            return;
        }

        $title = $debugInfo['title'];
        if ($cached['count'] > 3)
        {
            $title = "[{$cached['count']} times]: {$title}";
        }

        $this->mailService->sendRawMail(
            null,
            $this->assertRecipient,
            $title,
            $debugInfo['message']
        );
    }
}
