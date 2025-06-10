<?php

$mainFinder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/actions')
    ->in(__DIR__.'/config')
    ->in(__DIR__.'/definitions')
    ->in(__DIR__.'/htdocs')
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/scripts')
    ->in(__DIR__.'/templates')
    ->in(__DIR__.'/tests/Instantiations')
    ->in(__DIR__.'/tests/Unit')
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,

        // Preferences
        'braces_position' => [ 'control_structures_opening_brace' => 'next_line_unless_newline_at_signature_end'], // Allman style
        'control_structure_continuation_position' => [ 'position' => 'next_line'], // Allman style
        'echo_tag_syntax' => [ 'format' => 'short' ],
        'increment_style' => [ 'style' => 'post' ],
        'is_null' => false,
        'modernize_strpos' => true,
        'native_function_invocation' => false,
        'no_multiple_statements_per_line' => true,
        'no_unneeded_control_parentheses' => false,
        'octal_notation' => true,
        'operator_linebreak' => false,
        'phpdoc_param_order' => true,
        'random_api_migration' => true,
        'static_lambda' => false, // Does not work well with Slim routing groups and closures
        'strict_comparison' => false,
        'strict_param' => false,
        'yoda_style' => false,

        'ordered_class_elements' => false, // Big change, requires check
    ])
    ->setFinder($mainFinder)
    ->setRiskyAllowed(true)
;
