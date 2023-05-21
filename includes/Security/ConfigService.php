<?php

namespace WebFramework\Security;

class ConfigService
{
    public function __construct(
        protected string $auth_dir,
    ) {
    }

    public function get_auth_config(string $name): mixed
    {
        $auth_config = $this->load_file($name);

        if (!is_array($auth_config) && !strlen($auth_config))
        {
            throw new \RuntimeException('Auth Config '.$name.' invalid');
        }

        return $auth_config;
    }

    /**
     * @return array<mixed>|string
     */
    protected function load_file(string $name): array|string
    {
        $filename = "{$this->auth_dir}/{$name}.php";

        if (!file_exists($filename))
        {
            throw new \RuntimeException("Auth Config {$name} does not exist");
        }

        return require $filename;
    }
}
