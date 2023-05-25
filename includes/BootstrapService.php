<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;

class BootstrapService
{
    private bool $run_sanity_checks = true;

    public function __construct(
        private Container $container,
        private ConfigService $config_service,
        private SanityCheckRunner $sanity_check_runner,
        private string $app_dir,
    ) {
    }

    public function bootstrap(): void
    {
        $this->initialize_debugging();
        $this->initialize_timezone();
        $this->initialize_container_wrapper();
        $this->initialize_preload();
        $this->initialize_defines();

        if ($this->run_sanity_checks)
        {
            $this->initialize_core_sanity_checks();
            $this->initialize_app_sanity_checks();
            $this->run_sanity_checks();
        }
    }

    protected function initialize_debugging(): void
    {
        // Enable debugging if configured
        //
        if ($this->config_service->get('debug') === true)
        {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', '1');
        }
    }

    protected function initialize_timezone(): void
    {
        // Set default timezone
        //
        date_default_timezone_set($this->config_service->get('timezone'));
    }

    protected function initialize_container_wrapper(): void
    {
        // As long as old-style code is in WebFramework we need ContainerWrapper
        //
        require_once __DIR__.'/ContainerWrapper.php';
        ContainerWrapper::setContainer($this->container);
    }

    protected function initialize_preload(): void
    {
        // Check for special loads before anything else
        //
        if ($this->config_service->get('preload') === true)
        {
            if (!file_exists("{$this->app_dir}/includes/preload.inc.php"))
            {
                throw new \InvalidArgumentException('The file "includes/preload.inc.php" does not exist');
            }

            require_once "{$this->app_dir}/includes/preload.inc.php";
        }
    }

    protected function initialize_defines(): void
    {
        // Load global and site specific defines
        //
        require_once __DIR__.'/defines.inc.php';
    }

    protected function initialize_core_sanity_checks(): void
    {
        $this->sanity_check_runner->add(SanityCheck\RequiredCoreConfig::class, []);
        $this->sanity_check_runner->add(SanityCheck\DatabaseCompatibility::class, []);
    }

    protected function initialize_app_sanity_checks(): void
    {
        $modules = $this->config_service->get('sanity_check_modules');

        foreach ($modules as $class => $config)
        {
            $this->sanity_check_runner->add($class, $config);
        }
    }

    protected function run_sanity_checks(): void
    {
        $this->sanity_check_runner->execute();
    }

    public function set_sanity_check_force_run(): void
    {
        $this->sanity_check_runner->force_run();
    }

    public function set_sanity_check_fixing(): void
    {
        $this->sanity_check_runner->allow_fixing();
    }

    public function set_sanity_check_verbose(): void
    {
        $this->sanity_check_runner->set_verbose();
    }

    public function skip_sanity_checks(): void
    {
        $this->run_sanity_checks = false;
    }
}
