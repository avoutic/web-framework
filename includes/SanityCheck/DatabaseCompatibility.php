<?php

namespace WebFramework\Core\SanityCheck;

use WebFramework\Core\ConfigService;
use WebFramework\Core\Database;
use WebFramework\Core\StoredValues;

class DatabaseCompatibility extends Base
{
    public function __construct(
        private Database $database,
        private ConfigService $config_service,
        private bool $check_db = true,
        private bool $check_wf_db_version = true,
        private bool $check_app_db_version = true,
    ) {
    }

    public function perform_checks(): bool
    {
        // Verify all versions for compatibility
        //
        $required_wf_version = FRAMEWORK_VERSION;
        $supported_wf_version = $this->config_service->get('versions.supported_framework');

        $this->add_output('Checking WF version presence:'.PHP_EOL);

        if ($supported_wf_version == -1)
        {
            $this->add_output(
                '   No supported Framework version configured'.PHP_EOL.
                '   There is no supported framework version provided in "versions.supported_framework".'.PHP_EOL.
                "   The current version is {$required_wf_version} of this Framework.".PHP_EOL
            );

            return false;
        }

        $this->add_output('  Pass'.PHP_EOL.PHP_EOL);

        $this->add_output('Checking WF version match:'.PHP_EOL);

        if ($required_wf_version != $supported_wf_version)
        {
            $this->add_output(
                '   Framework version mismatch'.PHP_EOL.
                '   Please make sure that this app is upgraded to support version '.PHP_EOL.
                "   {$required_wf_version} of this Framework.".PHP_EOL
            );

            return false;
        }

        $this->add_output('  Pass'.PHP_EOL.PHP_EOL);

        if ($this->config_service->get('database_enabled') != true || !$this->check_db)
        {
            return true;
        }

        $required_wf_db_version = FRAMEWORK_DB_VERSION;
        $required_app_db_version = $this->config_service->get('versions.required_app_db');

        // Check if base table is present
        //
        $this->add_output('Checking for config_values table:'.PHP_EOL);

        if (!$this->database->table_exists('config_values'))
        {
            $this->add_output(
                '   Database missing config_values table'.PHP_EOL.
                '   Please make sure that the core Framework database scheme has been applied. (by running db_init script)'.PHP_EOL
            );

            return false;
        }

        $this->add_output('  Pass'.PHP_EOL.PHP_EOL);

        $stored_values = new StoredValues($this->database, 'db');
        $current_wf_db_version = $stored_values->get_value('wf_db_version', '0');
        $current_app_db_version = $stored_values->get_value('app_db_version', '1');

        $this->add_output('Checking for compatible Framework Database verion:'.PHP_EOL);

        if ($this->check_wf_db_version && $required_wf_db_version != $current_wf_db_version)
        {
            $this->add_output(
                '   Framework Database version mismatch'.PHP_EOL.
                '   Please make sure that the latest Framework database changes for version '.PHP_EOL.
                "   {$required_wf_db_version} of the scheme are applied.".PHP_EOL
            );

            return false;
        }

        $this->add_output('  Pass'.PHP_EOL);

        $this->add_output('Checking for compatible App Database verion:'.PHP_EOL);

        if ($this->check_app_db_version && $required_app_db_version > 0 && $current_app_db_version == 0)
        {
            $this->add_output(
                '   No app DB present'.PHP_EOL.
                '   Config (versions.required_app_db) indicates an App DB should be present. None found.'.PHP_EOL
            );

            return false;
        }

        if ($this->check_app_db_version && $required_app_db_version > $current_app_db_version)
        {
            $this->add_output(
                '   Outdated version of the app DB'.PHP_EOL.
                "   Please make sure that the app DB scheme is at least {$required_app_db_version}. (Current: {$current_app_db_version})".PHP_EOL
            );

            return false;
        }

        $this->add_output('  Pass'.PHP_EOL.PHP_EOL);

        return true;
    }
}
