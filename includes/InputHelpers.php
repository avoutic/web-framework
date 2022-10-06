<?php

namespace WebFramework\Core;

class InputHelpers
{
    /**
     * @param array<mixed> $args
     */
    public static function load_template(string $name, array $args = []): void
    {
        $app_dir = WF::get_app_dir();
        WF::verify(file_exists("{$app_dir}/templates/{$name}.inc.php"), 'Requested template not present');

        include "{$app_dir}/templates/{$name}.inc.php";
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    public static function print_input_field(string $template, array $template_parameters, array $parameters): void
    {
        $base_parameters = [
            'extra_label' => '',
            'id' => '',
            'label' => '',
            'max_length' => '',
            'model' => '',
            'name' => '',
            'placeholder' => '',
            'prefix' => '',
            'readonly' => false,
            'required' => false,
            'show' => '',
            'type' => 'text',
            'value' => '',
            'width' => '',
        ];

        $parameters = array_merge($base_parameters, $parameters);

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
        $base_parameters = [
            'extra_label' => '',
            'id' => '',
            'label' => '',
            'name' => '',
            'enable_editor' => false,
            'rows' => '',
            'required' => false,
            'show' => '',
            'value' => '',
            'width' => '',
        ];

        $parameters = array_merge($base_parameters, $parameters);

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
        $base_parameters = [
            'checked' => false,
            'extra_label' => '',
            'id' => '',
            'label' => '',
            'name' => '',
            'show' => '',
            'value' => '1',
            'width' => '',
        ];

        $parameters = array_merge($base_parameters, $parameters);

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
        $base_parameters = [
            'checked' => false,
            'extra_label' => '',
            'id' => '',
            'label' => '',
            'name' => '',
            'show' => '',
            'value' => '1',
            'width' => '',
        ];

        $parameters = array_merge($base_parameters, $parameters);

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
        $base_parameters = [
            'checked' => false,
            'extra_label' => '',
            'id' => '',
            'label' => '',
            'model' => '',
            'name' => '',
            'options' => [],
            'show' => '',
            'value' => '1',
            'width' => '',
        ];

        $parameters = array_merge($base_parameters, $parameters);

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
        $base_parameters = [
            'extra_label' => '',
            'file_types' => 'PNG, JPG, PDF',
            'id' => '',
            'label' => '',
            'max_size' => '10 Mb',
            'name' => '',
            'options' => [],
            'show' => '',
            'value' => '1',
            'width' => '',
        ];

        $parameters = array_merge($base_parameters, $parameters);

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
        $base_parameters = [
            'extra_label' => '',
            'id' => '',
            'label' => '',
            'name' => '',
            'placeholder' => '',
            'show' => '',
            'value' => '1',
            'width' => '',
        ];

        $parameters = array_merge($base_parameters, $parameters);

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
        $base_parameters = [
            'extra_label' => '',
            'id' => '',
            'label' => '',
            'name' => '',
            'placeholder' => '',
            'show' => '',
            'value' => '1',
            'width' => '',
        ];

        $parameters = array_merge($base_parameters, $parameters);

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
        $base_parameters = [
            'id' => '',
            'name' => '',
            'show' => '',
            'width' => '',
        ];

        $parameters = array_merge($base_parameters, $parameters);

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
