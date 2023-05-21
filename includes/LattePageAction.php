<?php

namespace WebFramework\Core;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class LattePageAction
{
    public function __construct(
        protected WF $container,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public function render(Request $request, Response $response, string $template_file, array $params): Response
    {
        $latte_renderer = $this->container->get_latte_render_service();

        $data = [];
        $data['base_url'] = $this->container->get_config('base_url');
        $data['csrf_token'] = $this->container->get_csrf_service()->get_token();
        $data['build_info'] = $this->container->get_debug_service()->get_build_info();
        $data['config'] = $this->container->get_config('');
        $data['messages'] = $this->container->get_web_handler()->get_messages();

        $data['content_title'] = '';
        $data['canonical'] = '';
        $data['description'] = '';
        $data['keywords'] = '';
        $data['meta_robots'] = '';
        $data['title'] = '';

        $params['core'] = $data;

        return $latte_renderer->render($response, $template_file, $params);
    }
}
