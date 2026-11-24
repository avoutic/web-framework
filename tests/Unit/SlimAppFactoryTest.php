<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use DI\Container;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Routing\RouteContext;
use WebFramework\Config\ConfigService;
use WebFramework\Http\RouteSet;
use WebFramework\Http\SlimAppFactory;

/**
 * @internal
 *
 * @covers \WebFramework\Http\SlimAppFactory
 */
final class SlimAppFactoryTest extends Unit
{
    /** @var array<string> */
    public array $log = [];

    private function buildMiddleware(string $name): MiddlewareInterface
    {
        return new class($this, $name) implements MiddlewareInterface {
            public function __construct(private SlimAppFactoryTest $test, private string $name) {}

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                // Routing has only run for middlewares that sit inside the routing middleware
                $routed = $request->getAttribute(RouteContext::ROUTE) !== null;
                $this->test->log[] = $this->name.($routed ? ' (routed)' : ' (not routed)');

                return $handler->handle($request);
            }
        };
    }

    private function buildContainer(): Container
    {
        $this->log = [];
        $test = $this;

        $container = new Container();
        $container->set(ResponseFactoryInterface::class, new ResponseFactory());
        $container->set('pre', $this->buildMiddleware('pre'));
        $container->set('post', $this->buildMiddleware('post'));
        $container->set('routes', new class($test) implements RouteSet {
            public function __construct(private SlimAppFactoryTest $test) {}

            public function register(App $app): void
            {
                $test = $this->test;

                $app->get('/hello', function (ServerRequestInterface $request, ResponseInterface $response) use ($test) {
                    $test->log[] = 'action';
                    $response->getBody()->write('hello');

                    return $response;
                });
            }
        });

        return $container;
    }

    private function buildFactory(Container $container): SlimAppFactory
    {
        $config = [
            'middlewares.post_routing' => ['post'],
            'middlewares.pre_routing' => ['pre'],
            'routes' => ['routes'],
        ];

        return new SlimAppFactory(
            $this->makeEmpty(ConfigService::class, [
                'get' => fn (string $key) => $config[$key],
            ]),
            $container,
        );
    }

    public function testCreatesWiredAppWithoutRunningIt()
    {
        $container = $this->buildContainer();

        $app = $this->buildFactory($container)->create();

        verify($app)->instanceOf(App::class);
        verify($app->getContainer())->same($container);
        verify($this->log)->same([]);

        $response = $app->handle((new ServerRequestFactory())->createServerRequest('GET', '/hello'));

        verify($response->getStatusCode())->equals(200);
        verify((string) $response->getBody())->equals('hello');
        verify($this->log)->same(['pre (not routed)', 'post (routed)', 'action']);
    }

    public function testEveryCallCreatesANewApp()
    {
        $factory = $this->buildFactory($this->buildContainer());

        verify($factory->create())->notSame($factory->create());
    }
}
