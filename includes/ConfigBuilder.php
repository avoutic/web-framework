<?php

namespace WebFramework\Core;

class ConfigBuilder
{
    /** @var array<string, mixed> */
    private array $global_config = [];

    public function __construct(
        private string $app_dir,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public function merge_config_on_top(array $config): void
    {
        // Merge configurations
        //
        $this->global_config = array_replace_recursive($this->global_config, $config);
    }

    /**
     * @return array<string, mixed>
     */
    private function load_config_file(string $config_location): array
    {
        if ($config_location[0] == '?')
        {
            $config_location = substr($config_location, 1);

            if (!file_exists("{$this->app_dir}{$config_location}"))
            {
                return [];
            }
        }

        $file_config = require "{$this->app_dir}{$config_location}";
        if (!is_array($file_config))
        {
            throw new \RuntimeException("No valid config array found in '{$config_location}'");
        }

        return $file_config;
    }

    public function populate_internals(string $server_name, string $host_name): void
    {
        $this->merge_config_on_top([
            'app_dir' => $this->app_dir,
        ]);

        /*
         * Force server_name and host_name to 'app' if run locally.
         * Otherwise only set dynamically to given parameters if not defined in the merged config.
         * server_name is meant to be used in urls and can contain port information.
         * host_name is meant to be used as host and cannot contain port information.
         */
        if (!strlen($server_name))
        {
            $this->merge_config_on_top([
                'server_name' => 'app',
                'host_name' => 'app',
            ]);

            return;
        }

        if (!strlen($this->global_config['server_name'] ?? ''))
        {
            $this->merge_config_on_top([
                'server_name' => $server_name,
            ]);
        }

        if (!strlen($this->global_config['host_name'] ?? ''))
        {
            $this->merge_config_on_top([
                'host_name' => $host_name,
            ]);
        }
    }

    /**
     * @param array<string> $configs Config files to merge on top of each other in order.
     *                               File locations should be relative to the app dir
     *                               including leading /. If it starts with a '?' the file
     *                               does not have to be present.
     */
    public function build_config(array $configs): void
    {
        foreach ($configs as $config_location)
        {
            $file_config = $this->load_config_file($config_location);

            $this->merge_config_on_top($file_config);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function get_config(): array
    {
        return $this->global_config;
    }

    public function get_flattened_config(): array
    {
        return $this->flatten($this->global_config);
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
