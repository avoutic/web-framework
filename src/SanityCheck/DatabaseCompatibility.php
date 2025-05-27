<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\SanityCheck;

use Psr\Log\LoggerInterface;
use WebFramework\Core\ConfigService;
use WebFramework\Core\Database;
use WebFramework\Support\StoredValuesService;

/**
 * Class DatabaseCompatibility.
 *
 * Performs sanity checks related to database compatibility.
 */
class DatabaseCompatibility extends Base
{
    /**
     * DatabaseCompatibility constructor.
     *
     * @param Database            $database            The database service
     * @param ConfigService       $configService       The configuration service
     * @param LoggerInterface     $logger              The logger
     * @param StoredValuesService $storedValuesService The stored values service
     * @param bool                $checkDb             Whether to check the database
     * @param bool                $checkWfDbVersion    Whether to check the WebFramework database version
     * @param bool                $checkAppDbVersion   Whether to check the application database version
     */
    public function __construct(
        private Database $database,
        private ConfigService $configService,
        private LoggerInterface $logger,
        private StoredValuesService $storedValuesService,
        private bool $checkDb = true,
        private bool $checkWfDbVersion = true,
        private bool $checkAppDbVersion = true,
    ) {}

    /**
     * Check if the supported framework version is configured and the framework version matches the required version.
     *
     * @return bool True if check passes, false otherwise
     */
    private function checkFrameworkVersion(): bool
    {
        $requiredWfVersion = FRAMEWORK_VERSION;
        $supportedWfVersion = $this->configService->get('versions.supported_framework');

        if ($supportedWfVersion == -1)
        {
            $this->logger->emergency('No supported Framework version configured', ['required_wf_version' => $requiredWfVersion, 'supported_wf_version' => $supportedWfVersion]);
            $this->addOutput(
                '   No supported Framework version configured'.PHP_EOL.
                '   There is no supported framework version provided in "versions.supported_framework".'.PHP_EOL.
                "   The current version is {$requiredWfVersion} of this Framework.".PHP_EOL
            );

            return false;
        }

        if ($requiredWfVersion != $supportedWfVersion)
        {
            $this->logger->emergency('Framework version mismatch', ['required_wf_version' => $requiredWfVersion, 'supported_wf_version' => $supportedWfVersion]);
            $this->addOutput(
                '   Framework version mismatch'.PHP_EOL.
                '   Please make sure that this app is upgraded to support version '.PHP_EOL.
                "   {$requiredWfVersion} of this Framework.".PHP_EOL
            );

            return false;
        }

        return true;
    }

    /**
     * Check if the stored_values table exists.
     *
     * @return bool True if check passes, false otherwise
     */
    private function checkStoredValuesTable(): bool
    {
        if (!$this->database->tableExists('stored_values'))
        {
            $this->logger->emergency('Database missing stored_values table');
            $this->addOutput(
                '   Database missing stored_values table'.PHP_EOL.
                '   Please make sure that the core Framework database scheme has been applied. (by running db:init task)'.PHP_EOL
            );

            return false;
        }

        return true;
    }

    /**
     * Check if the framework database version is compatible.
     *
     * @return bool True if check passes, false otherwise
     */
    private function checkFrameworkDbVersion(): bool
    {
        if (!$this->checkWfDbVersion)
        {
            return true;
        }

        $requiredWfDbVersion = FRAMEWORK_DB_VERSION;
        $currentWfDbVersion = $this->storedValuesService->getValue('wf_db_version', '0');

        if ($requiredWfDbVersion != $currentWfDbVersion)
        {
            $this->logger->emergency('Framework Database version mismatch', ['required_wf_db_version' => $requiredWfDbVersion, 'current_wf_db_version' => $currentWfDbVersion]);
            $this->addOutput(
                '   Framework Database version mismatch'.PHP_EOL.
                '   Please make sure that the latest Framework database changes for version '.PHP_EOL.
                "   {$requiredWfDbVersion} of the scheme are applied.".PHP_EOL
            );

            return false;
        }

        return true;
    }

    /**
     * Check if the application database version is compatible.
     *
     * @return bool True if check passes, false otherwise
     */
    private function checkAppDbVersion(): bool
    {
        if (!$this->checkAppDbVersion)
        {
            return true;
        }

        $requiredAppDbVersion = $this->configService->get('versions.required_app_db');
        $currentAppDbVersion = $this->storedValuesService->getValue('app_db_version', '1');

        if ($requiredAppDbVersion > 0 && $currentAppDbVersion == 0)
        {
            $this->logger->emergency('No app DB present', ['required_app_db_version' => $requiredAppDbVersion, 'current_app_db_version' => $currentAppDbVersion]);
            $this->addOutput(
                '   No app DB present'.PHP_EOL.
                '   Config (versions.required_app_db) indicates an App DB should be present. None found.'.PHP_EOL
            );

            return false;
        }

        if ($requiredAppDbVersion > $currentAppDbVersion)
        {
            $this->logger->emergency('Outdated version of the app DB', ['required_app_db_version' => $requiredAppDbVersion, 'current_app_db_version' => $currentAppDbVersion]);
            $this->addOutput(
                '   Outdated version of the app DB'.PHP_EOL.
                "   Please make sure that the app DB scheme is at least {$requiredAppDbVersion}. (Current: {$currentAppDbVersion})".PHP_EOL
            );

            return false;
        }

        return true;
    }

    /**
     * Perform database compatibility checks.
     *
     * @return bool True if all checks pass, false otherwise
     */
    public function performChecks(): bool
    {
        $this->addOutput('Checking WF version:'.PHP_EOL);
        if (!$this->checkFrameworkVersion())
        {
            return false;
        }
        $this->addOutput('  Pass'.PHP_EOL.PHP_EOL);

        if ($this->configService->get('database_enabled') != true || !$this->checkDb)
        {
            return true;
        }

        $this->addOutput('Checking for stored_values table:'.PHP_EOL);
        if (!$this->checkStoredValuesTable())
        {
            return false;
        }
        $this->addOutput('  Pass'.PHP_EOL.PHP_EOL);

        $this->addOutput('Checking for compatible Framework Database verion:'.PHP_EOL);
        if (!$this->checkFrameworkDbVersion())
        {
            return false;
        }
        $this->addOutput('  Pass'.PHP_EOL);

        $this->addOutput('Checking for compatible App Database verion:'.PHP_EOL);
        if (!$this->checkAppDbVersion())
        {
            return false;
        }
        $this->addOutput('  Pass'.PHP_EOL.PHP_EOL);

        return true;
    }
}
