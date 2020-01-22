<?php

namespace Uniolit\Bibtex\Helper;

class BibtexHelper
{
    /**
     * @param $config
     * @return array
     *
     * @deprecated
     */
    public function sortOptions($config)
    {
        $options = array();
        
        // Hier die Optionen hinzufügen
        $options[] = array("- leer lassen -"," ");
        
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
        $options = array();
        
        // Hier die Optionen hinzufügen
        $options[] = array('- alle erlauben -', '');
        
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
        $options = array();
        
        // Hier die Optionen hinzufügen
        $options[] = array('- keine Ausblenden -','');
        
        $config['items'] = array_merge($options, $config['items']);

        return $config['items'];
    }
}
