<?php

namespace WebFramework\Core;

/**
 * Class UrlBuilder.
 *
 * Provides methods for building URLs with various parameters and messages.
 */
class UrlBuilder
{
    /**
     * UrlBuilder constructor.
     *
     * @param MessageService     $messageService     The message service for handling messages
     * @param RuntimeEnvironment $runtimeEnvironment The runtime environment service
     */
    public function __construct(
        private MessageService $messageService,
        private RuntimeEnvironment $runtimeEnvironment,
    ) {}

    /**
     * Build a URL with query parameters and optional message.
     *
     * @param string                                              $template        The URL template
     * @param array<string, int|string>                           $values          Values to replace in the template
     * @param array<int|string, array<int|string, string>|string> $queryParameters Additional query parameters
     * @param null|string                                         $messageType     The type of message to include
     * @param null|string                                         $message         The message content
     * @param null|string                                         $extraMessage    Additional message content
     * @param bool                                                $absolute        Whether to return an absolute URL
     *
     * @return string The built URL
     *
     * @throws \InvalidArgumentException If a required template value is missing
     * @throws \RuntimeException         If URL building fails
     */
    public function buildQueryUrl(string $template, array $values = [], array $queryParameters = [], ?string $messageType = null, ?string $message = null, ?string $extraMessage = null, bool $absolute = false): string
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

        if ($url === null)
        {
            throw new \RuntimeException('Failed to replace url elements');
        }

        if ($messageType)
        {
            $queryParameters['msg'] = $this->messageService->getForUrl(
                $messageType,
                $message ?? '',
                $extraMessage ?? '',
                false,
            );
        }

        // Split URL into its elements to properly insert the query parameters
        // before the fragment in the url
        $parts = parse_url($url);

        if ($parts === false)
        {
            throw new \RuntimeException('Failed to parse url');
        }

        $query_string = http_build_query($queryParameters);

        // If there's already a query string in the url, append the new one with a &
        if (isset($parts['query']))
        {
            $parts['query'] .= '&'.$query_string;
        }
        else
        {
            $parts['query'] = $query_string;
        }

        $builtUrl = '';

        if ($absolute)
        {
            $builtUrl = $this->getServerUrl();
        }

        return
            ($absolute ? $this->getServerUrl() : '').
            $this->runtimeEnvironment->getBaseUrl().
            (isset($parts['path']) ? $parts['path'] : '').
            (isset($parts['query']) ? '?'.$parts['query'] : '').
            (isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }

    /**
     * Build a URL without query parameters.
     *
     * @param string                    $template     The URL template
     * @param array<string, int|string> $values       Values to replace in the template
     * @param null|string               $messageType  The type of message to include
     * @param null|string               $message      The message content
     * @param null|string               $extraMessage Additional message content
     * @param bool                      $absolute     Whether to return an absolute URL
     *
     * @return string The built URL
     */
    public function buildUrl(string $template, array $values = [], ?string $messageType = null, ?string $message = null, ?string $extraMessage = null, bool $absolute = false): string
    {
        return $this->buildQueryUrl($template, $values, [], $messageType, $message, $extraMessage, $absolute);
    }

    /**
     * Get the server URL.
     *
     * @return string The server URL
     */
    public function getServerUrl(): string
    {
        return "{$this->runtimeEnvironment->getHttpMode()}://{$this->runtimeEnvironment->getServerName()}";
    }
}
