<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Utility;

class BibtexUtility
{
    /**
     * @param $config
     * @return array
     *
     * @deprecated
     */
    public function sortOptions($config)
    {
        $options = [];

        // Hier die Optionen hinzufügen
        $options[] = ['- leer lassen -', ' '];

        $config['items'] = array_merge($options, $config['items']);

        return $config['items'];
    }

    /**
     * @param $config
     * @return array
     *
     * @deprecated
     */
    public function allowOptions($config)
    {
        $options = [];

        // Hier die Optionen hinzufügen
        $options[] = ['- alle erlauben -', ''];

        $config['items'] = array_merge($options, $config['items']);

        return $config['items'];
    }

    /**
     * @param $config
     * @return array
     *
     * @deprecated
     */
    public function denyOptions($config)
    {
        $options = [];

        // Hier die Optionen hinzufügen
        $options[] = ['- keine Ausblenden -', ''];

        $config['items'] = array_merge($options, $config['items']);

        return $config['items'];
    }
}
