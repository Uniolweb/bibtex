<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'bibtex',
    'description' => 'TYPO3 extension for displaying publications from a source in bibtex format',
    'category' => 'plugin',
    'author' => '',
    'author_email' => '',
    'author_company' => '',
    'state' => 'excludeFromUpdates',
    'version' => '4.0.1',
    'constraints' => [
        'depends' => [
            'extbase' => '',
            'fluid' => '',
            'typo3' => '13.4.19-13.99.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => ['classmap' => ['Classes']]
];
