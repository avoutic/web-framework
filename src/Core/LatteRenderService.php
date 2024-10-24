<?php

namespace WebFramework\Core;

use Latte\Engine;
use Latte\Loaders\FileLoader;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Class LatteRenderService.
 *
 * Implements the RenderService interface using the Latte templating engine.
 */
class LatteRenderService implements RenderService
{
    /**
     * LatteRenderService constructor.
     *
     * @param Instrumentation $instrumentation The instrumentation service for performance tracking
     * @param Engine          $latteEngine     The Latte templating engine
     * @param string          $templateDir     The directory containing the template files
     * @param string          $tmpDir          The directory for storing compiled templates
     */
    public function __construct(
        private Instrumentation $instrumentation,
        private Engine $latteEngine,
        private string $templateDir,
        private string $tmpDir,
    ) {}

    /**
     * Render a template and write the output to the response body.
     *
     * @param Request              $request      The current request object
     * @param Response             $response     The response object to write to
     * @param string               $templateFile The name of the template file to render
     * @param array<string, mixed> $params       An associative array of parameters to pass to the template
     *
     * @return Response The modified response object with the rendered content
     */
    public function render(Request $request, Response $response, string $templateFile, array $params): Response
    {
        $response->getBody()->write($this->renderToString($request, $templateFile, $params));

        return $response;
    }

    /**
     * Render a template and return the output as a string.
     *
     * @param Request              $request      The current request object
     * @param string               $templateFile The name of the template file to render
     * @param array<string, mixed> $params       An associative array of parameters to pass to the template
     *
     * @return string The rendered content as a string
     *
     * @throws \InvalidArgumentException If the requested template file does not exist
     */
    public function renderToString(Request $request, string $templateFile, array $params): string
    {
        $this->latteEngine->setTempDirectory($this->tmpDir);
        $this->latteEngine->setLoader(new FileLoader($this->templateDir));

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
