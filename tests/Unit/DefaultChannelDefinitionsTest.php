<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use DI\ContainerBuilder;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * @internal
 *
 * @coversNothing
 */
final class DefaultChannelDefinitionsTest extends Unit
{
    public function testDefaultChannelLogsErrorsToThePhpErrorLogAndExceptionChannelFollowsIt()
    {
        $definitions = require __DIR__.'/../../definitions/definitions.php';

        $builder = new ContainerBuilder();
        $builder->addDefinitions([
            'channels.default' => $definitions['channels.default'],
            'channels.exception' => $definitions['channels.exception'],
        ]);
        $container = $builder->build();

        $default = $container->get('channels.default');

        verify($default)->instanceOf(Logger::class);
        verify(count($default->getHandlers()))->equals(1);

        $handler = $default->getHandlers()[0];

        verify($handler)->instanceOf(ErrorLogHandler::class);
        verify($handler->getLevel())->same(Level::Error);

        verify($container->get('channels.exception'))->same($default);
    }
}
