<?php

namespace WebFramework\Core;

class InputHelpers
{
    /** @var array<string, mixed> */
    public static array $defaultParameters = [
        'extra_label' => '',
        'id' => '',
        'label' => '',
        'model' => '',
        'name' => '',
        'on_change' => '',
        'required' => false,
        'show' => '',
        'value' => '',
        'width' => '',
    ];

    /**
     * @param array<mixed> $args
     */
    public static function loadTemplate(string $name, array $args = []): void
    {
        $container = ContainerWrapper::get();

        $appDir = $container->get('app_dir');

        if (!file_exists("{$appDir}/templates/{$name}.inc.php"))
        {
            throw new \InvalidArgumentException('Requested template not present');
        }

        include "{$appDir}/templates/{$name}.inc.php";
    }

    /**
     * @param array<mixed> $templateParameters
     * @param array<mixed> $parameters
     */
    public static function printInputField(string $template, array $templateParameters, array $parameters): void
    {
        $typeParameters = [
            'max_length' => '',
            'on_keyup' => '',
            'placeholder' => '',
            'prefix' => '',
            'readonly' => false,
            'type' => 'text',
        ];

        $parameters = array_merge(static::$defaultParameters, $typeParameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::loadTemplate(
            $template,
            [
                'template_parameters' => $templateParameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $templateParameters
     * @param array<mixed> $parameters
     */
    public static function printInputArea(string $template, array $templateParameters, array $parameters): void
    {
        $typeParameters = [
            'enable_editor' => false,
            'on_keyup' => '',
            'rows' => '',
        ];

        $parameters = array_merge(static::$defaultParameters, $typeParameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::loadTemplate(
            $template,
            [
                'template_parameters' => $templateParameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $templateParameters
     * @param array<mixed> $parameters
     */
    public static function printInputCheckbox(string $template, array $templateParameters, array $parameters): void
    {
        $typeParameters = [
            'checked' => false,
            'value' => '1',
        ];

        $parameters = array_merge(static::$defaultParameters, $typeParameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::loadTemplate(
            $template,
            [
                'template_parameters' => $templateParameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $templateParameters
     * @param array<mixed> $parameters
     */
    public static function printInputToggle(string $template, array $templateParameters, array $parameters): void
    {
        $typeParameters = [
            'checked' => false,
        ];

        $parameters = array_merge(static::$defaultParameters, $typeParameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::loadTemplate(
            $template,
            [
                'template_parameters' => $templateParameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $templateParameters
     * @param array<mixed> $parameters
     */
    public static function printInputSelection(string $template, array $templateParameters, array $parameters): void
    {
        $typeParameters = [
            'options' => [],
        ];

        $parameters = array_merge(static::$defaultParameters, $typeParameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::loadTemplate(
            $template,
            [
                'template_parameters' => $templateParameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $templateParameters
     * @param array<mixed> $parameters
     */
    public static function printInputUpload(string $template, array $templateParameters, array $parameters): void
    {
        $typeParameters = [
            'file_types' => 'PNG, JPG, PDF',
            'max_size' => '10 Mb',
        ];

        $parameters = array_merge(static::$defaultParameters, $typeParameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::loadTemplate(
            $template,
            [
                'template_parameters' => $templateParameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $templateParameters
     * @param array<mixed> $parameters
     */
    public static function printInputDatepicker(string $template, array $templateParameters, array $parameters): void
    {
        $typeParameters = [
            'locale' => 'en',
            'placeholder' => '',
        ];

        $parameters = array_merge(static::$defaultParameters, $typeParameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::loadTemplate(
            $template,
            [
                'template_parameters' => $templateParameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $templateParameters
     * @param array<mixed> $parameters
     */
    public static function printInputTimepicker(string $template, array $templateParameters, array $parameters): void
    {
        $typeParameters = [
            'placeholder' => '',
        ];

        $parameters = array_merge(static::$defaultParameters, $typeParameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::loadTemplate(
            $template,
            [
                'template_parameters' => $templateParameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $templateParameters
     * @param array<mixed> $parameters
     */
    public static function printInputSignature(string $template, array $templateParameters, array $parameters): void
    {
        $typeParameters = [
        ];

        $parameters = array_merge(static::$defaultParameters, $typeParameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::loadTemplate(
            $template,
            [
                'template_parameters' => $templateParameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $templateParameters
     * @param array<mixed> $parameters
     */
    public static function printInputRadioGroup(string $template, array $templateParameters, array $parameters): void
    {
        $typeParameters = [
            'options' => [],
        ];

        $parameters = array_merge(static::$defaultParameters, $typeParameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::loadTemplate(
            $template,
            [
                'template_parameters' => $templateParameters,
                'parameters' => $parameters, ]
        );
    }
}
