<?php

if (!isset($args['template_parameters']['colors']))
{
    throw new \InvalidArgumentException('No colors defined');
}
$colors = $args['template_parameters']['colors'];
$required_colors = ['bg', 'border', 'text-input', 'text-label', 'text-extra-label', 'focus:ring'];
if (array_diff(array_keys($colors), $required_colors) != array_diff($required_colors, array_keys($colors)))
{
    throw new \InvalidArgumentException('Missing required colors');
}

if (!isset($args['template_parameters']['default_width']))
{
    throw new \InvalidArgumentException('No default_width defined');
}

$default_width = $args['template_parameters']['default_width'];

$parameters = $args['parameters'];

$model_var_fmt = (strlen($parameters['model'])) ? $parameters['model'] : $parameters['name'];
$model_fmt = "x-model=\"{$model_var_fmt}\"";
$show_fmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$width_fmt = (strlen($parameters['width'])) ? $parameters['width'] : $default_width;
$x_data_fmt = (strlen($parameters['model'])) ? '' : "x-data=\"{ {$parameters['name']}: '{$parameters['value']}' }\"";

echo <<<HTML
<div {$show_fmt} class="{$width_fmt}">
  <label for="{$parameters['id']}" class="block text-sm font-medium text-gray-700">
    {$parameters['label']}
  </label>
HTML;

if (strlen($parameters['extra_label']))
{
    echo <<<HTML
  <p class="text-sm text-gray-500">{$parameters['extra_label']}</p>
HTML;
}

echo <<<HTML
  <fieldset {$x_data_fmt}>
    <div class="bg-white rounded-md -space-y-px mt-3">
HTML;

$count = 0;

foreach ($parameters['options'] as $value => $option_info)
{
    if (!isset($options_info['label']))
    {
        throw new \InvalidArgumentException('Label of option not set');
    }

    $count++;
    $id_fmt = "{$parameters['id']}-{$count}";
    $selected_fmt = (strlen($parameters['value']) == strlen($value) && $parameters['value'] == $value) ? 'selected' : '';

    $rounding_fmt = '';
    if ($count == 1)
    {
        $rounding_fmt = 'rounded-tl-md rounded-tr-md';
    }
    elseif ($count == count($parameters['options']))
    {
        $rounding_fmt = 'rounded-bl-md rounded-br-md';
    }

    echo <<<HTML
      <div
        class="relative border {$rounding_fmt} flex"
        :class="{ '{$colors['bg']} {$colors['border']} z-10': {$model_var_fmt} === '{$value}',
                  'border-gray-200': {$model_var_fmt} !== '{$value}' }"
        >
        <div class="flex items-center h-5 my-4 ml-4">
          <input {$model_fmt} id="{$id_fmt}" name="{$parameters['name']}" type="radio" value="{$value}" class="{$colors['focus:ring']} h-4 w-4 {$colors['text-input']} cursor-pointer border-gray-300">
        </div>
        <label for="{$id_fmt}" class="p-4 pl-3 flex flex-col cursor-pointer w-full">
          <span
            class="block text-sm font-medium"
            :class="{ '{$colors['text-label']}': {$model_var_fmt} === '{$value}', 'text-gray-900': {$model_var_fmt} !== '{$value}' }"
            >
            {$option_info['label']}
          </span>
HTML;

    if (isset($option_info['extra_label']))
    {
        echo <<<HTML
          <span class="block text-sm"
                :class="{ '{$colors['text-extra-label']}': {$model_var_fmt} === '{$value}', 'text-gray-500': {$model_var_fmt} !== '{$value}' }"
                >
            {$option_info['extra_label']}
          </span>
HTML;
    }

    echo <<<'HTML'
        </label>
      </div>
HTML;
}

echo <<<'HTML'
    </div>
  </fieldset>
</div>
HTML;
