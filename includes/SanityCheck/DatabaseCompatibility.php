<?php

namespace WebFramework\SanityCheck;

use WebFramework\Core\ConfigService;
use WebFramework\Core\Database;
use WebFramework\Core\StoredValues;

class DatabaseCompatibility extends Base
{
    public function __construct(
        private Database $database,
        private ConfigService $configService,
        private bool $checkDb = true,
        private bool $checkWfDbVersion = true,
        private bool $checkAppDbVersion = true,
    ) {
    }

    public function performChecks(): bool
    {
        // Verify all versions for compatibility
        //
        $requiredWfVersion = FRAMEWORK_VERSION;
        $supportedWfVersion = $this->configService->get('versions.supported_framework');

        $this->addOutput('Checking WF version presence:'.PHP_EOL);

        if ($supportedWfVersion == -1)
        {
            $this->addOutput(
                '   No supported Framework version configured'.PHP_EOL.
                '   There is no supported framework version provided in "versions.supported_framework".'.PHP_EOL.
                "   The current version is {$requiredWfVersion} of this Framework.".PHP_EOL
            );

            return false;
        }

        $this->addOutput('  Pass'.PHP_EOL.PHP_EOL);

        $this->addOutput('Checking WF version match:'.PHP_EOL);

        if ($requiredWfVersion != $supportedWfVersion)
        {
            $this->addOutput(
                '   Framework version mismatch'.PHP_EOL.
                '   Please make sure that this app is upgraded to support version '.PHP_EOL.
                "   {$requiredWfVersion} of this Framework.".PHP_EOL
            );

            return false;
        }

        $this->addOutput('  Pass'.PHP_EOL.PHP_EOL);

        if ($this->configService->get('database_enabled') != true || !$this->checkDb)
        {
            return true;
        }

        $requiredWfDbVersion = FRAMEWORK_DB_VERSION;
        $requiredAppDbVersion = $this->configService->get('versions.required_app_db');

        // Check if base table is present
        //
        $this->addOutput('Checking for config_values table:'.PHP_EOL);

        if (!$this->database->tableExists('config_values'))
        {
            $this->addOutput(
                '   Database missing config_values table'.PHP_EOL.
                '   Please make sure that the core Framework database scheme has been applied. (by running db_init script)'.PHP_EOL
            );

            return false;
        }

        $this->addOutput('  Pass'.PHP_EOL.PHP_EOL);

        $storedValues = new StoredValues($this->database, 'db');
        $currentWfDbVersion = $storedValues->getValue('wf_db_version', '0');
        $currentAppDbVersion = $storedValues->getValue('app_db_version', '1');

        $this->addOutput('Checking for compatible Framework Database verion:'.PHP_EOL);

        if ($this->checkWfDbVersion && $requiredWfDbVersion != $currentWfDbVersion)
        {
            $this->addOutput(
                '   Framework Database version mismatch'.PHP_EOL.
                '   Please make sure that the latest Framework database changes for version '.PHP_EOL.
                "   {$requiredWfDbVersion} of the scheme are applied.".PHP_EOL
            );

            return false;
        }

        $this->addOutput('  Pass'.PHP_EOL);

        $this->addOutput('Checking for compatible App Database verion:'.PHP_EOL);

        if ($this->checkAppDbVersion && $requiredAppDbVersion > 0 && $currentAppDbVersion == 0)
        {
            $this->addOutput(
                '   No app DB present'.PHP_EOL.
                '   Config (versions.required_app_db) indicates an App DB should be present. None found.'.PHP_EOL
            );

            return false;
        }

        if ($this->checkAppDbVersion && $requiredAppDbVersion > $currentAppDbVersion)
        {
            $this->addOutput(
                '   Outdated version of the app DB'.PHP_EOL.
                "   Please make sure that the app DB scheme is at least {$requiredAppDbVersion}. (Current: {$currentAppDbVersion})".PHP_EOL
            );

            return false;
        }

        $this->addOutput('  Pass'.PHP_EOL.PHP_EOL);

        return true;
    }
}
