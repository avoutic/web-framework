<?php
use \WebFramework\Core\WF;

WF::verify(isset($args['template_parameters']['colors']), 'No colors defined');
$colors = $args['template_parameters']['colors'];
$required_colors = array('border', 'focus:ring');
WF::verify(count(array_diff(array_keys($colors), $required_colors)) == 0, 'Missing required colors');

WF::verify(isset($args['template_parameters']['default_width']), 'No default_width defined');
$default_width = $args['template_parameters']['default_width'];

$parameters = $args['parameters'];

$show_fmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$width_fmt = (strlen($parameters['width'])) ? $parameters['width'] : $default_width;

echo <<<HTML
<div {$show_fmt} x-data="canvas()" x-init="initializePad()" id="{$parameters['id']}" class="{$width_fmt}">
  <input x-model="signatureData" type="hidden" name="{$parameters['name']}" value="" />
  <div x-on:mouseup="saveSignature()" x-on:touchend="saveSignature()" class="w-72 h-48 bg-gray-200 rounded-md border {$colors['border']}">
    <canvas x-ref="canvas" class="w-full h-full"></canvas>
  </div>
  <div class="pt-2">
    <button type="button" @click="clearPad" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 {$colors['focus:ring']}">
      Clear
    </button>
  </div>
</div>
HTML;
?>
