<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Migration;

use Psr\Container\ContainerInterface as Container;
use WebFramework\Core\Database;
use WebFramework\Migration\Action\ActionHandler;
use WebFramework\Migration\Action\AddColumnActionHandler;
use WebFramework\Migration\Action\AddConstraintActionHandler;
use WebFramework\Migration\Action\CreateTableActionHandler;
use WebFramework\Migration\Action\CreateTriggerActionHandler;
use WebFramework\Migration\Action\InsertRowActionHandler;
use WebFramework\Migration\Action\ModifyColumnTypeActionHandler;
use WebFramework\Migration\Action\RawQueryActionHandler;
use WebFramework\Migration\Action\RenameColumnActionHandler;
use WebFramework\Migration\Action\RenameTableActionHandler;
use WebFramework\Migration\Action\RunTaskActionHandler;

/**
 * Coordinates migration actions by delegating the work to specialised handlers.
 */
class DatabaseManager
{
    /**
     * @var array<string, ActionHandler>
     */
    private array $handlers = [];

    /**
     * @var array<string, class-string<ActionHandler>>
     */
    private const HANDLER_CLASSES = [
        'create_table' => CreateTableActionHandler::class,
        'create_trigger' => CreateTriggerActionHandler::class,
        'add_column' => AddColumnActionHandler::class,
        'add_constraint' => AddConstraintActionHandler::class,
        'insert_row' => InsertRowActionHandler::class,
        'modify_column_type' => ModifyColumnTypeActionHandler::class,
        'rename_column' => RenameColumnActionHandler::class,
        'rename_table' => RenameTableActionHandler::class,
        'raw_query' => RawQueryActionHandler::class,
        'run_task' => RunTaskActionHandler::class,
    ];

    /**
     * @param resource $outputStream
     */
    public function __construct(
        private Database $database,
        private Container $container,
        private $outputStream = STDOUT
    ) {}

    /**
     * Execute the provided migration data.
     *
     * @param array<mixed, mixed> $data
     */
    public function execute(array $data, bool $dryRun = false): void
    {
        if (!isset($data['actions']) || !is_array($data['actions']))
        {
            throw new \InvalidArgumentException('No action array specified');
        }

        $this->write(' - Preparing all statements'.PHP_EOL);

        $steps = [];
        foreach ($data['actions'] as $action)
        {
            if (!isset($action['type']) || !is_string($action['type']))
            {
                throw new \InvalidArgumentException('No action type specified');
            }

            $handler = $this->getHandler($action['type']);
            $steps[] = $handler->buildStep($action);
        }

        if ($dryRun)
        {
            $this->handleDryRun($steps);

            return;
        }

        $this->write(' - Executing steps'.PHP_EOL);

        $this->database->startTransaction();

        foreach ($steps as $step)
        {
            if ($step instanceof QueryStep)
            {
                $this->write('   - Executing:'.PHP_EOL.$step->getQuery().PHP_EOL);

                try
                {
                    $step->execute($this->database);
                }
                catch (\RuntimeException $e)
                {
                    $this->write('   Failed: ');
                    $this->write($this->database->getLastError().PHP_EOL);

                    exit(1);
                }
            }
            else
            {
                $this->write('   - Executing:'.PHP_EOL.$step->describe().PHP_EOL);
                $step->execute($this->database);
            }
        }

        $this->database->commitTransaction();
    }

    /**
     * @param array<MigrationStep> $steps
     */
    private function handleDryRun(array $steps): void
    {
        $this->write(' - Dry run'.PHP_EOL);

        foreach ($steps as $step)
        {
            if ($step instanceof QueryStep)
            {
                $this->write('   - Would execute:'.PHP_EOL.$step->getQuery().PHP_EOL);
            }
            elseif ($step instanceof TaskStep)
            {
                $this->write('   - Would execute task:'.PHP_EOL.get_class($step->getTask()).PHP_EOL);
            }
            else
            {
                $this->write('   - Would execute:'.PHP_EOL.$step->describe().PHP_EOL);
            }
        }
    }

    private function write(string $message): void
    {
        fwrite($this->outputStream, $message);
    }

    private function getHandler(string $type): ActionHandler
    {
        if (!isset(self::HANDLER_CLASSES[$type]))
        {
            throw new \RuntimeException("Unknown action type '{$type}'");
        }

        if (!isset($this->handlers[$type]))
        {
            $class = self::HANDLER_CLASSES[$type];
            if ($class === RunTaskActionHandler::class)
            {
                $this->handlers[$type] = new RunTaskActionHandler($this->container);
            }
            else
            {
                $this->handlers[$type] = new $class();
            }
        }

        return $this->handlers[$type];
    }
}
