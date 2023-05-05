<?php

namespace WebFramework\Core\SanityCheck;

use WebFramework\Core\SanityCheckInterface;
use WebFramework\Core\WF;

abstract class Base implements SanityCheckInterface
{
    protected bool $allow_fixing = false;
    protected bool $verbose = false;

    /** @var array<string, mixed> */
    protected array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    protected function get_app_dir(): string
    {
        // Determine app dir
        //
        $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);

        $filename = $reflection->getFileName();
        WF::verify($filename !== false, 'Failed to retrieve filename');

        return dirname($filename, 3);
    }

    abstract public function perform_checks(): bool;

    public function allow_fixing(): void
    {
        $this->allow_fixing = true;
    }

    public function set_verbose(): void
    {
        $this->verbose = true;
    }

    protected function add_output(string $str): void
    {
        if ($this->verbose)
        {
            echo $str;
        }
    }
}
