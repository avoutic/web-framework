<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Core\ValidatorService;

/**
 * @internal
 *
 * @coversNothing
 */
final class ValidatorServiceTest extends Unit
{
    public function testGetFilterResultsEmpty()
    {
        $instance = $this->makeEmptyExcept(
            ValidatorService::class,
            'getFilterResults',
        );

        $params = [
            'query' => [],
            'post' => [],
            'json' => [],
        ];

        $filters = [
        ];

        $results = [
            'raw' => [],
            'filtered' => [],
        ];

        verify($instance->getFilterResults($params, $filters))
            ->equals($results)
        ;
    }

    public function testGetFilterResultsOnlyFiltered()
    {
        $instance = $this->makeEmptyExcept(
            ValidatorService::class,
            'getFilterResults',
        );

        $params = [
            'query' => ['test' => 'query'],
            'post' => ['test' => 'post'],
            'json' => ['test' => 'json'],
        ];

        $filters = [
        ];

        $results = [
            'raw' => [],
            'filtered' => [],
        ];

        verify($instance->getFilterResults($params, $filters))
            ->equals($results)
        ;
    }

    public function testGetFilterResultsJsonFirst()
    {
        $instance = $this->makeEmptyExcept(
            ValidatorService::class,
            'getFilterResults',
        );

        $params = [
            'query' => ['test' => 'query'],
            'post' => ['test' => 'post'],
            'json' => ['test' => 'json'],
        ];

        $filters = [
            'test' => '.*',
        ];

        $results = [
            'raw' => ['test' => 'json'],
            'filtered' => ['test' => 'json'],
        ];

        verify($instance->getFilterResults($params, $filters))
            ->equals($results)
        ;
    }

    public function testGetFilterResultsArrayJsonFirst()
    {
        $instance = $this->makeEmptyExcept(
            ValidatorService::class,
            'getFilterResults',
        );

        $params = [
            'query' => ['test' => ['key1' => 'query1', 'key2' => '2']],
            'post' => ['test' => ['key1' => 'post1', 'key2' => '3']],
            'json' => ['test' => ['key1' => 'json1', 'key2' => '4']],
        ];

        $filters = [
            'test[]' => '.*',
        ];

        $results = [
            'raw' => ['test' => ['key1' => 'json1', 'key2' => '4']],
            'filtered' => ['test' => ['key1' => 'json1', 'key2' => '4']],
        ];

        verify($instance->getFilterResults($params, $filters))
            ->equals($results)
        ;
    }

    public function testGetFilterResultsPostSecond()
    {
        $instance = $this->makeEmptyExcept(
            ValidatorService::class,
            'getFilterResults',
        );

        $params = [
            'query' => ['test' => 'query'],
            'post' => ['test' => 'post'],
            'json' => [],
        ];

        $filters = [
            'test' => '.*',
        ];

        $results = [
            'raw' => ['test' => 'post'],
            'filtered' => ['test' => 'post'],
        ];

        verify($instance->getFilterResults($params, $filters))
            ->equals($results)
        ;
    }

    public function testGetFilterResultsArrayPostSecond()
    {
        $instance = $this->makeEmptyExcept(
            ValidatorService::class,
            'getFilterResults',
        );

        $params = [
            'query' => ['test' => ['key1' => 'query1', 'key2' => '2']],
            'post' => ['test' => ['key1' => 'post1', 'key2' => '3']],
            'json' => [],
        ];

        $filters = [
            'test[]' => '.*',
        ];

        $results = [
            'raw' => ['test' => ['key1' => 'post1', 'key2' => '3']],
            'filtered' => ['test' => ['key1' => 'post1', 'key2' => '3']],
        ];

        verify($instance->getFilterResults($params, $filters))
            ->equals($results)
        ;
    }

    public function testGetFilterResultsQueryThird()
    {
        $instance = $this->makeEmptyExcept(
            ValidatorService::class,
            'getFilterResults',
        );

        $params = [
            'query' => ['test' => 'query'],
            'post' => [],
            'json' => [],
        ];

        $filters = [
            'test' => '.*',
        ];

        $results = [
            'raw' => ['test' => 'query'],
            'filtered' => ['test' => 'query'],
        ];

        verify($instance->getFilterResults($params, $filters))
            ->equals($results)
        ;
    }

    public function testGetFilterResultsArrayQueryThird()
    {
        $instance = $this->makeEmptyExcept(
            ValidatorService::class,
            'getFilterResults',
        );

        $params = [
            'query' => ['test' => ['key1' => 'query1', 'key2' => '2']],
            'post' => [],
            'json' => [],
        ];

        $filters = [
            'test[]' => '.*',
        ];

        $results = [
            'raw' => ['test' => ['key1' => 'query1', 'key2' => '2']],
            'filtered' => ['test' => ['key1' => 'query1', 'key2' => '2']],
        ];

        verify($instance->getFilterResults($params, $filters))
            ->equals($results)
        ;
    }

    public function testGetFilterResultsFiltered()
    {
        $instance = $this->makeEmptyExcept(
            ValidatorService::class,
            'getFilterResults',
        );

        $params = [
            'query' => ['test' => 'query'],
            'post' => [],
            'json' => [],
        ];

        $filters = [
            'test' => '\d+',
        ];

        $results = [
            'raw' => ['test' => 'query'],
            'filtered' => ['test' => ''],
        ];

        verify($instance->getFilterResults($params, $filters))
            ->equals($results)
        ;
    }

    public function testGetFilterResultsArrayFiltered()
    {
        $instance = $this->makeEmptyExcept(
            ValidatorService::class,
            'getFilterResults',
        );

        $params = [
            'query' => ['test' => ['key1' => 'query1', 'key2' => '2']],
            'post' => [],
            'json' => [],
        ];

        $filters = [
            'test[]' => '\d+',
        ];

        $results = [
            'raw' => ['test' => ['key1' => 'query1', 'key2' => '2']],
            'filtered' => ['test' => ['key2' => '2']],
        ];

        verify($instance->getFilterResults($params, $filters))
            ->equals($results)
        ;
    }

    public function testGetFilterResultsEmptyRegex()
    {
        $instance = $this->makeEmptyExcept(
            ValidatorService::class,
            'getFilterResults',
        );

        $params = [
            'query' => [],
            'post' => [],
            'json' => [],
        ];

        $filters = [
            'test' => '',
        ];

        verify(function () use ($instance, $params, $filters) {
            $instance->getFilterResults($params, $filters);
        })
            ->callableThrows(\InvalidArgumentException::class, 'Zero-length regex provided')
        ;
    }
}
