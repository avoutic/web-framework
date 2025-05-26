<?php

use Slim\Psr7\Factory\ServerRequestFactory;
use WebFramework\Core\DebugService;
use WebFramework\Core\ReportFunction;
use WebFramework\Exception\SanityCheckException;
use WebFramework\Task\SlimAppTask;
use WebFramework\Task\TaskRunner;

if (!file_exists(__DIR__.'/../vendor/autoload.php'))
{
    exit('Composer not initialized');
}

require_once __DIR__.'/../vendor/autoload.php';

$taskRunner = new TaskRunner(__DIR__.'/..');
$taskRunner->build();

try
{
    $taskRunner->execute(SlimAppTask::class);
}
catch (SanityCheckException $e)
{
    header('Content-type: text/plain');
    echo 'Sanity check failed';
}
catch (Throwable $e)
{
    $request = ServerRequestFactory::createFromGlobals();

    $debugService = $taskRunner->get(DebugService::class);
    $errorReport = $debugService->getThrowableReport($e, $request);

    $reportFunction = $taskRunner->get(ReportFunction::class);
    $reportFunction->report($e->getMessage(), 'unhandled_exception', $errorReport);

    $message = ($taskRunner->get('debug')) ? $errorReport['message'] : $errorReport['low_info_message'];

    header('Content-type: text/plain');
    echo $message;
}
