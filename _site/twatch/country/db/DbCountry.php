<?php
	/********************************************************************/
	/*                                                                  */
	/*      Copyright (C) 2004 Arash Dejkam, All Rights Reserved.       */
	/*      http://www.tracewatch.com                                   */
	/*                                                                  */
	/*      Please read the licence file distributed with this          */
	/*      file or the one available at tracewatch.com for             */
	/*      the terms under which you can use or modify this file.      */
	/*                                                                  */
	/********************************************************************/
    global $ardeBase;
    
	require_once $ardeBase->path( 'db/Db.php' );
	
	class ArdeDbCountry {
		private $db;
		
		private $rowValues;
		
		const ROWS_PER_TASK = 100;
		
		public function __construct( ArdeDb $db ) {
			$this->db = $db;
			$this->rowValues = new ArdeAppender( ', ' );
		}
		
		public function fetchCountry( $ip ) {
			$res = $this->db->query( 'SELECT '.$ip.' >= ipfrom, id FROM', 'cou', 'WHERE ipto >= '.$ip.' ORDER BY ipto LIMIT 1' );
	
			if( !( $r = $this->db->fetchRow( $res ) ) ) return null;
			if( !$r[0] ) return null;
			return (int)$r[1];
		}
		
		public function install( $overwrite = false ) {
			$this->db->createTable( '', 'cou', '( ipfrom INT UNSIGNED NOT NULL, ipto INT UNSIGNED NOT NULL, id SMALLINT UNSIGNED NOT NULL, UNIQUE( ipto ) )', $overwrite );
		}
		
		public function uninstall() {
			$this->db->dropTableIfExists( '', 'cou' );
		}
		
		function addRow( $ipFrom, $ipTo, $countryId ) {
			if( $this->rowValues->c >= self::ROWS_PER_TASK ) $this->flushRows();
			$this->rowValues->append( '( '.$ipFrom.', '.$ipTo.', '.$countryId.' )' );
		}
		
		function flushRows() {
			if( !$this->rowValues->c ) return;
			$this->db->query( 'INSERT INTO', 'cou', '( ipfrom, ipto, id ) values '.$this->rowValues->s );
			$this->rowValues = new ArdeAppender( ', ' );
		}
	}
?>