<?php

namespace Tests\Unit;

use WebFramework\Exception\MultiValidationException;
use WebFramework\Validation\CustomNumberValidator;
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
            'username' => (new UsernameValidator())->required(),
        ];

        $inputs = [
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(\InvalidArgumentException::class, 'Required field not present in inputs: username');
    }

    public function testArrayRequiredNotPresent()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username' => (new UsernameValidator())->required(),
        ];

        $inputs = [
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(\InvalidArgumentException::class, 'Required field not present in inputs: username');
    }

    public function testArrayNotArrayInput()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username[]' => new UsernameValidator(),
        ];

        $inputs = [
            'username' => 'abc',
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(\InvalidArgumentException::class, 'Array field not array in inputs: username');
    }

    public function testStringArrayInput()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username' => new UsernameValidator(),
        ];

        $inputs = [
            'username' => [
                'abc',
            ],
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(\InvalidArgumentException::class, 'String field is array in inputs: username');
    }

    public function testRequiredEmpty()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username' => (new UsernameValidator())->required(),
        ];

        $inputs = [
            'username' => '',
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(MultiValidationException::class);
    }

    public function testNotRequiredNotPresent()
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
            'username' => '',
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($results);
    }

    public function testNotRequiredArrayNotPresent()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username[]' => new UsernameValidator(),
        ];

        $inputs = [
        ];

        $results = [
            'username' => [],
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
            'username' => new UsernameValidator(),
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
            'username' => (new UsernameValidator())->maxLength(5),
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

    public function testArrayValidLength()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username[]' => (new UsernameValidator())->maxLength(5),
        ];

        $inputs = [
            'username' => [
                'ABCDE',
                'FGHIJ',
            ],
        ];

        $results = [
            'username' => [
                'ABCDE',
                'FGHIJ',
            ],
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
            'username' => (new UsernameValidator())->maxLength(5),
        ];

        $inputs = [
            'username' => 'ABCDEF',
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(MultiValidationException::class);
    }

    public function testArrayInvalidLength()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username[]' => (new UsernameValidator())->maxLength(5),
        ];

        $inputs = [
            'username' => [
                'ABCDE',
                'FGHIJK',
            ],
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(MultiValidationException::class);
    }

    public function testValidFilter()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username' => (new CustomValidator('custom'))->filter('[a-z]+'),
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
            'username' => (new CustomValidator('custom'))->filter('[a-z]+'),
        ];

        $inputs = [
            'username' => 'abcdeF',
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(MultiValidationException::class);
    }

    public function testArrayValidFilter()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username[]' => (new CustomValidator('custom'))->filter('[a-z]+'),
        ];

        $inputs = [
            'username' => [
                'abcdef',
                'bcdefg',
            ],
        ];

        $results = [
            'username' => [
                'abcdef',
                'bcdefg',
            ],
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($results);
    }

    public function testArrayInvalidFilter()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'username[]' => (new CustomValidator('custom'))->filter('[a-z]+'),
        ];

        $inputs = [
            'username' => [
                'abcdef',
                'bcdefG',
            ],
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(MultiValidationException::class);
    }

    public function testValidNumber()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'number' => (new CustomNumberValidator('custom'))->filter('[1-5]'),
        ];

        $inputs = [
            'number' => '1',
        ];

        $results = [
            'number' => 1,
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($results);
    }

    public function testInvalidNumberDefaultNull()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'number' => (new CustomNumberValidator('custom'))->required(),
        ];

        $inputs = [
            'number' => '',
        ];

        $results = [
            'number' => null,
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(MultiValidationException::class);

        verify($instance->getAll())
            ->equals($results);
    }

    public function testInvalidNumberDefaultNumber()
    {
        $instance = $this->make(
            InputValidationService::class,
        );

        $validators = [
            'number' => (new CustomNumberValidator('custom'))->required()->default(0),
        ];

        $inputs = [
            'number' => '',
        ];

        $results = [
            'number' => 0,
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(MultiValidationException::class);

        verify($instance->getAll())
            ->equals($results);
    }
}
