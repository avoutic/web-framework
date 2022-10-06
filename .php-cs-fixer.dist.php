<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/actions')
    ->in(__DIR__.'/htdocs')
    ->in(__DIR__.'/includes')
    ->in(__DIR__.'/scripts')
    ->in(__DIR__.'/templates')
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,

        // Preferences
        'braces' => [ 'position_after_control_structures' => 'next' ], // Allman style
        'echo_tag_syntax' => [ 'format' => 'short' ],
        'increment_style' => [ 'style' => 'post' ],
        'is_null' => false,
        'modernize_strpos' => true,
        'native_function_invocation' => false,
        'no_multiple_statements_per_line' => true,
        'no_unneeded_control_parentheses' => false,
        'octal_notation' => true,
        'random_api_migration' => true,
        'strict_comparison' => false,
        'strict_param' => false,
        'yoda_style' => false,

        'ordered_class_elements' => false, // Big change, requires check
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;
