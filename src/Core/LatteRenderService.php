<?php

namespace WebFramework\Core;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LatteRenderService implements RenderService
{
    public function __construct(
        private Instrumentation $instrumentation,
        private \Latte\Engine $latteEngine,
        private string $templateDir,
        private string $tmpDir,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public function render(Request $request, Response $response, string $templateFile, array $params): Response
    {
        $response->getBody()->write($this->renderToString($request, $templateFile, $params));

        return $response;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function renderToString(Request $request, string $templateFile, array $params): string
    {
        $this->latteEngine->setTempDirectory($this->tmpDir);
        $this->latteEngine->setLoader(new \Latte\Loaders\FileLoader($this->templateDir));

        if (!file_exists("{$this->templateDir}/{$templateFile}"))
        {
            throw new \InvalidArgumentException('Requested template not present');
        }

        $span = $this->instrumentation->startSpan('app.render');
        $result = $this->latteEngine->renderToString($templateFile, $params);
        $this->instrumentation->finishSpan($span);

        return $result;
    }
}
