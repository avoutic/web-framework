<?php

if (!isset($args['template_parameters']['colors']))
{
    throw new \InvalidArgumentException('No colors defined');
}

$colors = $args['template_parameters']['colors'];
$requiredColors = ['text', 'focus:ring'];
if (array_diff(array_keys($colors), $requiredColors) != array_diff($requiredColors, array_keys($colors)))
{
    throw new \InvalidArgumentException('Missing required colors');
}

if (!isset($args['template_parameters']['default_width']))
{
    throw new \InvalidArgumentException('No default_width defined');
}

$defaultWidth = $args['template_parameters']['default_width'];

$parameters = $args['parameters'];

$checkedFmt = ($parameters['checked']) ? 'checked' : '';
$modelFmt = (strlen($parameters['model'])) ? "x-model='{$parameters['model']}'" : '';
$showFmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$widthFmt = (strlen($parameters['width'])) ? $parameters['width'] : $defaultWidth;

echo <<<HTML
<div {$showFmt} class="flex items-start {$widthFmt}">
  <div class="flex items-center h-5">
    <input id="{$parameters['id']}"
           name="{$parameters['name']}"
           type="checkbox"
           value="{$parameters['value']}"
           {$modelFmt}
           {$checkedFmt}
           class="{$colors['focus:ring']} h-4 w-4 {$colors['text']} border-gray-300 rounded">
  </div>
  <div class="ml-3 text-sm">
    <label for="{$parameters['id']}" class="font-medium text-gray-700">{$parameters['label']}</label>
HTML;

if (strlen($parameters['extra_label']))
{
    echo <<<HTML
     <p class="text-sm text-gray-500">{$parameters['extra_label']}</p>
HTML;
}

echo <<<'HTML'
  </div>
</div>
HTML;
