<?php
use \WebFramework\Core\WF;

WF::verify(isset($args['template_parameters']['colors']), 'No colors defined');
$colors = $args['template_parameters']['colors'];
$required_colors = array('text', 'focus:ring');
WF::verify(count(array_diff(array_keys($colors), $required_colors)) == 0, 'Missing required colors');

WF::verify(isset($args['template_parameters']['default_width']), 'No default_width defined');
$default_width = $args['template_parameters']['default_width'];

$parameters = $args['parameters'];

$checked_fmt = ($parameters['checked']) ? 'checked' : '';
$show_fmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$width_fmt = (strlen($parameters['width'])) ? $parameters['width'] : $default_width;

echo <<<HTML
<div {$show_fmt} class="flex items-start {$width_fmt}">
  <div class="flex items-center h-5">
    <input id="{$parameters['id']}"
           name="{$parameters['name']}"
           type="checkbox"
           value="{$parameters['value']}"
           {$checked_fmt}
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

echo <<<HTML
  </div>
</div>
HTML;
?>
