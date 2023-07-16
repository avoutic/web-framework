<?php

namespace Tests\Unit;

use WebFramework\Exception\ValidationException;
use WebFramework\Validation\CustomValidator;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\UsernameValidator;

require_once 'src/Defines.php';

/**
 * @internal
 *
 * @coversNothing
 */
final class InputValidationServiceTest extends \Codeception\Test\Unit
{
    public function testNoValidatorsNoResults()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
        ];

        $inputs = [
            'a' => 'b',
        ];

        $results = [
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($results);
    }

    public function testRequiredNotPresent()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username' => new UsernameValidator(),
        ];

        $inputs = [
        ];

        $results = [
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(\InvalidArgumentException::class, 'Required field not present in inputs: username');
    }

    public function testRequiredEmpty()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username' => new UsernameValidator(),
        ];

        $inputs = [
            'username' => '',
        ];

        $results = [
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(ValidationException::class);
    }

    public function testNotRequiredNotPresent()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username' => new UsernameValidator(required: false),
        ];

        $inputs = [
        ];

        $results = [
            'username' => '',
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($results);
    }

    public function testNotRequiredEmpty()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username' => new UsernameValidator(required: false),
        ];

        $inputs = [
            'username' => '',
        ];

        $results = [
            'username' => '',
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($results);
    }

    public function testValidLength()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username' => new UsernameValidator(maxLength: 5),
        ];

        $inputs = [
            'username' => 'ABCDE',
        ];

        $results = [
            'username' => 'ABCDE',
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($results);
    }

    public function testInvalidLength()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username' => new UsernameValidator(maxLength: 5),
        ];

        $inputs = [
            'username' => 'ABCDEF',
        ];

        $results = [
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(ValidationException::class);
    }

    public function testValidFilter()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username' => new CustomValidator('custom', filter: '[a-z]+'),
        ];

        $inputs = [
            'username' => 'abcdef',
        ];

        $results = [
            'username' => 'abcdef',
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($results);
    }

    public function testInvalidFilter()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username' => new CustomValidator('custom', filter: '[a-z]+'),
        ];

        $inputs = [
            'username' => 'abcdeF',
        ];

        $results = [
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(ValidationException::class);
    }
}
