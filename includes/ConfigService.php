<?php

namespace WebFramework\Core;

class ConfigService
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private array $config,
    ) {
    }

    public function get(string $location = ''): mixed
    {
        if (!strlen($location))
        {
            return $this->config;
        }

        $path = explode('.', $location);
        $part = $this->config;

        foreach ($path as $step)
        {
            if (!isset($part[$step]))
            {
                throw new \InvalidArgumentException("Missing configuration {$location}");
            }

            $part = $part[$step];
        }

        return $part;
    }
}
