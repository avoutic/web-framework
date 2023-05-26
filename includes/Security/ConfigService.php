<?php

namespace WebFramework\Security;

class ConfigService
{
    public function __construct(
        protected string $appDir,
        protected string $authDir,
    ) {
    }

    public function getAuthConfig(string $name): mixed
    {
        $authConfig = $this->loadFile($name);

        if (!is_array($authConfig) && !strlen($authConfig))
        {
            throw new \RuntimeException('Auth Config '.$name.' invalid');
        }

        return $authConfig;
    }

    /**
     * @return array<mixed>|string
     */
    protected function loadFile(string $name): array|string
    {
        $filename = "{$this->appDir}{$this->authDir}/{$name}.php";

        if (!file_exists($filename))
        {
            throw new \RuntimeException("Auth Config {$name} does not exist");
        }

        return require $filename;
    }
}
