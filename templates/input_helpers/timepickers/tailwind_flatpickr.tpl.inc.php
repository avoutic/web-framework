<?php
use \WebFramework\Core\WF;

WF::verify(isset($args['template_parameters']['colors']), 'No colors defined');
$colors = $args['template_parameters']['colors'];
$required_colors = array('focus:border', 'focus:ring');
WF::verify(count(array_diff(array_keys($colors), $required_colors)) == 0, 'Missing required colors');

WF::verify(isset($args['template_parameters']['default_width']), 'No default_width defined');
$default_width = $args['template_parameters']['default_width'];

$parameters = $args['parameters'];

$show_fmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$width_fmt = (strlen($parameters['width'])) ? $parameters['width'] : $default_width;

echo <<<HTML
<div {$show_fmt}  class="{$width_fmt}">
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
  <input type="text"
         id="{$parameters['id']}"
         name="{$parameters['name']}"
         value="{$parameters['value']}"
         placeholder="{$parameters['placeholder']}"
         class="mt-1 {$colors['focus:ring']} {$colors['focus:border']} block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" />
</div>
<script>
flatpickr("#{$parameters['id']}", {
    "locale": "nl",
    "allowInput": true,
    "dateFormat": "H:i",
    "enableTime": true,
    "noCalendar": true,
    "time_24hr": true
});
</script>
HTML;
?>
