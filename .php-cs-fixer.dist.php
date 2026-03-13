<?php

$header = <<<EOF
This file is part of the RollerworksSearch package.

(c) Sebastiaan Stok <s.stok@rollerscapes.net>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

/** @var \Symfony\Component\Finder\Finder $finder */
$finder = PhpCsFixer\Finder::create();
$finder
    ->in([__DIR__.'/lib'])
    ->notPath('Symfony/SearchBundle/Tests/Functional/Application/var')
    ->exclude('Fixtures')
;

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@PER-CS2x0' => true,
        '@PER-CS2x0:risky' => true,
        '@PHP8x1Migration' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHPUnit8x4Migration:risky' => true,
        'attribute_empty_parentheses' => true, // By PER2.0
        'blank_line_before_statement' => [
            'statements' => [
                'break',
                'case',
                'continue',
                'declare',
                'default',
                'do',
                'exit',
                'for',
                'foreach',
                'goto',
                'if',
                'include',
                'include_once',
                'require',
                'require_once',
                'return',
                'switch',
                'throw',
                'try',
                'while',
                'yield_from',
            ],
        ],
        'blank_line_between_import_groups' => false, // Too much noise
        'comment_to_phpdoc' => ['ignored_tags' => ['codeCoverageIgnoreStart', 'codeCoverageIgnoreEnd']],
        'concat_space' => ['spacing' => 'one'], // Clarity for concatenations
        'class_attributes_separation' => [
            'elements' => [ // Keep spaces to a minimum
                'const' => 'none',
                'method' => 'one',
                'property' => 'none',
                'trait_import' => 'none',
                'case' => 'one'
            ],
        ],
        'general_phpdoc_annotation_remove' => ['annotations' => ['since', 'package', 'subpackage', 'date']], // Keep author for borrowed code
        //'header_comment' => ['header' => $header], // Needs configuring per project
        'mb_str_functions' => true,
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline', 'attribute_placement' => 'standalone', 'after_heredoc' => true],
        'method_chaining_indentation' => false, // Too fragile for general usage
        'multiline_promoted_properties' => true, // By PER2.0
        'not_operator_with_successor_space' => true,
        'phpdoc_line_span' => ['property' => 'single', 'const' => 'single'],
        'phpdoc_param_order' => true,
        'phpdoc_tag_casing' => true,
        'regular_callable_call' => true,
        'ordered_class_elements' => false, // Use the depth-first ordering approach
        'ordered_imports' => [
            'imports_order' => ['const', 'class', 'function'],
        ],
        'php_unit_internal_class' => true,
        'php_unit_method_casing' => ['case' => 'snake_case'], // it_[does]_[something]
        'php_unit_strict' => false, // Cannot do this globally
        'php_unit_test_annotation' => ['style' => 'annotation'],

        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'], // Temporary
        'php_unit_test_class_requires_covers' => false,


        'phpdoc_to_comment' => false, // PHPStan needs these
        'phpdoc_var_without_name' => false, // PHPStan needs these
        'single_line_throw' => false, // Long messages might be used
        'strict_comparison' => false, // Cannot do this globally
        'types_spaces' => ['space' => 'single'], // Clarity for separate classes
        'yoda_style' => ['equal' => false, 'identical' => false], // Yoda style is not used
        'header_comment' => ['header' => $header],
    ])
    ->setFinder($finder);

return $config;
