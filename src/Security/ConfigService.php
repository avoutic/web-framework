<?php

namespace WebFramework\Security;

use WebFramework\Core\RuntimeEnvironment;

class ConfigService
{
    public function __construct(
        private RuntimeEnvironment $runtimeEnvironment,
        private string $authDir,
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
    private function loadFile(string $name): array|string
    {
        $filename = "{$this->runtimeEnvironment->getAppDir()}{$this->authDir}/{$name}.php";

        if (!file_exists($filename))
        {
            throw new \RuntimeException("Auth Config {$name} does not exist");
        }

        return require $filename;
    }
}
