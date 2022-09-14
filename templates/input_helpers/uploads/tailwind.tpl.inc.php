<?php
use \WebFramework\Core\WF;

WF::verify(isset($args['template_parameters']['colors']), 'No colors defined');
$colors = $args['template_parameters']['colors'];
$required_colors = array('border', 'shadow-outline', 'text', 'hover:text', 'focus-within:ring');
WF::verify(count(array_diff(array_keys($colors), $required_colors)) == 0, 'Missing required colors');

WF::verify(isset($args['template_parameters']['default_width']), 'No default_width defined');
$default_width = $args['template_parameters']['default_width'];

$parameters = $args['parameters'];

$show_fmt = (strlen($parameters['show'])) ? "x-cloak x-show=\"{$parameters['show']}\"" : '';
$width_fmt = (strlen($parameters['width'])) ? $parameters['width'] : $default_width;

echo <<<HTML
<div {$show_fmt} x-data="{ fileSelected: false, filename: '' }" class="{$width_fmt}">
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
  <div x-ref="border" class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md relative cursor-pointer">
    <input id="{$parameters['id']}" name="{$parameters['name']}" type="file"
           class="absolute inset-0 z-50 m-0 p-0 w-full h-full outline-none opacity-0 cursor-pointer"
           x-on:change="fileSelected = true; filename = \$event.target.files[0].name;"
           x-on:dragover="\$refs.border.classList.add('{$colors['shadow-outline']}', '{$colors['border']}')"
           x-on:dragleave="\$refs.border.classList.remove('{$colors['shadow-outline']}', '{$colors['border']}')"
           x-on:drop="\$refs.border.classList.remove('{$colors['shadow-outline']}', '{$colors['border']}')"
         />
    <div class="flex flex-col space-y-2 items-center justify-center text-center">
      <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      <div class="flex text-sm text-gray-600">
        <a href="javascript:void()" class="cursor-pointer bg-white rounded-md font-medium {$colors['text']} {$colors['hover:text']} focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 {$colors['focus-within:ring']}">Upload a file</a>
        <p class="pl-1">or drag here</p>
      </div>
      <p class="text-xs text-gray-500">
        {$parameters['file_types']} with a maximum of {$parameters['max_size']}
      </p>
      <p x-cloak x-show="fileSelected" class="text-xs text-gray-500">
        Selected:
        <span x-text="filename" class="font-semibold {$colors['text']}">
        </span>
      </p>
    </div>
  </div>
</div>
HTML;
