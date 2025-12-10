<?php

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/translations',
    ])
;

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@PHP8x2Migration' => true,
        '@PHP8x2Migration:risky' => true,
        '@PHPUnit9x1Migration:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'protected_to_private' => false,
        'native_constant_invocation' => ['strict' => false],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'remove_inheritdoc' => true],
        'modernize_strpos' => true,
        'get_class_to_class_keyword' => true,
        'declare_strict_types' => true,
        'phpdoc_separation' => false,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
