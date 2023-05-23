<?php

if (!isset($args['template_parameters']['colors']))
{
    throw new \InvalidArgumentException('No colors defined');
}
$colors = $args['template_parameters']['colors'];
$required_colors = ['focus:border', 'focus:ring'];
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

$show_fmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$width_fmt = (strlen($parameters['width'])) ? $parameters['width'] : $default_width;

echo <<<HTML
<div {$show_fmt} x-data="timePicker()" x-init="initTime('{$parameters['value']}')" class="{$width_fmt}">
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
  <input type="hidden"
         name="{$parameters['name']}"
         x-model="timeValue" />
  <div class="mt-1 flex -space-x-px">
    <div class="w-16 flex min-w-0">
      <input x-ref="timeHour"
             x-on:focus="\$refs.timeHour.select()"
             x-on:change="update()"
             @keydown.enter.prevent="update(true)"
             x-model="timeHour"
             type="number"
             min="0" max="23"
             name="{$parameters['name']}_hour"
             placeholder="{$parameters['placeholder']}"
             class="{$colors['focus:ring']} {$colors['focus:border']} relative block w-full px-3 py-2 rounded-none rounded-l-md bg-transparent focus:z-10 sm:text-sm border border-gray-300" />
    </div>
    <div class="flex block px-2 shadow-sm border border-gray-300 items-center">
      <span>:</span>
    </div>
    <div class="w-16 flex min-w-0">
      <input x-ref="timeMinutes"
             x-on:focus="\$refs.timeMinutes.select()"
             x-on:change="update()"
             @keydown.enter.prevent="update(true)"
             x-model="timeMinutes"
             type="number"
             min="0" max="59"
             name="{$parameters['name']}_minutes"
             placeholder="{$parameters['placeholder']}"
             class="{$colors['focus:ring']} {$colors['focus:border']} relative block w-full px-3 py-2 rounded-none rounded-r-md bg-transparent focus:z-10 sm:text-sm border border-gray-300" />
    </div>
  </div>
</div>
HTML;
