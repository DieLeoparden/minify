<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
;

$header = <<<EOF
Pimcore MinifyBundle
Copyright (c) Die Leoparden e.K.
EOF;

return PhpCsFixer\Config::create()
    ->setUsingCache(false)
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'header_comment' => ['header' => $header, 'separate' => 'bottom', 'commentType' => 'PHPDoc'],
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'phpdoc_order' => true,
        'phpdoc_summary' => false,
        'blank_line_after_opening_tag' => false,
        'concat_space' => ['spacing' => 'one'],
        'array_syntax' => ['syntax' => 'short'],
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
    ])
    ->setFinder($finder)
;
