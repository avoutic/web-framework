<?php
class InputHelpers
{
    /**
     * @param array<mixed> $args
     */
    static function load_template(string $name, array $args = array()): void
    {
        WF::verify(file_exists(WF::$site_templates.$name.'.inc.php'), 'Requested template not present');
        include(WF::$site_templates.$name.'.inc.php');
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    static function print_input_field(string $template, array $template_parameters, array $parameters): void
    {
        $base_parameters = array(
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
            'type' => 'text',
            'value' => '',
            'width' => '',
        );

        $parameters = array_merge($base_parameters, $parameters);

        if (!strlen($parameters['id']))
            $parameters['id'] = "input_{$parameters['name']}";

        static::load_template($template, array(
            'template_parameters' => $template_parameters,
            'parameters' => $parameters)
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    static function print_input_area(string $template, array $template_parameters, array $parameters): void
    {
        $base_parameters = array(
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
        );

        $parameters = array_merge($base_parameters, $parameters);

        if (!strlen($parameters['id']))
            $parameters['id'] = "input_{$parameters['name']}";

        static::load_template($template, array(
            'template_parameters' => $template_parameters,
            'parameters' => $parameters)
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    static function print_input_checkbox(string $template, array $template_parameters, array $parameters): void
    {
        $base_parameters = array(
            'checked' => false,
            'extra_label' => '',
            'id' => '',
            'label' => '',
            'name' => '',
            'show' => '',
            'value' => '1',
            'width' => '',
        );

        $parameters = array_merge($base_parameters, $parameters);

        if (!strlen($parameters['id']))
            $parameters['id'] = "input_{$parameters['name']}";

        static::load_template($template, array(
            'template_parameters' => $template_parameters,
            'parameters' => $parameters)
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    static function print_input_toggle(string $template, array $template_parameters, array $parameters): void
    {
        $base_parameters = array(
            'checked' => false,
            'extra_label' => '',
            'id' => '',
            'label' => '',
            'name' => '',
            'show' => '',
            'value' => '1',
            'width' => '',
        );

        $parameters = array_merge($base_parameters, $parameters);

        if (!strlen($parameters['id']))
            $parameters['id'] = "input_{$parameters['name']}";

        static::load_template($template, array(
            'template_parameters' => $template_parameters,
            'parameters' => $parameters)
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    static function print_input_selection(string $template, array $template_parameters, array $parameters): void
    {
        $base_parameters = array(
            'checked' => false,
            'extra_label' => '',
            'id' => '',
            'label' => '',
            'model' => '',
            'name' => '',
            'options' => array(),
            'show' => '',
            'value' => '1',
            'width' => '',
        );

        $parameters = array_merge($base_parameters, $parameters);

        if (!strlen($parameters['id']))
            $parameters['id'] = "input_{$parameters['name']}";

        static::load_template($template, array(
            'template_parameters' => $template_parameters,
            'parameters' => $parameters)
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    static function print_input_upload(string $template, array $template_parameters, array $parameters): void
    {
        $base_parameters = array(
            'extra_label' => '',
            'file_types' => 'PNG, JPG, PDF',
            'id' => '',
            'label' => '',
            'max_size' => '10 Mb',
            'name' => '',
            'options' => array(),
            'show' => '',
            'value' => '1',
            'width' => '',
        );

        $parameters = array_merge($base_parameters, $parameters);

        if (!strlen($parameters['id']))
            $parameters['id'] = "input_{$parameters['name']}";

        static::load_template($template, array(
            'template_parameters' => $template_parameters,
            'parameters' => $parameters)
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    static function print_input_datepicker(string $template, array $template_parameters, array $parameters): void
    {
        $base_parameters = array(
            'extra_label' => '',
            'id' => '',
            'label' => '',
            'name' => '',
            'placeholder' => '',
            'show' => '',
            'value' => '1',
            'width' => '',
        );

        $parameters = array_merge($base_parameters, $parameters);

        if (!strlen($parameters['id']))
            $parameters['id'] = "input_{$parameters['name']}";

        static::load_template($template, array(
            'template_parameters' => $template_parameters,
            'parameters' => $parameters)
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    static function print_input_timepicker(string $template, array $template_parameters, array $parameters): void
    {
        $base_parameters = array(
            'extra_label' => '',
            'id' => '',
            'label' => '',
            'name' => '',
            'placeholder' => '',
            'show' => '',
            'value' => '1',
            'width' => '',
        );

        $parameters = array_merge($base_parameters, $parameters);

        if (!strlen($parameters['id']))
            $parameters['id'] = "input_{$parameters['name']}";

        static::load_template($template, array(
            'template_parameters' => $template_parameters,
            'parameters' => $parameters)
        );
    }

    /**
     * @param array<mixed> $template_parameters
     * @param array<mixed> $parameters
     */
    static function print_input_signature(string $template, array $template_parameters, array $parameters): void
    {
        $base_parameters = array(
            'id' => '',
            'name' => '',
            'show' => '',
            'width' => '',
        );

        $parameters = array_merge($base_parameters, $parameters);

        if (!strlen($parameters['id']))
            $parameters['id'] = "input_{$parameters['name']}";

        static::load_template($template, array(
            'template_parameters' => $template_parameters,
            'parameters' => $parameters)
        );
    }
}
?>
