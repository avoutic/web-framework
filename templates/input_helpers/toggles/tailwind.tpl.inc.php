<?php

use WebFramework\Core\WF;

WF::verify(isset($args['template_parameters']['colors']), 'No colors defined');
$colors = $args['template_parameters']['colors'];
$required_colors = ['bg', 'focus:ring'];
WF::verify(count(array_diff(array_keys($colors), $required_colors)) == 0, 'Missing required colors');

WF::verify(isset($args['template_parameters']['default_width']), 'No default_width defined');
$default_width = $args['template_parameters']['default_width'];

$parameters = $args['parameters'];

$model_variable_fmt = (strlen($parameters['model'])) ? $parameters['model'] : 'enabled';
$checked_fmt = ($parameters['checked']) ? 'true' : 'false';
$show_fmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$width_fmt = (strlen($parameters['width'])) ? $parameters['width'] : $default_width;

echo <<<HTML
<div {$show_fmt} x-data="{ {$model_variable_fmt}: {$checked_fmt} }" class="flex items-center justify-between {$width_fmt}">
  <span class="flex-grow flex flex-col" id="{$parameters['id']}-label">
    <label for="{$parameters['id']}" class="text-sm font-base text-gray-900">{$parameters['label']}</label>
HTML;

if (strlen($parameters['extra_label']))
{
    echo <<<HTML
    <span class="text-sm text-gray-500">{$parameters['extra_label']}</span>
HTML;
}

echo <<<HTML
  </span>
  <input x-model="{$model_variable_fmt}"
         id="{$parameters['id']}"
         name="{$parameters['name']}"
         type="hidden" />
  <button @click="{$model_variable_fmt} = !{$model_variable_fmt}"
          type="button"
          :class="{'{$colors['bg']}': {$model_variable_fmt}, 'bg-gray-200': !{$model_variable_fmt}}"
          class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 {$colors['focus:ring']}" aria-pressed="false" aria-labelledby="{$parameters['id']}-label">
    <span class="sr-only">Use setting</span>
    <span aria-hidden="true"
          :class="{'translate-x-5': {$model_variable_fmt}, 'translate-x-0': !{$model_variable_fmt}}"
          class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"></span>
  </button>
</div>
HTML;
