<?php

$finder = (new PhpCsFixer\Finder())
    ->in('app')
    ->in('tests')
    ->exclude('tests/Coverage')
    ->in('routes')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'no_extra_blank_lines' => true,
        'declare_strict_types' => true,
        'single_quote' => true,
        'strict_comparison' => true,
        'modernize_types_casting' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;
