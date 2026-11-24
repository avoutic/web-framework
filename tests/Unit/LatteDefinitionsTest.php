<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use WebFramework\Diagnostics\Instrumentation;
use WebFramework\Diagnostics\NullInstrumentation;
use WebFramework\Presentation\LatteRenderService;

/**
 * @internal
 *
 * @covers \WebFramework\Presentation\LatteRenderService
 */
final class LatteDefinitionsTest extends Unit
{
    private string $dir;

    protected function _before(): void
    {
        $this->dir = codecept_output_dir().'latte_test';
        mkdir($this->dir.'/templates', 0o777, true);
        file_put_contents($this->dir.'/templates/hello.latte', 'Hello {$name}');
    }

    protected function _after(): void
    {
        foreach ((array) glob($this->dir.'/compiled/*') as $file)
        {
            unlink((string) $file);
        }

        @rmdir($this->dir.'/compiled');
        unlink($this->dir.'/templates/hello.latte');
        rmdir($this->dir.'/templates');
        rmdir($this->dir);
    }

    public function testDefaultTmpDirIsUnchanged()
    {
        $config = require __DIR__.'/../../config/base_config.php';

        verify($config['latte']['tmp_dir'])->equals('/tmp/latte');
    }

    public function testCompiledTemplatesGoToTheConfiguredDirectory()
    {
        $definitions = require __DIR__.'/../../definitions/definitions.php';

        $builder = new ContainerBuilder();
        $builder->addDefinitions([
            'app_dir' => $this->dir,
            'latte.tmp_dir' => $this->dir.'/compiled',
            Instrumentation::class => new NullInstrumentation(),
            LoggerInterface::class => $this->makeEmpty(LoggerInterface::class),
            LatteRenderService::class => $definitions[LatteRenderService::class],
        ]);

        $renderer = $builder->build()->get(LatteRenderService::class);

        $output = $renderer->renderToString((new ServerRequestFactory())->createServerRequest('GET', '/'), 'hello.latte', ['name' => 'World']);

        verify($output)->equals('Hello World');
        verify(count((array) glob($this->dir.'/compiled/*')))->greaterThan(0);
    }
}
