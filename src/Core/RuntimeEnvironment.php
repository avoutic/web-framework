<?php

namespace WebFramework\Core;

class RuntimeEnvironment
{
    public function __construct(
        private string $appDir,
        private string $baseUrl,
        private bool $debug,
        private string $httpMode,
        private bool $production,
        private string $serverName,
    ) {
    }

    public function getAppDir(): string
    {
        return $this->appDir;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function getHttpMode(): string
    {
        return $this->httpMode;
    }

    public function isProduction(): bool
    {
        return $this->production;
    }

    public function getServerName(): string
    {
        return $this->serverName;
    }
}
