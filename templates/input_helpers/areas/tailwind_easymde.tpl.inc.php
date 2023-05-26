<?php

use WebFramework\Core\ActionCore;

if (!isset($args['template_parameters']['colors']))
{
    throw new \InvalidArgumentException('No colors defined');
}
$colors = $args['template_parameters']['colors'];
$requiredColors = ['focus:ring', 'focus:border'];
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

$modelFmt = (strlen($parameters['model'])) ? "x-model='{$parameters['model']}'" : '';
$requiredFmt = (!$parameters['enable_editor'] && $parameters['required']) ? 'required' : '';
$rowsFmt = (strlen($parameters['rows'])) ? "rows=\"{$parameters['rows']}\"" : '';
$showFmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$widthFmt = (strlen($parameters['width'])) ? $parameters['width'] : $defaultWidth;

$valueFmt = '';
$valueJsonFmt = '';
if ($parameters['enable_editor'])
{
    $valueJsonFmt = json_encode($parameters['value']);
}
else
{
    $valueFmt = ActionCore::encode($parameters['value']);
}

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
  <div class="mt-1">
    <textarea
      x-ref="area"
      id="{$parameters['id']}"
      name="{$parameters['name']}"
      {$requiredFmt}
      {$rowsFmt}
      {$modelFmt}
      class="shadow-sm {$colors['focus:ring']} {$colors['focus:border']} block w-full text-sm border-gray-300 rounded-md">{$valueFmt}</textarea>
HTML;

if ($parameters['enable_editor'])
{
    echo <<<HTML
    <script>
      const easyMDE_{$parameters['name']} = new EasyMDE({
          element: document.getElementById('{$parameters['id']}'),
          spellChecker: false,
          initialValue: {$valueJsonFmt},
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
