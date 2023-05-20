<?php

namespace WebFramework\Core;

use Psr\Http\Message\ResponseInterface as Response;

class LatteRenderService
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
    public function render(Response $response, string $template_file, array $params): Response
    {
        $this->latte_engine->setTempDirectory($this->tmp_dir);
        $this->latte_engine->setLoader(new \Latte\Loaders\FileLoader($this->template_dir));

        $this->assert_service->verify(file_exists("{$this->template_dir}/{$template_file}"), 'Requested template not present');

        $output = $this->latte_engine->renderToString($template_file, $params);

        $response->getBody()->write($output);

        return $response;
    }
}
