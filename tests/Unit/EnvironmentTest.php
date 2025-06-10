<?php

namespace Tests\Unit;

use Codeception\Test\Unit;

require_once __DIR__.'/../../src/Environment.php';

/**
 * @internal
 *
 * @coversNothing
 */
final class EnvironmentTest extends Unit
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('TEST_ENV_VAR');
        putenv('TEST_BOOL_TRUE');
        putenv('TEST_BOOL_FALSE');
        putenv('TEST_NULL');
        putenv('TEST_EMPTY');
    }

    protected function tearDown(): void
    {
        putenv('TEST_ENV_VAR');
        putenv('TEST_BOOL_TRUE');
        putenv('TEST_BOOL_FALSE');
        putenv('TEST_NULL');
        putenv('TEST_EMPTY');

        parent::tearDown();
    }

    public function testEnvWithDefault()
    {
        $result = env('NONEXISTENT_VAR', 'default_value');
        verify($result)->equals('default_value');
    }

    public function testEnvWithoutDefault()
    {
        $result = env('NONEXISTENT_VAR');
        verify($result)->equals(null);
    }

    public function testEnvWithExistingVariable()
    {
        putenv('TEST_ENV_VAR=test_value');

        $result = env('TEST_ENV_VAR', 'default_value');
        verify($result)->equals('test_value');
    }

    public function testEnvBooleanConversion()
    {
        putenv('TEST_BOOL_TRUE=true');
        putenv('TEST_BOOL_FALSE=false');

        verify(env('TEST_BOOL_TRUE'))->equals(true);
        verify(env('TEST_BOOL_FALSE'))->equals(false);
    }

    public function testEnvNullConversion()
    {
        putenv('TEST_NULL=null');

        verify(env('TEST_NULL'))->equals(null);
    }

    public function testEnvEmptyConversion()
    {
        putenv('TEST_EMPTY=empty');

        verify(env('TEST_EMPTY'))->equals('');
    }

    public function testEnvRegularString()
    {
        putenv('TEST_ENV_VAR=regular_string');

        verify(env('TEST_ENV_VAR'))->equals('regular_string');
    }

    public function testEnvWithParentheses()
    {
        putenv('TEST_BOOL_TRUE=(true)');
        putenv('TEST_BOOL_FALSE=(false)');
        putenv('TEST_NULL=(null)');
        putenv('TEST_EMPTY=(empty)');

        verify(env('TEST_BOOL_TRUE'))->equals(true);
        verify(env('TEST_BOOL_FALSE'))->equals(false);
        verify(env('TEST_NULL'))->equals(null);
        verify(env('TEST_EMPTY'))->equals('');
    }

    public function testEnvAvailableInAuthContext()
    {
        putenv('TEST_AUTH_VAR=auth_value');

        $authConfig = function () {
            return [
                'test_setting' => env('TEST_AUTH_VAR', 'default'),
                'missing_setting' => env('MISSING_AUTH_VAR', 'fallback'),
            ];
        };

        $result = $authConfig();
        verify($result['test_setting'])->equals('auth_value');
        verify($result['missing_setting'])->equals('fallback');
    }
}
