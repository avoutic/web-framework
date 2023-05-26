<?php

if (!isset($args['template_parameters']['colors']))
{
    throw new \InvalidArgumentException('No colors defined');
}

$colors = $args['template_parameters']['colors'];
$requiredColors = ['bg', 'focus:ring'];
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

$modelVariableFmt = (strlen($parameters['model'])) ? $parameters['model'] : 'enabled';
$checkedFmt = ($parameters['checked']) ? 'true' : 'false';
$showFmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$widthFmt = (strlen($parameters['width'])) ? $parameters['width'] : $defaultWidth;

echo <<<HTML
<div {$showFmt} x-data="{ {$modelVariableFmt}: {$checkedFmt} }" class="flex items-center justify-between {$widthFmt}">
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
  <input x-model="{$modelVariableFmt}"
         id="{$parameters['id']}"
         name="{$parameters['name']}"
         type="hidden" />
  <button @click="{$modelVariableFmt} = !{$modelVariableFmt}"
          type="button"
          :class="{'{$colors['bg']}': {$modelVariableFmt}, 'bg-gray-200': !{$modelVariableFmt}}"
          class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 {$colors['focus:ring']}" aria-pressed="false" aria-labelledby="{$parameters['id']}-label">
    <span class="sr-only">Use setting</span>
    <span aria-hidden="true"
          :class="{'translate-x-5': {$modelVariableFmt}, 'translate-x-0': !{$modelVariableFmt}}"
          class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"></span>
  </button>
</div>
HTML;
