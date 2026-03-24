<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Bibtex Extension (Mit Uniol Anpassungen)',
    'description' => '',
    'category' => 'plugin',
    'author' => '',
    'author_email' => '',
    'author_company' => '',
    'state' => 'excludeFromUpdates',
    'version' => '3.1.1-dev',
    'constraints' => [
        'depends' => [
            'extbase' => '',
            'fluid' => '',
            'typo3' => '11.5.19-12.99.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => ['classmap' => ['Classes']]
];
