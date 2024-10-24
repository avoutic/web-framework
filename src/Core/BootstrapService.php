<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use WebFramework\SanityCheck\DatabaseCompatibility;
use WebFramework\SanityCheck\RequiredCoreConfig;

/**
 * Class BootstrapService.
 *
 * This service is responsible for bootstrapping the application by initializing
 * various components and running sanity checks.
 */
class BootstrapService
{
    /** @var bool Whether to run sanity checks during bootstrap */
    private bool $runSanityChecks = true;

    /**
     * BootstrapService constructor.
     *
     * @param Container          $container          The dependency injection container
     * @param ConfigService      $configService      The configuration service
     * @param RuntimeEnvironment $runtimeEnvironment The runtime environment service
     * @param SanityCheckRunner  $sanityCheckRunner  The sanity check runner service
     */
    public function __construct(
        private Container $container,
        private ConfigService $configService,
        private RuntimeEnvironment $runtimeEnvironment,
        private SanityCheckRunner $sanityCheckRunner,
    ) {}

    /**
     * Bootstrap the application.
     *
     * This method initializes various components of the application and runs sanity checks if enabled.
     */
    public function bootstrap(): void
    {
        $this->initializeDebugging();
        $this->initializeTimezone();
        $this->initializeContainerWrapper();
        $this->initializePreload();
        $this->initializeDefines();
        $this->initializeTranslations();

        if ($this->runSanityChecks)
        {
            $this->initializeCoreSanityChecks();
            $this->initializeAppSanityChecks();
            $this->runSanityChecks();
        }
    }

    /**
     * Initialize debugging settings based on configuration.
     *
     * @uses config key 'debug' to determine if debugging should be enabled
     */
    private function initializeDebugging(): void
    {
        // Enable debugging if configured
        //
        if ($this->configService->get('debug') === true)
        {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', '1');
        }
    }

    /**
     * Set the default timezone based on configuration.
     *
     * @uses config key 'timezone' to set the default timezone
     */
    private function initializeTimezone(): void
    {
        // Set default timezone
        //
        date_default_timezone_set($this->configService->get('timezone'));
    }

    /**
     * Initialize the ContainerWrapper for backward compatibility.
     */
    private function initializeContainerWrapper(): void
    {
        // As long as old-style code is in WebFramework we need ContainerWrapper
        //
        require_once __DIR__.'/ContainerWrapper.php';
        ContainerWrapper::setContainer($this->container);
    }

    /**
     * Initialize preload files if configured.
     *
     * @throws \InvalidArgumentException If the preload file does not exist
     *
     * @uses config key 'preload' to determine if and which preload file to include
     */
    private function initializePreload(): void
    {
        // Check for special loads before anything else
        //
        $preload = $this->configService->get('preload');

        $filename = null;

        if ($preload === true)
        {
            if (file_exists("{$this->runtimeEnvironment->getAppDir()}/src/preload.inc.php"))
            {
                $filename = 'src/preload.inc.php';
            }
            elseif (file_exists("{$this->runtimeEnvironment->getAppDir()}/includes/preload.inc.php"))
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
            if (!file_exists("{$this->runtimeEnvironment->getAppDir()}/{$filename}"))
            {
                throw new \InvalidArgumentException("The file '{$filename}' does not exist");
            }

            require_once "{$this->runtimeEnvironment->getAppDir()}/{$filename}";
        }
    }

    /**
     * Load global and site-specific defines.
     */
    private function initializeDefines(): void
    {
        // Load global and site specific defines
        //
        require_once __DIR__.'/../Defines.php';
    }

    /**
     * Load translation helpers.
     */
    private function initializeTranslations(): void
    {
        // Load translation helpers
        //
        require_once __DIR__.'/../Translations.php';
    }

    /**
     * Initialize core sanity checks RequiredCoreConfig and DatabaseCompatibility.
     */
    private function initializeCoreSanityChecks(): void
    {
        $this->sanityCheckRunner->add(RequiredCoreConfig::class, []);
        $this->sanityCheckRunner->add(DatabaseCompatibility::class, []);
    }

    /**
     * Initialize application-specific sanity checks.
     *
     * The sanity check modules are configured in the 'sanity_check_modules' key
     * of the configuration.
     * The format is an associative array with fully qualified class names as keys
     * and their respective configuration arrays as values.
     *
     * @uses config key 'sanity_check_modules' to determine which sanity checks to run
     */
    private function initializeAppSanityChecks(): void
    {
        $modules = $this->configService->get('sanity_check_modules');

        foreach ($modules as $class => $config)
        {
            $this->sanityCheckRunner->add($class, $config);
        }
    }

    /**
     * Run all registered sanity checks.
     */
    private function runSanityChecks(): void
    {
        $this->sanityCheckRunner->execute();
    }

    /**
     * Set the sanity check runner to force run all checks.
     */
    public function setSanityCheckForceRun(): void
    {
        $this->sanityCheckRunner->forceRun();
    }

    /**
     * Allow the sanity check runner to fix issues.
     */
    public function setSanityCheckFixing(): void
    {
        $this->sanityCheckRunner->allowFixing();
    }

    /**
     * Set the sanity check runner to verbose mode.
     */
    public function setSanityCheckVerbose(): void
    {
        $this->sanityCheckRunner->setVerbose();
    }

    /**
     * Skip running sanity checks during bootstrap.
     */
    public function skipSanityChecks(): void
    {
        $this->runSanityChecks = false;
    }
}
