<?php

namespace WebFramework\Core;

class InputHelpers
{
    /** @var array<string, mixed> */
    public static array $default_parameters = [
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
    public static function load_template(string $name, array $args = []): void
    {
        $app_dir = WF::get_app_dir();

        if (!file_exists("{$app_dir}/templates/{$name}.inc.php"))
        {
            throw new \InvalidArgumentException('Requested template not present');
        }

        include "{$app_dir}/templates/{$name}.inc.php";
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    public static function print_input_field(string $template, array $template_parameters, array $parameters): void
    {
        $type_parameters = [
            'max_length' => '',
            'on_keyup' => '',
            'placeholder' => '',
            'prefix' => '',
            'readonly' => false,
            'type' => 'text',
        ];

        $parameters = array_merge(static::$default_parameters, $type_parameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::load_template(
            $template,
            [
                'template_parameters' => $template_parameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    public static function print_input_area(string $template, array $template_parameters, array $parameters): void
    {
        $type_parameters = [
            'enable_editor' => false,
            'on_keyup' => '',
            'rows' => '',
        ];

        $parameters = array_merge(static::$default_parameters, $type_parameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::load_template(
            $template,
            [
                'template_parameters' => $template_parameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    public static function print_input_checkbox(string $template, array $template_parameters, array $parameters): void
    {
        $type_parameters = [
            'checked' => false,
            'value' => '1',
        ];

        $parameters = array_merge(static::$default_parameters, $type_parameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::load_template(
            $template,
            [
                'template_parameters' => $template_parameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    public static function print_input_toggle(string $template, array $template_parameters, array $parameters): void
    {
        $type_parameters = [
            'checked' => false,
        ];

        $parameters = array_merge(static::$default_parameters, $type_parameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::load_template(
            $template,
            [
                'template_parameters' => $template_parameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    public static function print_input_selection(string $template, array $template_parameters, array $parameters): void
    {
        $type_parameters = [
            'options' => [],
        ];

        $parameters = array_merge(static::$default_parameters, $type_parameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::load_template(
            $template,
            [
                'template_parameters' => $template_parameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    public static function print_input_upload(string $template, array $template_parameters, array $parameters): void
    {
        $type_parameters = [
            'file_types' => 'PNG, JPG, PDF',
            'max_size' => '10 Mb',
        ];

        $parameters = array_merge(static::$default_parameters, $type_parameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::load_template(
            $template,
            [
                'template_parameters' => $template_parameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    public static function print_input_datepicker(string $template, array $template_parameters, array $parameters): void
    {
        $type_parameters = [
            'locale' => 'en',
            'placeholder' => '',
        ];

        $parameters = array_merge(static::$default_parameters, $type_parameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::load_template(
            $template,
            [
                'template_parameters' => $template_parameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    public static function print_input_timepicker(string $template, array $template_parameters, array $parameters): void
    {
        $type_parameters = [
            'placeholder' => '',
        ];

        $parameters = array_merge(static::$default_parameters, $type_parameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::load_template(
            $template,
            [
                'template_parameters' => $template_parameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    public static function print_input_signature(string $template, array $template_parameters, array $parameters): void
    {
        $type_parameters = [
        ];

        $parameters = array_merge(static::$default_parameters, $type_parameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::load_template(
            $template,
            [
                'template_parameters' => $template_parameters,
                'parameters' => $parameters, ]
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    public static function print_input_radio_group(string $template, array $template_parameters, array $parameters): void
    {
        $type_parameters = [
            'options' => [],
        ];

        $parameters = array_merge(static::$default_parameters, $type_parameters, $parameters);

        if (!strlen($parameters['id']))
        {
            $parameters['id'] = "input_{$parameters['name']}";
        }

        static::load_template(
            $template,
            [
                'template_parameters' => $template_parameters,
                'parameters' => $parameters, ]
        );
    }
}
