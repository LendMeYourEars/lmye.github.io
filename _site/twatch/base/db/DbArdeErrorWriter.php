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

	class ArdeDbDbErrorWriter {
		private $db;
		private $sub;
		private $tableName;
		private $maxErrors;

		function __construct( $db, $sub, $tableName, $maxErrors ) {
			$this->db = $db;
			$this->sub = $sub;
			$this->tableName = $tableName;
			$this->maxErrors = $maxErrors;
		}

		public function write( $str ) {
			$res = $this->db->query_sub( $this->sub, 'SELECT COUNT(*) FROM', $this->tableName );

			if( !( $r = $this->db->fetchRow( $res ) ) ) throw new ArdeException( "can't get the number of errors in db" );

			if( (int)$r[0] >= $this->maxErrors ) {
				$this->db->query_sub( $this->sub, 'DELETE FROM', $this->tableName,' ORDER BY id LIMIT 1' );
			}

			$this->db->query_sub( $this->sub, 'INSERT INTO', $this->tableName, "VALUES( NULL, ".$this->db->string( $str )." )" );

			return $this->db->lastInsertId();

		}

		public function clear() {
			$this->db->query_sub( $this->sub, 'TRUNCATE', $this->tableName );
		}

		public function getErrorsCount() {
			$res = $this->db->query_sub( $this->sub, 'SELECT COUNT(*) FROM', $this->tableName );
			$r = $this->db->fetchRow( $res );
			return (int)$r[0];
		}

		public function getErrors() {
			$res = $this->db->query_sub( $this->sub, 'SELECT id, '.$this->db->stringResult( 'str' ).' FROM', $this->tableName,' ORDER BY id DESC' );
			$o = array();
			while( $r = $this->db->fetchRow( $res ) ) {
				$o[ (int)$r[0] ] = $r[1];
			}
			return $o;
		}

		public function install( $overwrite ) {
			$this->db->createTable( $this->sub, $this->tableName, '( id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, str TEXT CHARACTER SET '.$this->db->getCharset().' COLLATE '.$this->db->getCollation().' )', $overwrite );
		}

		public function uninstall() {
			$this->db->dropTableIfExists( $this->sub, $this->tableName );
		}
	}
?>