<?php

namespace WebFramework\Core;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LatteRenderService implements RenderService
{
    public function __construct(
        private AssertService $assert_service,
        private \Latte\Engine $latte_engine,
        private string $template_dir,
        private string $tmp_dir,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public function render(Request $request, Response $response, string $template_file, array $params): Response
    {
        $response->getBody()->write($this->render_to_string($request, $template_file, $params));

        return $response;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function render_to_string(Request $request, string $template_file, array $params): string
    {
        $this->latte_engine->setTempDirectory($this->tmp_dir);
        $this->latte_engine->setLoader(new \Latte\Loaders\FileLoader($this->template_dir));

        $this->assert_service->verify(file_exists("{$this->template_dir}/{$template_file}"), 'Requested template not present');

        return $this->latte_engine->renderToString($template_file, $params);
    }
}
