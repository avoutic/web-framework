<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;

class SanityCheckRunner
{
    /** @var array<array{class: string, config: array<mixed>}> */
    private array $modules = [];
    private bool $force_run = false;
    private bool $verbose = false;
    private bool $fixing = false;

    /**
     * @param array<string, mixed> $build_info
     */
    public function __construct(
        private Container $container,
        private StoredValues $stored_values,
        private array $build_info,
    ) {
    }

    /**
     * @param array<mixed> $module_config
     */
    public function add(string $module_class, array $module_config): void
    {
        $this->modules[] = [
            'class' => $module_class,
            'config' => $module_config,
        ];
    }

    public function execute(): bool
    {
        if (!count($this->modules))
        {
            return true;
        }

        $commit = $this->build_info['commit'];

        $needs_run = $this->needs_run($commit);
        if (!$needs_run)
        {
            return true;
        }

        foreach ($this->modules as $info)
        {
            $module = $this->container->get($info['class']);
            $module->set_config($info['config']);

            if ($this->fixing)
            {
                $module->allow_fixing();
            }

            if ($this->verbose)
            {
                $module->set_verbose();
            }

            $result = $module->perform_checks();

            if ($result === false)
            {
                throw new \RuntimeException('Sanity check failed');
            }
        }

        $this->register_run($commit);

        return true;
    }

    protected function needs_run(?string $commit): bool
    {
        if ($this->force_run)
        {
            return true;
        }

        if ($commit == null)
        {
            // We are in live / development code.
            // Prevent flooding. Only start check once per
            // five seconds.
            //
            $last_timestamp = (int) $this->stored_values->get_value('last_check', '0');

            if (time() - $last_timestamp < 5)
            {
                return false;
            }

            $this->stored_values->set_value('last_check', (string) time());

            return true;
        }

        // We are in an image
        // Only check if this commit was not yet successfully checked
        //
        $checked = $this->stored_values->get_value('checked_'.$commit, '0');

        return ($checked === '0');
    }

    protected function register_run(?string $commit): void
    {
        // Register successful check of this commit
        //
        if ($commit !== null)
        {
            $this->stored_values->set_value('checked_'.$commit, '1');
        }
    }

    public function force_run(): void
    {
        $this->force_run = true;
    }

    public function allow_fixing(): void
    {
        $this->fixing = true;
    }

    public function set_verbose(): void
    {
        $this->verbose = true;
    }
}
