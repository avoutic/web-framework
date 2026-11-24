<?php

namespace Tests\Unit\Migration\Action;

use Codeception\Test\Unit;
use WebFramework\Migration\Action\AddColumnActionHandler;
use WebFramework\Migration\QueryStep;

/**
 * @internal
 *
 * @covers \WebFramework\Migration\Action\AbstractActionHandler
 * @covers \WebFramework\Migration\Action\AddColumnActionHandler
 */
final class AddColumnActionHandlerTest extends Unit
{
    public function testGetType()
    {
        $handler = new AddColumnActionHandler();
        verify($handler->getType())->equals('add_column');
    }

    public function testBuildStepWithValidAction()
    {
        $handler = new AddColumnActionHandler();
        $action = [
            'table_name' => 'test_table',
            'field' => [
                'name' => 'new_column',
                'type' => 'varchar',
                'size' => 100,
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(QueryStep::class);
        verify($step->getQuery())->stringContainsString('ALTER TABLE `test_table`');
        verify($step->getQuery())->stringContainsString('ADD `new_column` VARCHAR(100)');
        verify($step->getParams())->equals([]);
    }

    public function testBuildStepWithDefaultValue()
    {
        $handler = new AddColumnActionHandler();
        $action = [
            'table_name' => 'test_table',
            'field' => [
                'name' => 'status',
                'type' => 'varchar',
                'size' => 50,
                'default' => 'active',
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step->getQuery())->stringContainsString("DEFAULT 'active'");
    }

    public function testBuildStepWithNullField()
    {
        $handler = new AddColumnActionHandler();
        $action = [
            'table_name' => 'test_table',
            'field' => [
                'name' => 'description',
                'type' => 'text',
                'null' => true,
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step->getQuery())->stringContainsString('NULL');
    }

    public function testBuildStepThrowsExceptionWhenTableNameMissing()
    {
        $handler = new AddColumnActionHandler();
        $action = [
            'field' => [
                'name' => 'new_column',
                'type' => 'varchar',
                'size' => 100,
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No table_name specified');
    }

    public function testBuildStepThrowsExceptionWhenFieldMissing()
    {
        $handler = new AddColumnActionHandler();
        $action = [
            'table_name' => 'test_table',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No field array specified');
    }

    public function testBuildStepThrowsExceptionWhenFieldNameMissing()
    {
        $handler = new AddColumnActionHandler();
        $action = [
            'table_name' => 'test_table',
            'field' => [
                'type' => 'varchar',
                'size' => 100,
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No field name specified');
    }

    public function testBuildStepThrowsExceptionWhenFieldTypeMissing()
    {
        $handler = new AddColumnActionHandler();
        $action = [
            'table_name' => 'test_table',
            'field' => [
                'name' => 'new_column',
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No field type specified');
    }

    /**
     * @param array<string, mixed> $field
     */
    private function buildQuery(array $field): string
    {
        $step = (new AddColumnActionHandler())->buildStep([
            'table_name' => 'test_table',
            'field' => $field,
        ]);

        return $step->getQuery();
    }

    public function testNullDefaultOnATextColumnIsDefaultNull()
    {
        $query = $this->buildQuery(['name' => 'note', 'type' => 'varchar', 'size' => 50, 'null' => true, 'default' => null]);

        verify($query)->stringContainsString('NULL DEFAULT NULL');
        verify($query)->stringNotContainsString("DEFAULT ''");
    }

    public function testNullDefaultOnANumericColumnIsDefaultNull()
    {
        $query = $this->buildQuery(['name' => 'request_id', 'type' => 'int', 'null' => true, 'default' => null]);

        verify($query)->stringContainsString('`request_id` INT NULL DEFAULT NULL');
    }

    public function testNullDefaultOnAColumnThatIsNotNullableIsRefused()
    {
        verify(function () {
            $this->buildQuery(['name' => 'request_id', 'type' => 'int', 'default' => null]);
        })->callableThrows(\InvalidArgumentException::class, "Field 'request_id' cannot have NULL as default, it is not nullable");
    }

    public function testBooleanDefaults()
    {
        verify($this->buildQuery(['name' => 'active', 'type' => 'tinyint', 'size' => 1, 'default' => false]))
            ->stringContainsString('`active` TINYINT(1) NOT NULL DEFAULT 0')
        ;
        verify($this->buildQuery(['name' => 'active', 'type' => 'tinyint', 'size' => 1, 'default' => true]))
            ->stringContainsString('`active` TINYINT(1) NOT NULL DEFAULT 1')
        ;
    }

    public function testStringDefaultIsEscaped()
    {
        $query = $this->buildQuery(['name' => 'label', 'type' => 'varchar', 'size' => 50, 'default' => "it's a \\ test"]);

        verify($query)->stringContainsString("DEFAULT 'it''s a \\\\ test'");
    }

    public function testEmptyStringAndZeroDefaultsAreKept()
    {
        verify($this->buildQuery(['name' => 'label', 'type' => 'varchar', 'size' => 50, 'default' => '']))
            ->stringContainsString("DEFAULT ''")
        ;
        verify($this->buildQuery(['name' => 'count', 'type' => 'int', 'default' => 0]))
            ->stringContainsString('DEFAULT 0')
        ;
    }
}
