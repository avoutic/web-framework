<?php

namespace WebFramework\Core;

class ConfigBuilder
{
    /** @var array<string, mixed> */
    private array $globalConfig = [];

    public function __construct(
        private string $appDir,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public function mergeConfigOnTop(array $config): void
    {
        // Merge configurations
        //
        $this->globalConfig = array_replace_recursive($this->globalConfig, $config);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadConfigFile(string $configLocation): array
    {
        if ($configLocation[0] == '?')
        {
            $configLocation = substr($configLocation, 1);

            if (!file_exists("{$this->appDir}{$configLocation}"))
            {
                return [];
            }
        }

        $fileConfig = require "{$this->appDir}{$configLocation}";
        if (!is_array($fileConfig))
        {
            throw new \RuntimeException("No valid config array found in '{$configLocation}'");
        }

        return $fileConfig;
    }

    /**
     * @param array<string> $configs Config files to merge on top of each other in order.
     *                               File locations should be relative to the app dir
     *                               including leading /. If it starts with a '?' the file
     *                               does not have to be present.
     *
     * @return array<string, mixed>
     */
    public function buildConfig(array $configs): array
    {
        foreach ($configs as $configLocation)
        {
            $fileConfig = $this->loadConfigFile($configLocation);

            $this->mergeConfigOnTop($fileConfig);
        }

        return $this->globalConfig;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->globalConfig;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFlattenedConfig(): array
    {
        return $this->flatten($this->globalConfig);
    }

    /**
     * @param array<string, mixed> $array
     *
     * @return array<string, mixed>
     */
    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $result = $result + $this->flatten($value, $prefix.$key.'.');
            }
            else
            {
                $result[$prefix.$key] = $value;
            }
        }

        return $result;
    }
}
