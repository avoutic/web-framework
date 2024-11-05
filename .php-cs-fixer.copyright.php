<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$header = <<<'EOF'
This file is part of WebFramework.

(c) Avoutic <avoutic@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

$headerFinder = Finder::create()
    ->in(__DIR__.'/actions')
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/scripts')
;

$config = new Config();

return $config->setRules([
    '@PSR12' => false,

    // Copyright header
    'header_comment' => ['header' => $header],
])
    ->setFinder($headerFinder)
;
