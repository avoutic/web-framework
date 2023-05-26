<?php

function snakeToCamel(string $val): string
{
    preg_match('#^_*#', $val, $underscores);
    $underscores = current($underscores);
    $camel = str_replace(' ', '', ucwords(str_replace('_', ' ', $val)));
    $camel = strtolower(substr($camel, 0, 1)).substr($camel, 1);

    return $underscores.$camel;
}

function convert(string $str): string
{
    $name = '[a-z_\x7f-\xff][a-z0-9_\x7f-\xff]*';
    $snakeRegexps = [
        "#::class, '({$name})']#",
        "#'(get_{$name})'#",
        '#(newest_version)#',
        "#(print_input_{$name})#",
        "#->({$name})#",
        "#::({$name})#",
        '#\$('.$name.')#',
        "#function ({$name})#",
    ];
    foreach ($snakeRegexps as $regexp)
    {
        $str = preg_replace_callback($regexp, function ($matches) {
            // print_r($matches);
            $camel = snakeToCamel($matches[1]);

            return str_replace($matches[1], $camel, $matches[0]);
        }, $str);
    }

    return $str;
}

$path = $argv[1];
$Iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
foreach ($Iterator as $file)
{
    if (substr($file, -4) !== '.php')
    {
        continue;
    }
    echo($file);
    $out = convert(file_get_contents($file));
    file_put_contents($file, $out);
}
