<?php

namespace WebFramework;

use DI;
use Psr\Container\ContainerInterface;

return [
    \Cache\Adapter\Redis\RedisCachePool::class => function (ContainerInterface $c) {
        $secureConfigService = $c->get(Security\ConfigService::class);

        $cacheConfig = $secureConfigService->getAuthConfig('redis');

        $redisClient = new \Redis();

        $result = $redisClient->pconnect(
            $cacheConfig['hostname'],
            $cacheConfig['port'],
            1,
            'wf',
            0,
            0,
            ['auth' => $cacheConfig['password']]
        );

        if ($result !== true)
        {
            throw new \RuntimeException('Redis Cache connection failed');
        }

        return new \Cache\Adapter\Redis\RedisCachePool($redisClient);
    },
    \Latte\Engine::class => DI\create(),
    \Slim\Psr7\Factory\ResponseFactory::class => DI\create(),

    'app_dir' => function (ContainerInterface $c) {
        // Determine app dir
        //
        $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);

        return dirname($reflection->getFileName() ?: '', 3);
    },
    'build_info' => function (ContainerInterface $c) {
        $debugService = $c->get(Core\DebugService::class);

        return $debugService->getBuildInfo();
    },
    'full_base_url' => DI\string('{http_mode}://{server_name}{base_url}'),
    'host_name' => $_SERVER['SERVER_NAME'] ?? 'app',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'app',

    'DbStoredValues' => DI\autowire(Core\StoredValues::class)
        ->constructor(
            module: 'db',
        ),
    'SanityCheckStoredValues' => DI\autowire(Core\StoredValues::class)
        ->constructor(
            module: 'sanity_check',
        ),

    Core\BootstrapService::class => DI\autowire()
        ->constructor(
            appDir: DI\get('app_dir'),
        ),
    Core\BrowserSessionService::class => DI\autowire()
        ->method(
            'start',
            hostName: DI\get('host_name'),
            httpMode: DI\get('http_mode'),
        ),
    Core\Cache::class => DI\autowire(Core\NullCache::class),
    Core\ConfigService::class => DI\autowire()
        ->constructor(
            DI\get('config_tree'),
        ),
    Core\Database::class => function (ContainerInterface $c) {
        $secureConfigService = $c->get(Security\ConfigService::class);

        $dbConfig = $secureConfigService->getAuthConfig('db_config.main');

        $mysql = new \mysqli(
            $dbConfig['database_host'],
            $dbConfig['database_user'],
            $dbConfig['database_password'],
            $dbConfig['database_database']
        );

        if ($mysql->connect_error)
        {
            throw new \RuntimeException('Mysqli Database connection failed');
        }

        $database = new Core\MysqliDatabase($mysql);

        $c->get(Core\DatabaseProvider::class)->set($database);

        return $database;
    },
    Core\DatabaseManager::class => DI\autowire()
        ->constructor(
            storedValues: DI\get('DbStoredValues'),
        ),
    Core\DebugService::class => DI\autowire()
        ->constructor(
            appDir: DI\get('app_dir'),
            serverName: DI\get('server_name'),
        ),
    Core\LatteRenderService::class => DI\autowire()
        ->constructor(
            templateDir: DI\string('{app_dir}/templates'),
            tmpDir: '/tmp/latte',
        ),
    Core\MailService::class => DI\autowire(Core\NullMailService::class),
    Core\PostmarkClientFactory::class => DI\factory(function (ContainerInterface $c) {
        $secureConfigService = $c->get(Security\ConfigService::class);

        $apiKey = $secureConfigService->getAuthConfig('postmark');

        return new Core\PostmarkClientFactory($apiKey);
    }),
    Core\Recaptcha::class => DI\Factory(function (ContainerInterface $c) {
        return new Core\Recaptcha(
            $c->get(Core\AssertService::class),
            $c->get(\GuzzleHttp\Client::class),
            $c->get('security.recaptcha.secret_key'),
        );
    }),
    Core\ReportFunction::class => DI\autowire(Core\NullReportFunction::class),
    Core\SanityCheckRunner::class => DI\autowire()
        ->constructor(
            storedValues: DI\get('SanityCheckStoredValues'),
            buildInfo: DI\get('build_info'),
        ),
    Core\UserMailer::class => DI\autowire()
        ->constructor(
            defaultSender: DI\get('sender_core.default_sender'),
        ),
    Core\ValidatorService::class => DI\autowire(),

    Security\AuthenticationService::class => DI\autowire(Security\NullAuthenticationService::class),
    Security\BlacklistService::class => DI\autowire(Security\NullBlacklistService::class),
    Security\ProtectService::class => DI\autowire()
        ->constructor(
            [
                'hash' => DI\get('security.hash'),
                'crypt_key' => DI\get('security.crypt_key'),
                'hmac_key' => DI\get('security.hmac_key'),
            ]
        ),
    Security\ConfigService::class => DI\autowire()
        ->constructor(
            DI\get('app_dir'),
            DI\get('security.auth_dir'),
        ),
];
