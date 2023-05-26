<?php

if (!isset($args['template_parameters']['colors']))
{
    throw new \InvalidArgumentException('No colors defined');
}

$colors = $args['template_parameters']['colors'];
$requiredColors = ['readonly:bg', 'focus:ring', 'focus:border'];
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

$modelFmt = (strlen($parameters['model'])) ? "x-model='{$parameters['model']}'" : "x-model='content'";
$modelName = (strlen($parameters['model'])) ? $parameters['model'] : 'content';
$onChangeFmt = (strlen($parameters['on_change'])) ? "x-on:change=\"{$parameters['on_change']}\"" : '';
$onKeyupFmt = (strlen($parameters['on_keyup'])) ? "x-on:keyup=\"{$parameters['on_keyup']}\"" : '';
$requiredFmt = ($parameters['required']) ? 'required' : '';
$readonlyFmt = ($parameters['readonly']) ? 'readonly' : '';
$readonlyClassFmt = ($parameters['readonly']) ? "{$colors['readonly:bg']}" : '';
$showFmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$widthFmt = (strlen($parameters['width'])) ? $parameters['width'] : $defaultWidth;

$counterInfo = [
    'content' => $parameters['value'],
    'limit' => $parameters['max_length'],
];

$counterInfoFmt = json_encode($counterInfo, JSON_HEX_APOS);

echo <<<HTML
<div {$showFmt} x-data='{$counterInfoFmt}' class="{$widthFmt}">
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
      {$modelFmt}
      {$onChangeFmt}
      {$onKeyupFmt}
      type="{$parameters['type']}"
      id="{$parameters['id']}"
      name="{$parameters['name']}"
      {$requiredFmt}
      {$readonlyFmt}
      placeholder="{$parameters['placeholder']}"
      class="{$readonlyClassFmt} {$colors['focus:ring']} {$colors['focus:border']} flex-1 block w-full rounded-none rounded-r-md sm:text-sm border-gray-300" />
  </div>
HTML;
}
else
{
    echo <<<HTML
  <input
    {$modelFmt}
    {$onChangeFmt}
    {$onKeyupFmt}
    type="{$parameters['type']}"
    id="{$parameters['id']}"
    name="{$parameters['name']}"
    {$requiredFmt}
    {$readonlyFmt}
    placeholder="{$parameters['placeholder']}"
    class="mt-1 {$readonlyClassFmt} {$colors['focus:ring']} {$colors['focus:border']} block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" />
HTML;
}

if ($counterInfo['limit'] > 0)
{
    echo <<<'HTML'
  <div class="text-right">
    <span x-ref="remaining" class="text-xs text-gray-500">( <span x-text="limit - {$modelName}.length"></span> / <span x-text="limit"></span> )</span>
  </div>
HTML;
}

echo <<<'HTML'
</div>
HTML;
