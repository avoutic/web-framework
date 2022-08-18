<?php
class InputHelpers
{
    static function load_template($name, $args = array())
    {
        WF::verify(file_exists(WF::$site_templates.$name.'.inc.php'), 'Requested template not present');
        include(WF::$site_templates.$name.'.inc.php');
    }

    static function print_input_field($template, $template_parameters, $parameters)
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

    static function print_input_area($template, $template_parameters, $parameters)
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

    static function print_input_checkbox($template, $template_parameters, $parameters)
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

    static function print_input_toggle($template, $template_parameters, $parameters)
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

    static function print_input_selection($template, $template_parameters, $parameters)
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

    static function print_input_upload($template, $template_parameters, $parameters)
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

    static function print_input_datepicker($template, $template_parameters, $parameters)
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

    static function print_input_timepicker($template, $template_parameters, $parameters)
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

    static function print_input_signature($template, $template_parameters, $parameters)
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
