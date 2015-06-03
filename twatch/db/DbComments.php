<?php
	class TwatchDbComments {
		protected $db;

		public function __construct( ArdeDb $db ) {
			$this->db = $db;
		}

		public function add( $dt, $txt, $visibility ) {
			$this->db->query( 'INSERT INTO', 'cm', "VALUES ( NULL, ".$this->db->string( $dt, 'latin1' ).", ".$this->db->string( $txt ).", ".$visibility." )" );
			return $this->db->lastInsertId();
		}

		public function remove( $id ) {
			$this->db->query( 'DELETE FROM', 'cm', 'WHERE id='.$id );
		}

		public function getAll() {
			$res = $this->db->query( 'SELECT id, dt, '.$this->db->stringResult( 'txt' ).', vis FROM', 'cm', 'ORDER BY id DESC' );
			return $this->returnDbComments( $res );
		}

		protected function returnDbComments( $res ) {
			$o = array();
			while( $r = $this->db->fetchRow( $res ) ) {
				$o[] = array(
					 'id' => (int)$r[0]
					,'dt' => $r[1]
					,'txt' => $r[2]
					,'visibility' => (int)$r[3]
				);
			}
			return $o;
		}

		public function get( $minDt, $maxDt, $maxVisibility ) {
			$res = $this->db->query( 'SELECT id, dt, '.$this->db->stringResult( 'txt' ).', vis FROM', 'cm', "WHERE dt >= ".$this->db->string( $minDt, 'latin1' )." AND dt <= ".$this->db->string( $maxDt, 'latin1' )." AND vis <= ".$maxVisibility." ORDER BY id DESC" );
			return $this->returnDbComments( $res );
		}

		public function install( $overwrite = false ) {
			$this->db->createTable( '', 'cm', "( id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, dt CHAR(6) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL, txt TEXT CHARACTER SET ".$this->db->getCharset().' COLLATE '.$this->db->getCollation().", vis TINYINT NOT NULL DEFAULT 0 )", $overwrite );
		}

		public function uninstall() {
			$this->db->dropTableIfExists( '', 'cm' );
		}
	}
?>