<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Exception\MultiValidationException;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\Validator\CustomNumberValidator;
use WebFramework\Validation\Validator\CustomValidator;
use WebFramework\Validation\Validator\UsernameValidator;

require_once 'src/Defines.php';

/**
 * @internal
 *
 * @covers \WebFramework\Validation\InputValidationService
 */
final class InputValidationServiceTest extends Unit
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

        $validated = [
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($validated)
        ;
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

        $validated = [
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(\InvalidArgumentException::class, 'Required field not present in inputs: username')
        ;

        verify($instance->getValidated())
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($inputs)
        ;
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

        $validated = [
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(\InvalidArgumentException::class, 'Required field not present in inputs: username')
        ;

        verify($instance->getValidated())
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($inputs)
        ;
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

        $all = [
        ];

        $validated = [
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(\InvalidArgumentException::class, 'Array field not array in inputs: username')
        ;

        verify($instance->getValidated())
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($all)
        ;
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

        $all = [
        ];

        $validated = [
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(\InvalidArgumentException::class, 'String field is array in inputs: username')
        ;

        verify($instance->getValidated())
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($all)
        ;
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

        $validated = [
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(MultiValidationException::class)
        ;

        verify($instance->getValidated())
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($inputs)
        ;
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

        $validated = [
            'username' => '',
        ];

        $all = [
            'username' => '',
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($all)
        ;
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

        $validated = [
            'username' => [],
        ];

        $all = [
            'username' => [],
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($all)
        ;
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

        $validated = [
            'username' => '',
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($inputs)
        ;
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

        $validated = [
            'username' => 'ABCDE',
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($inputs)
        ;
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

        $validated = [
            'username' => [
                'ABCDE',
                'FGHIJ',
            ],
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($inputs)
        ;
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

        $validated = [
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(MultiValidationException::class)
        ;

        verify($instance->getValidated())
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($inputs)
        ;
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

        $validated = [
            'username' => [
                'ABCDE',
            ],
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(MultiValidationException::class)
        ;

        verify($instance->getValidated())
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($inputs)
        ;
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

        $validated = [
            'username' => 'abcdef',
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($inputs)
        ;
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

        $validated = [
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(MultiValidationException::class)
        ;

        verify($instance->getValidated())
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($inputs)
        ;
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

        $validated = [
            'username' => [
                'abcdef',
                'bcdefg',
            ],
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($inputs)
        ;
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

        $validated = [
            'username' => [
                'abcdef',
            ],
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(MultiValidationException::class)
        ;

        verify($instance->getValidated())
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($inputs)
        ;
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

        $validated = [
            'number' => 1,
        ];

        verify($instance->validate($validators, $inputs))
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($inputs)
        ;
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

        $validated = [
        ];

        $all = [
            'number' => null,
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(MultiValidationException::class)
        ;

        verify($instance->getValidated())
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($all)
        ;
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

        $validated = [
        ];

        $all = [
            'number' => 0,
        ];

        verify(function () use ($instance, $validators, $inputs) {
            $instance->validate($validators, $inputs);
        })
            ->callableThrows(MultiValidationException::class)
        ;

        verify($instance->getValidated())
            ->equals($validated)
        ;

        verify($instance->getAll())
            ->equals($all)
        ;
    }
}
