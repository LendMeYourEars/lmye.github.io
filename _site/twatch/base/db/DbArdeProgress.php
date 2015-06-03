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
  
	class ArdeDbProgress {
		protected $db;
		protected $tableName;
		protected $channelId;
		
		public function __construct( ArdeDb $db, $tableName, $channelId ) {
			$this->db = $db;
			$this->tableName = $tableName;
			$this->channelId = $channelId;
		}
		
		public function set( $progress ) {
			$this->db->query( 'INSERT INTO', $this->tableName, 'VALUES ( '.$this->channelId.', '.$progress.', UNIX_TIMESTAMP() ) ON DUPLICATE KEY UPDATE prog = VALUES( prog )' );
		}
		
		public function get() {
			$res = $this->db->query( 'SELECT prog FROM ', $this->tableName, 'WHERE chn = '.$this->channelId );
			if( $r = $this->db->fetchRow( $res ) ) {
				return (float)$r[0];
			}
			return false;
		}
		
		public function cleanup( $seconds ) {
			$this->db->query( 'DELETE FROM', $this->tableName, 'WHERE sts < UNIX_TIMESTAMP() - '.$seconds );
		}
		
		public function install( $overwrite ) {
			$this->db->createTable( '', $this->tableName, '( chn INT UNSIGNED NOT NULL PRIMARY KEY, prog FLOAT NOT NULL DEFAULT 0, sts INT UNSIGNED )', $overwrite );
		}
		
		public function uninstall() {
			$this->db->dropTableIfExists( '', $this->tableName );
		}
	}
?>