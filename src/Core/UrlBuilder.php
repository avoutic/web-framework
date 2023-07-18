<?php

namespace WebFramework\Core;

class UrlBuilder
{
    public function __construct(
        private MessageService $messageService,
        private string $baseUrl,
    ) {
    }

    /**
     * @param array<string, int|string>                           $values
     * @param array<int|string, array<int|string, string>|string> $queryParameters
     */
    public function buildQueryUrl(string $template, array $values = [], array $queryParameters = [], ?string $messageType = null, ?string $message = null, ?string $extraMessage = null): string
    {
        preg_match_all('/\{(\w+)\}/', $template, $matches);

        foreach ($matches[1] as $match)
        {
            if (!array_key_exists($match, $values))
            {
                throw new \InvalidArgumentException("Missing value for URL placeholder: {$match}");
            }
        }

        $url = preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($values) {
            return $values[$matches[1]] ?? $matches[0];
        }, $template);

        if ($messageType)
        {
            $queryParameters['msg'] = $this->messageService->getForUrl(
                $messageType,
                $message ?? '',
                $extraMessage ?? '',
                false,
            );
        }

        if (count($queryParameters))
        {
            $url .= '?'.http_build_query($queryParameters);
        }

        return $this->baseUrl.$url;
    }

    /**
     * @param array<string, int|string> $values
     */
    public function buildUrl(string $template, array $values, ?string $messageType = null, ?string $message = null, ?string $extraMessage = null): string
    {
        return $this->buildQueryUrl($template, $values, [], $messageType, $message, $extraMessage);
    }
}
