<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Task;

use Slim\Psr7\Factory\ServerRequestFactory;
use WebFramework\Core\BootstrapService;
use WebFramework\Diagnostics\Instrumentation;
use WebFramework\Http\SlimAppFactory;

/**
 * Class SlimAppTask.
 *
 * This task is responsible for setting up and running the Slim application.
 */
class SlimAppTask implements Task
{
    /**
     * SlimAppTask constructor.
     *
     * @param BootstrapService $bootstrapService The bootstrap service
     * @param Instrumentation  $instrumentation  The instrumentation service
     * @param SlimAppFactory   $slimAppFactory   The factory for the Slim application
     */
    public function __construct(
        private BootstrapService $bootstrapService,
        private Instrumentation $instrumentation,
        private SlimAppFactory $slimAppFactory,
    ) {}

    /**
     * Execute the Slim application task.
     *
     * This method bootstraps the framework, creates the Slim application and handles
     * the request.
     */
    public function execute(): void
    {
        // Start instrumentation transaction
        //
        $request = ServerRequestFactory::createFromGlobals();
        $transaction = $this->instrumentation->startTransaction('http.server', $request->getUri()->getPath());

        // Bootstrap WebFramework
        //
        $span = $this->instrumentation->startSpan('app.bootstrap');
        $this->bootstrapService->bootstrap();
        $this->instrumentation->finishSpan($span);

        // Create the Slim application with its middlewares and routes
        //
        $app = $this->slimAppFactory->create();

        // Handle the request
        //
        $span = $this->instrumentation->startSpan('app.run');
        $app->run();
        $this->instrumentation->finishSpan($span);

        $this->instrumentation->finishTransaction($transaction);
    }

    /**
     * Check if the task handles its own bootstrapping.
     *
     * @return bool True if the task handles its own bootstrapping, false otherwise
     */
    public function handlesOwnBootstrapping(): bool
    {
        return true;
    }
}
