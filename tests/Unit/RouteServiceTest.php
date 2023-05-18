<?php

namespace Tests\Unit;

use Slim\Psr7\Factory\RequestFactory;
use WebFramework\Core\RouteService;

/**
 * @internal
 *
 * @coversNothing
 */
final class RouteServiceTest extends \Codeception\Test\Unit
{
    public function testHasRoutesNone()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        verify($instance->has_routes())
            ->equals(false);
    }

    public function testHasRoutesRedirect()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        $instance->register_redirect('GET', '/level', '/pipe');

        verify($instance->has_routes())
            ->equals(true);
    }

    public function testHasRoutesAction()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        $instance->register_action('GET', '/level', 'class', 'function');

        verify($instance->has_routes())
            ->equals(true);
    }

    public function testGetRedirectNone()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('GET', '/app/level');

        verify($instance->get_redirect($request))
            ->equals(false);
    }

    public function testGetRedirectMatch()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        $instance->register_redirect('GET', '/level', '/pipe');

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('GET', '/app/level');

        verify($instance->get_redirect($request))
            ->equals(['url' => '/pipe', 'redirect_type' => 301]);
    }

    public function testGetRedirectMatchMethods()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        $instance->register_redirect(['GET', 'POST'], '/level', '/pipe');

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('POST', '/app/level');

        verify($instance->get_redirect($request))
            ->equals(['url' => '/pipe', 'redirect_type' => 301]);
    }

    public function testGetRedirectMatchQueryParameters()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        $instance->register_redirect('GET', '/level', '/pipe');

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('GET', '/app/level?key1=val');

        verify($instance->get_redirect($request))
            ->equals(['url' => '/pipe', 'redirect_type' => 301]);
    }

    public function testGetRedirectMethodMismatch()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        $instance->register_redirect(['PUT', 'POST'], '/level', '/pipe');

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('GET', '/app/level');

        verify($instance->get_redirect($request))
            ->equals(false);
    }

    public function testGetRedirectMatchArgs()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        $instance->register_redirect('GET', '/level/(\d+)', '/pipe/{level_id}/go', '301', ['level_id' => 1]);

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('GET', '/app/level/12');

        verify($instance->get_redirect($request))
            ->equals(['url' => '/pipe/12/go', 'redirect_type' => 301]);
    }

    public function testGetActionNone()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('GET', '/app/level');

        verify($instance->get_action($request))
            ->equals(false);
    }

    public function testGetActionMatch()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        $instance->register_action('GET', '/level', 'Level', 'html_main');

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('GET', '/app/level');

        verify($instance->get_action($request))
            ->equals(['class' => 'Level', 'function' => 'html_main', 'args' => []]);
    }

    public function testGetActionMatchMethods()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        $instance->register_action(['GET', 'POST'], '/level', 'Level', 'html_main');

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('GET', '/app/level');

        verify($instance->get_action($request))
            ->equals(['class' => 'Level', 'function' => 'html_main', 'args' => []]);
    }

    public function testGetActionMatchQueryParameters()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        $instance->register_action('GET', '/level', 'Level', 'html_main');

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('GET', '/app/level?key1=val');

        verify($instance->get_action($request))
            ->equals(['class' => 'Level', 'function' => 'html_main', 'args' => []]);
    }

    public function testGetActionMethodMismatch()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        $instance->register_action(['PUT', 'POST'], '/level', 'Level', 'html_main');

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('GET', '/app/level');

        verify($instance->get_action($request))
            ->equals(false);
    }

    public function testGetActionMatchArgs()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        $instance->register_action('GET', '/level/(\d+)', 'Level', 'html_main', ['level_id']);

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('GET', '/app/level/12');

        verify($instance->get_action($request))
            ->equals(['class' => 'Level', 'function' => 'html_main', 'args' => ['level_id' => '12']]);
    }

    public function testGetActionMismatchPartial()
    {
        $instance = $this->construct(
            RouteService::class,
            [
                '/app',
            ],
        );

        $instance->register_action('GET', '/level/(\d+)/go', 'Level', 'html_main', ['level_id']);

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('GET', '/app/level/12');

        verify($instance->get_action($request))
            ->equals(false);
    }
}
