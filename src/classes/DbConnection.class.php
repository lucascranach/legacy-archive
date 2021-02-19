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
 
 /**
 * Utility Class 'DbConnection' handles the whole Database connection process.
 * Sets User, host, password and database variables.   
 *
 * @author	Joerg Stahlmann <>
 * @package	elements/utility 
 */
class DbConnection {
	// lokale Zugangsdaten zu MySQL
	private $_user;
	private $_host;
	private $_password;
	private $_database;
	
	
	/**
	 * Constructor function of the class
	 */
	public function __construct() {

    $config = new Config;
    $db_credentials = $config->getSection('db');

		// set user name:
		$this->_user     = $db_credentials->user;
		
		// set host adress:
		$this->_host     = $db_credentials->host;
		
		// set password:
		$this->_password = $db_credentials->password;
		
		// set database:
		$this->_database = $db_credentials->db;
	}
	
	
	/**
	 * The Method connects to the given Database and returns the requested connection
	 *
	 * @return mysql connection
	 */
	public function getConnection() {
    
		$con = mysqli_connect($this->_host, $this->_user, $this->_password, $this->_database) or die ("Keine Verbindung moeglich");
		mysqli_set_charset($con, 'utf8');
		
		return $con;
	}
}
?>