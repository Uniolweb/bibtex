<?php

namespace Uniol\Btex\Helper;

class BibtexHelper{

	function sortOptions($config) {
		$options = array();
		
		// Hier die Optionen hinzufügen
		$options[] = array("- leer lassen -"," ");
		
		$config['items'] = array_merge($options, $config['items']);

		return $config['items'];
	}
	
	function allowOptions($config) {
		$options = array();
		
		// Hier die Optionen hinzufügen
		$options[] = array("- leer lassen -"," ");
		
		$config['items'] = array_merge($options, $config['items']);

		return $config['items'];
	}
	
	function denyOptions($config) {
		$options = array();
		
		// Hier die Optionen hinzufügen
		$options[] = array("- leer lassen -"," ");
		
		$config['items'] = array_merge($options, $config['items']);

		return $config['items'];
	}
	
}