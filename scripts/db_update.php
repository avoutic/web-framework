<?php

// Global configuration
//
if (!file_exists(__DIR__.'/../vendor/autoload.php'))
{
    exit('Composer not initialized');
}

require_once __DIR__.'/../vendor/autoload.php';

use WebFramework\Core\BootstrapService;
use WebFramework\Core\ConfigBuilder;
use WebFramework\Core\DatabaseManager;
use WebFramework\Core\DebugService;

header('content-type: text/plain');

// Build config
//
$appDir = __DIR__.'/..';
$configs = [
    '/config/base_config.php',
    '/config/config.php',
    '?/config/config_local.php',
];

$configBuilder = new ConfigBuilder($appDir);
$config = $configBuilder->buildConfig(
    $configs,
);

// Build container
//
$builder = new DI\ContainerBuilder();
$builder->addDefinitions(['config_tree' => $configBuilder->getConfig()]);
$builder->addDefinitions($configBuilder->getFlattenedConfig());

foreach ($config['definition_files'] as $file)
{
    $builder->addDefinitions("{$appDir}/definitions/{$file}");
}

$container = $builder->build();

try
{
    $bootstrapService = $container->get(BootstrapService::class);

    $bootstrapService->skipSanityChecks();

    $bootstrapService->bootstrap();

    $dbManager = $container->get(DatabaseManager::class);

    $currentVersion = $dbManager->getCurrentVersion();
    $requiredVersion = $container->get('versions.required_app_db');

    if ($requiredVersion <= $currentVersion)
    {
        echo 'Nothing to be done'.PHP_EOL;

        exit();
    }

    // Retrieve relevant change set
    //
    $nextVersion = $currentVersion + 1;

    while ($nextVersion <= $requiredVersion)
    {
        $versionFile = "{$appDir}/db_scheme/{$nextVersion}.inc.php";

        if (!file_exists($versionFile))
        {
            echo " - No changeset for {$nextVersion} available".PHP_EOL;

            exit();
        }

        $changeSet = require $versionFile;
        if (!is_array($changeSet))
        {
            throw new \RuntimeException('No change set array found');
        }

        $dbManager->execute($changeSet);
        $currentVersion = $dbManager->getCurrentVersion();
        $nextVersion = $currentVersion + 1;
    }
}
catch (Throwable $e)
{
    echo PHP_EOL.PHP_EOL;

    if (!$container->get('debug'))
    {
        echo 'Unhandled exception'.PHP_EOL;

        exit();
    }

    $debugService = $container->get(DebugService::class);
    $errorReport = $debugService->getThrowableReport($e);

    echo $errorReport['message'];
}
