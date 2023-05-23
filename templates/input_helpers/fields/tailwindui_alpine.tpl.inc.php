<?php

if (!isset($args['template_parameters']['colors']))
{
    throw new \InvalidArgumentException('No colors defined');
}

$colors = $args['template_parameters']['colors'];
$required_colors = ['readonly:bg', 'focus:ring', 'focus:border'];
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

$model_fmt = (strlen($parameters['model'])) ? "x-model='{$parameters['model']}'" : "x-model='content'";
$model_name = (strlen($parameters['model'])) ? $parameters['model'] : 'content';
$on_change_fmt = (strlen($parameters['on_change'])) ? "x-on:change=\"{$parameters['on_change']}\"" : '';
$on_keyup_fmt = (strlen($parameters['on_keyup'])) ? "x-on:keyup=\"{$parameters['on_keyup']}\"" : '';
$required_fmt = ($parameters['required']) ? 'required' : '';
$readonly_fmt = ($parameters['readonly']) ? 'readonly' : '';
$readonly_class_fmt = ($parameters['readonly']) ? "{$colors['readonly:bg']}" : '';
$show_fmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$width_fmt = (strlen($parameters['width'])) ? $parameters['width'] : $default_width;

$counter_info = [
    'content' => $parameters['value'],
    'limit' => $parameters['max_length'],
];

$counter_info_fmt = json_encode($counter_info, JSON_HEX_APOS);

echo <<<HTML
<div {$show_fmt} x-data='{$counter_info_fmt}' class="{$width_fmt}">
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

if (strlen($parameters['prefix']))
{
    echo <<<HTML
  <div class="mt-1 flex rounded-md shadow-sm">
    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
      {$parameters['prefix']}
    </span>
    <input
      {$model_fmt}
      {$on_change_fmt}
      {$on_keyup_fmt}
      type="{$parameters['type']}"
      id="{$parameters['id']}"
      name="{$parameters['name']}"
      {$required_fmt}
      {$readonly_fmt}
      placeholder="{$parameters['placeholder']}"
      class="{$readonly_class_fmt} {$colors['focus:ring']} {$colors['focus:border']} flex-1 block w-full rounded-none rounded-r-md sm:text-sm border-gray-300" />
  </div>
HTML;
}
else
{
    echo <<<HTML
  <input
    {$model_fmt}
    {$on_change_fmt}
    {$on_keyup_fmt}
    type="{$parameters['type']}"
    id="{$parameters['id']}"
    name="{$parameters['name']}"
    {$required_fmt}
    {$readonly_fmt}
    placeholder="{$parameters['placeholder']}"
    class="mt-1 {$readonly_class_fmt} {$colors['focus:ring']} {$colors['focus:border']} block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" />
HTML;
}

if ($counter_info['limit'] > 0)
{
    echo <<<'HTML'
  <div class="text-right">
    <span x-ref="remaining" class="text-xs text-gray-500">( <span x-text="limit - {$model_name}.length"></span> / <span x-text="limit"></span> )</span>
  </div>
HTML;
}

echo <<<'HTML'
</div>
HTML;
