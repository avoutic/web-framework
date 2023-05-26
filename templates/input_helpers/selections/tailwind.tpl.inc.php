<?php

if (!isset($args['template_parameters']['colors']))
{
    throw new \InvalidArgumentException('No colors defined');
}
$colors = $args['template_parameters']['colors'];
$requiredColors = ['focus:border', 'focus:ring'];
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

$modelFmt = (strlen($parameters['model'])) ? "x-model=\"{$parameters['model']}\"" : '';
$showFmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$widthFmt = (strlen($parameters['width'])) ? $parameters['width'] : $defaultWidth;
$valueParam = $parameters['value'] ?? '';

echo <<<HTML
<div {$showFmt} class="{$widthFmt}">
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
  <select {$modelFmt}
          id="{$parameters['id']}"
          name="{$parameters['name']}"
          class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none {$colors['focus:ring']} {$colors['focus:border']} sm:text-sm">
HTML;

foreach ($parameters['options'] as $value => $name)
{
    $selectedFmt = (strlen($valueParam) == strlen($value) && $valueParam == $value) ? 'selected' : '';

    echo <<<HTML
    <option value="{$value}" {$selectedFmt}>{$name}</option>
HTML;
}

echo <<<'HTML'
  </select>
</div>
HTML;
