<?php

namespace WebFramework\Core;

use Psr\Http\Message\RequestInterface;

class RouteService
{
    /**
     * @var array<array{methods: array<string>, regex: string, class: string, function: string, args: array<string>}>
     */
    private array $route_array = [];

    /**
     * @var array<array{methods: array<string>, regex: string, url: string, redir_type: int, args: array<string, int>}>
     */
    private array $redirect_array = [];

    public function __construct(
        private string $base_url,
    ) {
    }

    public function has_routes(): bool
    {
        return (count($this->route_array) + count($this->redirect_array) > 0);
    }

    /**
     * @return array{url: string, redirect_type: int}|false
     */
    public function get_redirect(RequestInterface $request): array|false
    {
        $request_method = $request->getMethod();
        $request_uri = (string) $request->getUri()->getPath();

        // Remove base URL
        //
        if (substr($request_uri, 0, strlen($this->base_url)) === $this->base_url)
        {
            $request_uri = substr($request_uri, 4);
        }

        // Check if there is a redirect to follow
        //
        $matches = null;

        foreach ($this->redirect_array as $target)
        {
            if (!in_array($request_method, $target['methods']))
            {
                continue;
            }

            $route = $target['regex'];
            if (preg_match("!^{$route}$!", $request_uri, $matches))
            {
                $url = $target['url'];

                foreach ($target['args'] as $name => $match_index)
                {
                    $url = preg_replace("!\\{{$name}\\}!", $matches[$match_index], $url);
                }

                return [
                    'url' => (string) $url,
                    'redirect_type' => $target['redir_type'],
                ];
            }
        }

        return false;
    }

    /**
     * @return array{class: string, function: string, args: array<string, string>}|false
     */
    public function get_action(RequestInterface $request): array|false
    {
        $request_method = $request->getMethod();
        $request_uri = (string) $request->getUri()->getPath();

        // Remove base URL
        //
        if (substr($request_uri, 0, strlen($this->base_url)) === $this->base_url)
        {
            $request_uri = substr($request_uri, 4);
        }

        // Check if there is a route to follow
        //
        $matches = null;

        foreach ($this->route_array as $target)
        {
            if (!in_array($request_method, $target['methods']))
            {
                continue;
            }

            $route = $target['regex'];
            if (preg_match("!^{$route}$!", $request_uri, $matches))
            {
                $args = [];

                for ($i = 0; $i < count($target['args']); $i++)
                {
                    $args[$target['args'][$i]] = $matches[$i + 1];
                }

                $class = $target['class'];

                return [
                    'class' => $target['class'],
                    'function' => $target['function'],
                    'args' => $args,
                ];
            }
        }

        return false;
    }

    /**
     * @param array<string>|string $methods
     * @param array<string>        $args
     */
    public function register_action(string|array $methods, string $regex, string $class, string $function, array $args = []): void
    {
        if (!is_array($methods))
        {
            $methods = [$methods];
        }

        $this->route_array[] = [
            'methods' => $methods,
            'regex' => $regex,
            'class' => $class,
            'function' => $function,
            'args' => $args,
        ];
    }

    /**
     * @param array<string>|string $methods
     * @param array<string,int>    $args
     */
    public function register_redirect(string|array $methods, string $regex, string $url, int $type = 301, array $args = []): void
    {
        if (!is_array($methods))
        {
            $methods = [$methods];
        }

        $this->redirect_array[] = [
            'methods' => $methods,
            'regex' => $regex,
            'url' => $url,
            'redir_type' => $type,
            'args' => $args,
        ];
    }
}
