<?php

use WebFramework\Core\ActionCore;
use WebFramework\Core\WF;

WF::verify(isset($args['template_parameters']['colors']), 'No colors defined');
$colors = $args['template_parameters']['colors'];
$required_colors = ['focus:ring', 'focus:border'];
WF::verify(count(array_diff(array_keys($colors), $required_colors)) == 0, 'Missing required colors');

WF::verify(isset($args['template_parameters']['default_width']), 'No default_width defined');
$default_width = $args['template_parameters']['default_width'];

$parameters = $args['parameters'];

$required_fmt = ($parameters['required']) ? 'required' : '';
$rows_fmt = (strlen($parameters['rows'])) ? "rows=\"{$parameters['rows']}\"" : '';
$show_fmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$width_fmt = (strlen($parameters['width'])) ? $parameters['width'] : $default_width;

$value_fmt = '';
$value_json_fmt = '';
if ($parameters['enable_editor'])
{
    $value_json_fmt = json_encode($parameters['value']);
}
else
{
    $value_fmt = ActionCore::encode($parameters['value']);
}

echo <<<HTML
<div {$show_fmt} class="{$width_fmt}">
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
  <div class="mt-1">
    <textarea
      x-ref="area"
      id="{$parameters['id']}"
      name="{$parameters['name']}"
      {$required_fmt}
      {$rows_fmt}
      class="shadow-sm {$colors['focus:ring']} {$colors['focus:border']} block w-full text-sm border-gray-300 rounded-md">{$value_fmt}</textarea>
HTML;

if ($parameters['enable_editor'])
{
    echo <<<HTML
    <script>
      const easyMDE_{$parameters['name']} = new EasyMDE({
          element: document.getElementById('{$parameters['id']}'),
          spellChecker: false,
          initialValue: {$value_json_fmt},
          hideIcons: [ 'link', 'image', 'side-by-side', 'fullscreen' ],
          autoRefresh: { delay: 300 },
      });
    </script>
HTML;
}

echo <<<'HTML'
  </div>
</div>
HTML;
