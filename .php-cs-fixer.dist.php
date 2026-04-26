<?php
declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->exclude('Fixtures');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS3.0' => true,
        '@PER-CS3.0:risky' => true,
        'declare_strict_types' => true,
    ])
    ->setFinder($finder)
    ->setIndent('    ')
    ->setLineEnding("\n")
    ->setCacheFile(__DIR__ . '/cache/php-cs-fixer/.cache');
