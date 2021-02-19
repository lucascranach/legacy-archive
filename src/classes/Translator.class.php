<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Joerg Stahlmann <>
 *  All rights reserved
 *
 *  This script is part of the cranach project. The Cranach Digital Archive (cda) is an interdisciplinary collaborative research resource, 
 *	providing access to art historical, technical and conservation information on paintings by Lucas Cranach (c.1472 - 1553) and his workshop. 
 *	The repository presently provides information on more than 400 paintings including c.5000 images and documents from 19 partner institutions.
 *
 ***************************************************************/

// start session
//session_start();

/**
 * Class 'Translator' translates the string vars in the chosen language.
 * It opens the given xml document via dom and xpath and selects the right
 * translation string.	
 *
 * @author	Joerg Stahlmann <>
 * @package	elements/classes 
 */
class Translator {
	
	// xml document:
	private $_doc;
	// translated string var:
	private $_str;
	// chosen language:
	private $_lang;
	// xpath item:
	private $_xpath;
	
	
	/**
	 * Constructor function of the class
	 */
	public function __construct($doc) {
		
		// set the document
		$this->_doc = new DOMDocument;
		
		// load the document
		$this->_doc->load($doc);
		
		// create the xpath object
		$this->_xpath = new DOMXPath($this->_doc);
		
		// get the language from the session
		$this->_lang = (isset($_SESSION['lang'])) ? $_SESSION['lang'] : 'Englisch';
    // if cookie language != session language
    if(isset($_COOKIE['lang']) && $this->_lang != $_COOKIE['lang']) {
      // get the language from the cookie
      $this->_lang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'Englisch';
    }
	}

	/**
	* Function trans selects the right tag of the given xml file
	* and returns the translation string
	*
	* @param string index of the node
	* @return string node value
	*/
	public function trans($str, $langKey='') {
		
		// if language Key is empty set session language to key
		if($langKey == '') {
			$langKey = $this->_lang;
		}
		
		// clear the return var
		$this->_str = '';
		
		// select the nodes
		$nodes = $this->_xpath->query('//languageKey[@index="'.$langKey.'"]/label[@index="'.$str.'"]');
 		
		// write the node to the return var
		foreach ($nodes as $node) {
				$this->_str = $node->textContent;
		}
		
		// return translation
		return $this->_str;
		
	}
}
