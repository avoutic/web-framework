<?php

if (!isset($args['template_parameters']['colors']))
{
    throw new \InvalidArgumentException('No colors defined');
}
$colors = $args['template_parameters']['colors'];
$requiredColors = ['border', 'focus:ring'];
if (array_diff(array_keys($colors), $requiredColors) != array_diff($requiredColors, array_keys($colors)))
{
    throw new \InvalidArgumentException('Missing required colors');
}

if (!isset($args['template_parameters']['texts']))
{
    throw new \InvalidArgumentException('No texts defined');
}
$texts = $args['template_parameters']['texts'];
$requiredTexts = ['clear'];
if (array_diff(array_keys($texts), $requiredTexts) != array_diff($requiredTexts, array_keys($texts)))
{
    throw new \InvalidArgumentException('Missing required texts');
}

if (!isset($args['template_parameters']['default_width']))
{
    throw new \InvalidArgumentException('No default_width defined');
}

$defaultWidth = $args['template_parameters']['default_width'];

$parameters = $args['parameters'];

$showFmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$widthFmt = (strlen($parameters['width'])) ? $parameters['width'] : $defaultWidth;

echo <<<HTML
<div {$showFmt} x-data="canvas()" x-init="initializePad()" id="{$parameters['id']}" class="{$widthFmt}">
  <input x-model="signatureData" type="hidden" name="{$parameters['name']}" value="" />
  <div x-on:mouseup="saveSignature()" x-on:touchend="saveSignature()" class="w-72 h-48 bg-gray-200 rounded-md border {$colors['border']}">
    <canvas x-ref="canvas" class="w-full h-full"></canvas>
  </div>
  <div class="pt-2">
    <button type="button" @click="clearPad" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 {$colors['focus:ring']}">
      {$texts['clear']}
    </button>
  </div>
</div>
HTML;
