<?php

namespace WebFramework;

use Composer\Autoload\ClassLoader;
use DI;
use Latte\Engine;
use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Slim\Http\Factory\DecoratedResponseFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;

return [
    Engine::class => DI\create(),
    LoggerInterface::class => DI\factory(static fn (Logging\ChannelManager $manager) => $manager->getDefaultLogger()),
    ResponseFactoryInterface::class => DI\autowire(DecoratedResponseFactory::class)
        ->constructorParameter('responseFactory', DI\get(ResponseFactory::class))
        ->constructorParameter('streamFactory', DI\get(StreamFactory::class)),
    SessionManagerInterface::class => DI\get(SessionInterface::class),
    SessionInterface::class => function (ContainerInterface $container) {
        $options = [
            'name' => $container->get('app_name'),
            'lifetime' => $container->get('authenticator.session_timeout'),
            'path' => '/',
            'domain' => $container->get('host_name'),
            'secure' => $container->get('http_mode') === 'https',
            'httponly' => true,
        ];

        return new PhpSession($options);
    },

    'channels.default' => NullLogger::class,
    'channels.exception' => NullLogger::class,

    'app_dir' => function (ContainerInterface $c) {
        // Determine app dir
        //
        $reflection = new \ReflectionClass(ClassLoader::class);

        return dirname($reflection->getFileName() ?: '', 3);
    },
    'app_name' => 'app',
    'build_info' => function (ContainerInterface $c) {
        $buildInfoService = $c->get(Core\BuildInfoService::class);

        return $buildInfoService->getInfo();
    },
    'full_base_url' => DI\string('{http_mode}://{server_name}{base_url}'),
    'host_name' => $_SERVER['SERVER_NAME'] ?? 'app',
    'offline_mode' => false,
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'app',

    Cache\Cache::class => DI\autowire(Cache\NullCache::class),

    Config\ConfigService::class => DI\autowire()
        ->constructorParameter('config', DI\get('config_tree')),

    Console\ConsoleApplication::class => DI\autowire()
        ->constructorParameter('debug', DI\get('debug')),

    Core\RuntimeEnvironment::class => DI\autowire()
        ->constructorParameter('appDir', DI\get('app_dir'))
        ->constructorParameter('baseUrl', DI\get('base_url'))
        ->constructorParameter('debug', DI\get('debug'))
        ->constructorParameter('httpMode', DI\get('http_mode'))
        ->constructorParameter('offlineMode', DI\get('offline_mode'))
        ->constructorParameter('production', DI\get('production'))
        ->constructorParameter('serverName', DI\get('server_name')),

    Database\Database::class => DI\autowire(Database\NullDatabase::class),

    Diagnostics\Instrumentation::class => DI\autowire(Diagnostics\NullInstrumentation::class),
    Diagnostics\MailReportFunction::class => DI\autowire()
        ->constructorParameter('assertRecipient', DI\get('sender_core.assert_recipient')),
    Diagnostics\ReportFunction::class => DI\autowire(Diagnostics\NullReportFunction::class),

    Http\HttpApplication::class => DI\autowire()
        ->constructorParameter('debug', DI\get('debug')),
    Http\ResponseEmitter::class => DI\autowire()
        ->constructorParameter('responseFactory', DI\get(ResponseFactoryInterface::class)),

    // Needed because the mapped config tree does not contain intermediate config nodes
    'logging.channels' => function (Config\ConfigService $configService) {
        return $configService->get('logging.channels');
    },
    Logging\ChannelManager::class => DI\autowire()
        ->constructorParameter('channelConfig', DI\get('logging.channels')),

    Mail\MailService::class => DI\autowire(Mail\NullMailService::class),
    'templateOverrides' => function (ContainerInterface $c) {
        $configService = $c->get(Config\ConfigService::class);

        return $configService->get('user_mailer.template_overrides');
    },
    Mail\UserMailer::class => DI\autowire()
        ->constructorParameter('senderEmail', DI\get('sender_core.default_sender'))
        ->constructorParameter('templateOverrides', DI\get('templateOverrides')),

    Middleware\ErrorRedirectMiddleware::class => DI\autowire()
        ->constructorParameter('debug', DI\get('debug')),

    Presentation\LatteRenderService::class => DI\autowire()
        ->constructorParameter('templateDir', DI\string('{app_dir}/templates'))
        ->constructorParameter('tmpDir', '/tmp/latte'),
    Presentation\RenderService::class => DI\get(Presentation\LatteRenderService::class),

    Queue\Queue::class => DI\autowire(Queue\MemoryQueue::class)
        ->constructorParameter('name', 'default'),

    SanityCheck\SanityCheckRunner::class => DI\autowire()
        ->constructorParameter('buildInfo', DI\get('build_info')),

    Security\AuthenticationService::class => DI\autowire(Security\NullAuthenticationService::class),
    Security\BlacklistService::class => DI\autowire(Security\NullBlacklistService::class),
    Security\ConfigService::class => DI\autowire()
        ->constructorParameter('authDir', DI\get('security.auth_dir')),
    Security\DatabaseAuthenticationService::class => DI\autowire(Security\DatabaseAuthenticationService::class)
        ->constructorParameter('sessionTimeout', DI\get('authenticator.session_timeout')),
    Security\DatabaseBlacklistService::class => DI\autowire()
        ->constructorParameter('storePeriod', DI\get('security.blacklist.store_period'))
        ->constructorParameter('threshold', DI\get('security.blacklist.threshold'))
        ->constructorParameter('triggerPeriod', DI\get('security.blacklist.trigger_period')),
    Security\ProtectService::class => DI\autowire()
        ->constructorParameter('moduleConfig', [
            'hash' => DI\get('security.hash'),
            'crypt_key' => DI\get('security.crypt_key'),
            'hmac_key' => DI\get('security.hmac_key'),
        ]),
    Security\RandomProvider::class => DI\get(Security\OpensslRandomProvider::class),
    Security\Recaptcha::class => DI\autowire()
        ->constructorParameter('secretKey', DI\get('security.recaptcha.secret_key')),
    Security\RecaptchaFactory::class => DI\autowire()
        ->constructorParameter('secretKey', DI\get('security.recaptcha.secret_key')),

    'translationDirectories' => function (ContainerInterface $c) {
        $configService = $c->get(Config\ConfigService::class);

        return $configService->get('translations.directories');
    },
    Translation\TranslationLoader::class => DI\get(Translation\FileTranslationLoader::class),
    Translation\FileTranslationLoader::class => DI\autowire()
        ->constructorParameter('directories', DI\get('translationDirectories')),
    Translation\TranslationService::class => DI\autowire()
        ->constructorParameter('language', DI\get('translations.default_language')),
];
