<?php

namespace WebFramework;

use DI;
use Psr\Container\ContainerInterface;

return [
    \Cache\Adapter\Redis\RedisCachePool::class => function (ContainerInterface $c) {
        $secure_config_service = $c->get(Security\ConfigService::class);

        $cache_config = $secure_config_service->get_auth_config('redis');

        $redis_client = new \Redis();

        $result = $redis_client->pconnect(
            $cache_config['hostname'],
            $cache_config['port'],
            1,
            'wf',
            0,
            0,
            ['auth' => $cache_config['password']]
        );

        if ($result !== true)
        {
            throw new \RuntimeException('Redis Cache connection failed');
        }

        return new \Cache\Adapter\Redis\RedisCachePool($redis_client);
    },
    \Latte\Engine::class => DI\create(),
    \Slim\Psr7\Factory\ResponseFactory::class => DI\create(),

    Core\AssertService::class => DI\autowire(),
    Core\BaseFactory::class => DI\autowire(),
    Core\BrowserSessionService::class => DI\autowire()
        ->method(
            'start',
            host_name: DI\get('host_name'),
            http_mode: DI\get('http_mode'),
        ),
    Core\Cache::class => function (ContainerInterface $c) {
        if ($c->get('cache_enabled'))
        {
            return $c->get(Core\RedisCache::class);
        }

        return $c->get(Core\NullCache::class);
    },
    Core\ConfigService::class => DI\autowire()
        ->constructor(
            DI\get('config_tree'),
        ),
    Core\Database::class => function (ContainerInterface $c) {
        $framework = $c->get('framework');

        $framework->init_databases();
        $framework->check_compatibility();

        return $framework->get_main_db();
    },
    Core\DatabaseManager::class => DI\autowire()
        ->constructor(
            stored_values: DI\get('DbStoredValues'),
        ),
    Core\DebugService::class => DI\autowire()
        ->constructor(
            app_dir: DI\get('app_dir'),
            server_name: DI\get('server_name'),
        ),
    Core\LatteRenderService::class => DI\autowire()
        ->constructor(
            template_dir: DI\get('app_dir').'/templates',
            tmp_dir: '/tmp/latte',
        ),
    Core\MailService::class => DI\autowire(Core\NullMailService::class),
    Core\MessageService::class => DI\autowire(),
    Core\MiddlewareStack::class => DI\autowire()
        ->constructor(
            DI\get(Middleware\RoutingMiddleware::class),
        ),
    Core\MysqliDatabase::class => DI\autowire(),
    Core\NullCache::class => DI\autowire(),
    Core\ObjectFunctionCaller::class => DI\autowire(),
    Core\PostmarkClientFactory::class => DI\factory(function (ContainerInterface $c) {
        $secure_config_service = $c->get(Security\ConfigService::class);

        $api_key = $secure_config_service->get_auth_config('postmark');

        return new Core\PostmarkClientFactory($api_key);
    }),
    Core\RedisCache::class => DI\autowire(),
    Core\ReportFunction::class => DI\autowire(Core\MailReportFunction::class)
        ->constructor(
            assert_recipient: DI\get('sender_core.assert_recipient'),
        ),
    Core\ResponseEmitter::class => DI\autowire(),
    Core\RouteService::class => DI\autowire()
        ->constructor(
            base_url: DI\get('base_url'),
        ),
    Core\UserMailer::class => DI\autowire()
        ->constructor(
            default_sender: DI\get('sender_core.default_sender'),
        ),
    Core\ValidatorService::class => DI\autowire(),
    Core\WF::class => DI\autowire(),
    Core\WFWebHandler::class => DI\autowire(),
    'DbStoredValues' => DI\autowire(Core\StoredValues::class)
        ->constructor(
            module: 'db',
        ),

    Middleware\AuthenticationInfoMiddleware::class => DI\autowire(),
    Middleware\BlacklistMiddleware::class => DI\autowire(),
    Middleware\CsrfValidationMiddleware::class => DI\autowire(),
    Middleware\IpMiddleware::class => DI\autowire(),
    Middleware\JsonParserMiddleware::class => DI\autowire(),
    Middleware\MessageMiddleware::class => DI\autowire(),
    Middleware\RoutingMiddleware::class => DI\autowire()
        ->constructor(
            action_app_namespace: DI\get('actions.app_namespace'),
        ),
    Middleware\SecurityHeadersMiddleware::class => DI\autowire(),

    Security\AuthenticationService::class => DI\autowire(Security\DatabaseAuthenticationService::class)
        ->constructor(
            session_timeout: DI\get('authenticator.session_timeout'),
            user_class: DI\get('authenticator.user_class'),
        ),
    Security\BlacklistService::class => DI\autowire(Security\NullBlacklistService::class),
    Security\CsrfService::class => DI\autowire(),
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
