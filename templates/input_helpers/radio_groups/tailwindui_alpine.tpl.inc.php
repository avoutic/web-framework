<?php

if (!isset($args['template_parameters']['colors']))
{
    throw new \InvalidArgumentException('No colors defined');
}
$colors = $args['template_parameters']['colors'];
$requiredColors = ['bg', 'border', 'text-input', 'text-label', 'text-extra-label', 'focus:ring'];
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

$modelVarFmt = (strlen($parameters['model'])) ? $parameters['model'] : $parameters['name'];
$modelFmt = "x-model=\"{$modelVarFmt}\"";
$showFmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$widthFmt = (strlen($parameters['width'])) ? $parameters['width'] : $defaultWidth;
$xDataFmt = (strlen($parameters['model'])) ? '' : "x-data=\"{ {$parameters['name']}: '{$parameters['value']}' }\"";

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
  <fieldset {$xDataFmt}>
    <div class="bg-white rounded-md -space-y-px mt-3">
HTML;

$count = 0;

foreach ($parameters['options'] as $value => $optionInfo)
{
    if (!isset($optionInfo['label']))
    {
        throw new \InvalidArgumentException('Label of option not set');
    }

    $count++;
    $idFmt = "{$parameters['id']}-{$count}";
    $selectedFmt = (strlen($parameters['value']) == strlen($value) && $parameters['value'] == $value) ? 'selected' : '';

    $roundingFmt = '';
    if ($count == 1)
    {
        $roundingFmt = 'rounded-tl-md rounded-tr-md';
    }
    elseif ($count == count($parameters['options']))
    {
        $roundingFmt = 'rounded-bl-md rounded-br-md';
    }

    echo <<<HTML
      <div
        class="relative border {$roundingFmt} flex"
        :class="{ '{$colors['bg']} {$colors['border']} z-10': {$modelVarFmt} === '{$value}',
                  'border-gray-200': {$modelVarFmt} !== '{$value}' }"
        >
        <div class="flex items-center h-5 my-4 ml-4">
          <input {$modelFmt} id="{$idFmt}" name="{$parameters['name']}" type="radio" value="{$value}" class="{$colors['focus:ring']} h-4 w-4 {$colors['text-input']} cursor-pointer border-gray-300">
        </div>
        <label for="{$idFmt}" class="p-4 pl-3 flex flex-col cursor-pointer w-full">
          <span
            class="block text-sm font-medium"
            :class="{ '{$colors['text-label']}': {$modelVarFmt} === '{$value}', 'text-gray-900': {$modelVarFmt} !== '{$value}' }"
            >
            {$optionInfo['label']}
          </span>
HTML;

    if (isset($optionInfo['extra_label']))
    {
        echo <<<HTML
          <span class="block text-sm"
                :class="{ '{$colors['text-extra-label']}': {$modelVarFmt} === '{$value}', 'text-gray-500': {$modelVarFmt} !== '{$value}' }"
                >
            {$optionInfo['extra_label']}
          </span>
HTML;
    }

    echo <<<'HTML'
        </label>
      </div>
HTML;
}

echo <<<'HTML'
    </div>
  </fieldset>
</div>
HTML;
