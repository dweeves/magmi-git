<?php

$finder = PhpCsFixer\Finder::create()
    ->in('magmi');


$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PSR12' => true,
    'binary_operator_spaces' => [
        'operators' => [
            '=' => 'single_space',
            '=>' => 'single_space',
        ],
    ],
])
    ->setFinder($finder);
