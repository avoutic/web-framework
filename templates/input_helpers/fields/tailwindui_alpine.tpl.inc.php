<?php
WF::verify(isset($args['template_parameters']['colors']), 'No colors defined');
$colors = $args['template_parameters']['colors'];
$required_colors = array('focus:ring', 'focus:border');
WF::verify(count(array_diff(array_keys($colors), $required_colors)) == 0, 'Missing required colors');

WF::verify(isset($args['template_parameters']['default_width']), 'No default_width defined');
$default_width = $args['template_parameters']['default_width'];

$parameters = $args['parameters'];

$model_fmt = (strlen($parameters['model'])) ? "x-model='{$parameters['model']}'" : '';
$required_fmt = ($parameters['required']) ? 'required' : '';
$readonly_fmt = ($parameters['readonly']) ? 'readonly' : '';
$width_fmt = (strlen($parameters['width'])) ? $parameters['width'] : $default_width;

$counter_info = array(
    'content' => $parameters['value'],
    'limit' => $parameters['max_length'],
);

$counter_info_fmt = json_encode($counter_info);

echo <<<HTML
<div x-data='{$counter_info_fmt}' class="{$width_fmt}">
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
      x-ref="content" x-model="content"
      type="{$parameters['type']}"
      id="{$parameters['id']}"
      name="{$parameters['name']}"
      {$required_fmt}
      {$readonly_fmt}
      placeholder="{$parameters['placeholder']}"
      class="{$colors['focus:ring']} {$colors['focus:border']} flex-1 block w-full rounded-none rounded-r-md sm:text-sm border-gray-300" />
  </div>
HTML;
}
else
{
    echo <<<HTML
  <input
    x-ref="content" x-model="content"
    type="{$parameters['type']}"
    id="{$parameters['id']}"
    name="{$parameters['name']}"
    {$required_fmt}
    {$readonly_fmt}
    placeholder="{$parameters['placeholder']}"
    class="mt-1 {$colors['focus:ring']} {$colors['focus:border']} block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" />
HTML;
}

if ($counter_info['limit'] > 0)
{
    echo <<<HTML
  <div class="text-right">
    <span x-ref="remaining" class="text-xs text-gray-500">( <span x-text="limit - content.length"></span> / <span x-text="limit"></span> )</span>
  </div>
HTML;
}

echo <<<HTML
</div>
HTML;
?>

