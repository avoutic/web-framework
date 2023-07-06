<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use WebFramework\SanityCheck\DatabaseCompatibility;
use WebFramework\SanityCheck\RequiredCoreConfig;

class BootstrapService
{
    private bool $runSanityChecks = true;

    public function __construct(
        private Container $container,
        private ConfigService $configService,
        private SanityCheckRunner $sanityCheckRunner,
        private string $appDir,
    ) {
    }

    public function bootstrap(): void
    {
        $this->initializeDebugging();
        $this->initializeTimezone();
        $this->initializeContainerWrapper();
        $this->initializePreload();
        $this->initializeDefines();

        if ($this->runSanityChecks)
        {
            $this->initializeCoreSanityChecks();
            $this->initializeAppSanityChecks();
            $this->runSanityChecks();
        }
    }

    protected function initializeDebugging(): void
    {
        // Enable debugging if configured
        //
        if ($this->configService->get('debug') === true)
        {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', '1');
        }
    }

    protected function initializeTimezone(): void
    {
        // Set default timezone
        //
        date_default_timezone_set($this->configService->get('timezone'));
    }

    protected function initializeContainerWrapper(): void
    {
        // As long as old-style code is in WebFramework we need ContainerWrapper
        //
        require_once __DIR__.'/ContainerWrapper.php';
        ContainerWrapper::setContainer($this->container);
    }

    protected function initializePreload(): void
    {
        // Check for special loads before anything else
        //
        $preload = $this->configService->get('preload');

        $filename = null;

        if ($preload === true)
        {
            if (file_exists("{$this->appDir}/src/preload.inc.php"))
            {
                $filename = 'src/preload.inc.php';
            }
            elseif (file_exists("{$this->appDir}/includes/preload.inc.php"))
            {
                $filename = 'includes/preload.inc.php';
            }
            else
            {
                throw new \InvalidArgumentException("'preload.inc.php' does not exist in either src/ or includes/");
            }
        }
        elseif (is_string($preload) && strlen($preload))
        {
            $filename = $preload;
        }

        if ($filename)
        {
            if (!file_exists("{$this->appDir}/{$filename}"))
            {
                throw new \InvalidArgumentException("The file '{$filename}' does not exist");
            }

            require_once "{$this->appDir}/{$filename}";
        }
    }

    protected function initializeDefines(): void
    {
        // Load global and site specific defines
        //
        require_once __DIR__.'/../Defines.php';
    }

    protected function initializeCoreSanityChecks(): void
    {
        $this->sanityCheckRunner->add(RequiredCoreConfig::class, []);
        $this->sanityCheckRunner->add(DatabaseCompatibility::class, []);
    }

    protected function initializeAppSanityChecks(): void
    {
        $modules = $this->configService->get('sanity_check_modules');

        foreach ($modules as $class => $config)
        {
            $this->sanityCheckRunner->add($class, $config);
        }
    }

    protected function runSanityChecks(): void
    {
        $this->sanityCheckRunner->execute();
    }

    public function setSanityCheckForceRun(): void
    {
        $this->sanityCheckRunner->forceRun();
    }

    public function setSanityCheckFixing(): void
    {
        $this->sanityCheckRunner->allowFixing();
    }

    public function setSanityCheckVerbose(): void
    {
        $this->sanityCheckRunner->setVerbose();
    }

    public function skipSanityChecks(): void
    {
        $this->runSanityChecks = false;
    }
}
