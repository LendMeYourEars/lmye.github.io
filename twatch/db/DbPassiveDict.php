<?php
	class TwatchDbPassiveDict {
		/**
		 * @var ArdeDb
		 */
		private $db;

		public $cache;

		public function __construct( ArdeDb $db ) {
			$this->db = $db;
			$this->cache = array();
		}

		public function getId( $dictId, $string ) {
			$string = TwatchDbDict::prepString( $string );
			$key = $dictId.'_'.$string;
			if( isset( $this->cache[ $key ] ) ) {
				return $this->cache[ $key ];
			}
			$res = $this->db->query( 'SELECT id FROM', 'd', 'WHERE did = '.$dictId." AND str = ".$this->db->string( $string ) );
			if( !( $r = $this->db->fetchRow( $res ) ) ) return false;
			return $this->cache[ $key ] = ardeStrToU32( $r[0] );
		}

		public function putString( $dictId, $string, $cache = array(), $ext = '', $id = null, $cts = 0 ) {
			$string = TwatchDbDict::prepString( $string );
			$c1 = isset( $cache[0] )?$cache[0]:'0';
			$c2 = isset( $cache[1] )?$cache[1]:'0';
			if( $id === null ) $idStr = 'NULL';
			else $idStr = ardeU32ToStr( $id );
			$res = $this->db->query( 'INSERT INTO', 'd', '( did, id, str, c1, c2, ext, cts ) VALUES ( '.$dictId.', '.$idStr.", ".$this->db->string( $string ).", ".$c1.", ".$c2.", ".$this->db->string( $ext ).", ".$cts." )" );
			if( $id === null ) {
				$id = $this->db->lastInsertId();
			}
			return $this->cache[ $dictId.'_'.$string ] = $id;
		}

		public function clearCache( $dictId, $pos ) {
			$this->db->query( 'UPDATE', 'd', 'SET c'.$pos.' = 0 WHERE did = '.$dictId );
		}

	}
?>