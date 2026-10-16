<?php

return (new PhpCsFixer\Config())
    ->setRules(['@Symfony' => true, 'declare_strict_types' => true])
    ->setRiskyAllowed(true)
    ->setCacheFile(__DIR__.'/var/php-cs-fixer.cache')
    ->setFinder(PhpCsFixer\Finder::create()->in([__DIR__.'/src', __DIR__.'/tests', __DIR__.'/migrations']));
