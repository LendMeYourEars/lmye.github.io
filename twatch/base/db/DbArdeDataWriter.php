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

	require_once dirname(__FILE__).'/Db.php';

	abstract class ArdeDbDataWriterJob {

		public $id;
		public $subId;
		public $extraKeyValues;

		public function __construct( $id, $subId, $extraKeyValues ) {
			$this->id = $id;
			$this->subId = $subId;
			$this->extraKeyValues = $extraKeyValues;
		}
	}

	abstract class ArdeDbDataWriterJobDelete extends ArdeDbDataWriterJob {}

	class ArdeDbDataWriterJobDelValue extends ArdeDbDataWriterJobDelete {}

	class ArdeDbDataWriterJobDelPosition extends ArdeDbDataWriterJobDelete {}

	class ArdeDbDataWriterJobSet extends ArdeDbDataWriterJob {}

	class ArdeDbDataWriterJobSetValue extends ArdeDbDataWriterJobSet {

		public $value;

		public function __construct( $id, $subId, $value, $extraKeyValues ) {
			parent::__construct( $id, $subId, $extraKeyValues );
			$this->value = $value;
		}
	}

	class ArdeDbDataWriterJobSetPosition extends ArdeDbDataWriterJobSet {

		public $position;

		public function __construct( $id, $subId, $position, $extraKeyValues ) {
			parent::__construct( $id, $subId, $extraKeyValues );
			$this->position = $position;
		}
	}

	class ArdeDbDataWriterResult {
		public $id;
		public $subId;
		public $value;
		public $position;

		public function __construct( $id, $subId, $value, $position ) {
			$this->id = $id;
			$this->subId = $subId;
			$this->value = $value;
			$this->position = $position;
		}
		
	}

	class ArdeDbDataWriter {

		protected $db;
		protected $sub;
		protected $tableName;
		protected $extraKeys;

		protected $holding = 0;

		protected $setValueJobs = array();
		protected $setPositionJobs = array();
		protected $deleteValueJobs = array();
		protected $deletePositionJobs = array();

		public function __construct( ArdeDb $db, $sub, $tableName, $extraKeys = array() ) {
			$this->db = $db;
			$this->sub = $sub;
			$this->tableName = $tableName;
			$this->extraKeys = $extraKeys;
		}

		public function setValue( $id, $subId, $value, $extraKeyValues = null ) {
			$key = $this->makeJobKey( $id, $subId, $extraKeyValues );
			if( isset( $this->deleteValueJobs[ $key ] ) ) {
				unset( $this->deleteValueJobs[ $key ] );
			}

			$this->setValueJobs[ $key ] = new ArdeDbDataWriterJobSetValue( $id, $subId, $value, $extraKeyValues );

			if( $this->holding == 0 ) $this->rollSetJobs();
		}



		public function setPosition( $id, $subId, $position, $extraKeyValues = null ) {
			$key = $this->makeJobKey( $id, $subId, $extraKeyValues );
			if( isset( $this->deletePositionJobs[ $key ] ) ) unset( $this->deletePositionJobs[ $key ] );
			$this->setPositionJobs[ $key ] = new ArdeDbDataWriterJobSetPosition( $id, $subId, $position, $extraKeyValues );

			if( $this->holding == 0 ) $this->rollSetJobs();
		}

		public function deleteValue( $id, $subId, $extraKeyValues = null ) {
			$key = $this->makeJobKey( $id, $subId, $extraKeyValues );
			if( isset( $this->setValueJobs[ $key ] ) ) unset( $this->setValueJobs[ $key ] );
			$this->deleteValueJobs[ $key ] = new ArdeDbDataWriterJobDelValue( $id, $subId, $extraKeyValues );

			if( $this->holding == 0 ) $this->rollDeleteJobs();
		}

		public function deletePosition( $id, $subId, $extraKeyValues = null ) {
			$key = $this->makeJobKey( $id, $subId, $extraKeyValues );
			if( isset( $this->setPositionJobs[ $key ] ) ) {
				unset( $this->setPositionJobs[ $key ] );
			}
			$this->deletePositionJobs[ $key ] = new ArdeDbDataWriterJobDelPosition( $id, $subId, $extraKeyValues );

			if( $this->holding == 0 ) $this->rollDeleteJobs();
		}


		protected function makeJobKey( $id, $subId, $extraKeyValues = null ) {
			$jobKey = '';
			foreach( $this->extraKeys as $key ) {
				$jobKey .= $extraKeyValues[ $key ].' - ';
			}
			return $jobKey.$id.' - '.$subId;
		}

		public function deleteIdRange( $start, $end ) {
			$this->db->query_sub( $this->sub, 'DELETE FROM', $this->tableName, 'WHERE id >= '.$start.' AND id <= '.$end );
		}

		public function deleteSubIdRange( $start, $end ) {
			$this->db->query_sub( $this->sub, 'DELETE FROM', $this->tableName, 'WHERE subid >= '.$start.' AND subid <= '.$end );
		}

		public function copyData( $fromKeyValues, $toKeyValues ) {
			$selectItems = new ArdeAppender( ', ' );
			$where = new ArdeAppender( ' AND ' );
			foreach( $this->extraKeys as $key ) {
				if( isset( $toKeyValues[ $key ] ) ) {
					$selectItems->append( $toKeyValues[ $key ] );
				} else {
					$selectItems->append( $key );
				}
			}
			foreach( $fromKeyValues as $key => $value ) {
				$where->append( $key.' = '.$value );
			}
			$selectItems->append( 'id' );
			$selectItems->append( 'subid' );
			$selectItems->append( 'type' );
			$selectItems->append( 'v' );
			$selectItems->append( 'pos' );
			$q = 'INSERT IGNORE INTO '.$this->db->table( $this->tableName ).' SELECT '.$selectItems->s.' FROM '.$this->db->table( $this->tableName );
			$q .= ' WHERE '.$where->s;
			$this->db->query( $q );
		}
		
		public function clearData( $keyValues ) {
			$where = new ArdeAppender( ' AND ' );
			foreach( $keyValues as $key => $value ) {
				$where->append( $key.' = '.$value );
			}
			$this->db->query( 'DELETE FROM', $this->tableName, 'WHERE '.$where->s );
		}
		
		public function get( $ids = null, $extraKeyValues = null ) {

			$res = $this->db->query( $this->getQuery( 0, $ids, $extraKeyValues ) );
			
			$out = $this->getResult( $res, array( 0 => true ) );
			return $out[0];
		}
		
		protected $queuedGets = array();
		
		public function queueGet( $key, $ids = null, $extraKeyValues = null ) {
			$this->queuedGets[ $key ] = array( 'ids' => $ids, 'extraKeyValues' => $extraKeyValues );
		}
		
		public function rollGets() {
			if( !count( $this->queuedGets ) ) return array(); 
			
			$unions = new ArdeAppender( ' UNION ALL ' );
			foreach( $this->queuedGets as $key => $get ) {
				$unions->append( '( '.$this->getQuery( $key, $get[ 'ids' ], $get[ 'extraKeyValues' ] ).' )' );
			}
			
			
			
			$res = $this->db->query( $unions->s );
				
			$out = $this->getResult( $res, $this->queuedGets );
			
			$this->queuedGets = array();
			
			
			
			return $out;
		}
		
		private function getResult( $res, $queueKeys ) {
			$out = array();
			foreach( $queueKeys as $queueKey => $notImportant ) {
				$out[ $queueKey ] = array();
			}
			
			while( $r = $this->db->fetchRow( $res ) ) {
				$queueKey = (int)$r[0];
				$key = $r[1].' - '.$r[2];
				if( isset( $out[ $queueKey ][ $key ] ) ) {
					if( $r[3] == 'v' ) $out[ $queueKey ][ $key ]->value = $r[4];
					else $out[ $queueKey ][ $key ]->position = (int)$r[5];
				} else {
					$out[ $queueKey ][ $key ] = new ArdeDbDataWriterResult( (int)$r[1], (int)$r[2], $r[3] == 'v' ? $r[4] : null, $r[3] == 'pos' ? (int)$r[5] : null );
				}
			}

			return $out;
		}
		
		private function getQuery( $multiId, $ids, $extraKeyValues ) {
			$where = new ArdeAppender( ' AND ' );
			foreach( $this->extraKeys as $key ) {
				$where->append( $key.' = '.$extraKeyValues[ $key ] );

			}
			if( $ids !== null ) {
				$idsA = new ArdeAppender( ', ' );
				foreach( $ids as $id ) $idsA->append( $id );
				$where->append( 'id IN( '.$idsA->s.' )' );
			}
			
			return $this->db->make_query_sub( $this->sub, 'SELECT '.$multiId.', id, subid, type, '.$this->db->stringResult( 'v' ).', pos FROM', $this->tableName, ($where->c?'WHERE '.$where->s:'') );
		}
		
		
		

		public function hold() {
			++$this->holding;
		}

		public function flush() {
			--$this->holding;
			if( $this->holding == 0 ) $this->rollJobs();
		}

		public function clearId( $id, $subId = null ) {
			$this->db->query_sub( $this->sub, 'DELETE FROM', $this->tableName, 'WHERE id = '.$id.( $subId==null ? '' : ' AND subid = '.$subId ) );
		}
		
		protected function rollJobs() {
			if( count( $this->deleteValueJobs ) + count( $this->deletePositionJobs ) ) $this->rollDeleteJobs();
			if( count( $this->setValueJobs ) + count( $this->setPositionJobs ) ) $this->rollSetJobs();
		}

		protected function rollSetJobs() {

			$fields = new ArdeAppender( ', ' );
			foreach( $this->extraKeys as $key ) $fields->append( $key );
			$fields->append( 'id, subid, type, v, pos' );

			$values = new ArdeAppender( ', ' );

			foreach( $this->setValueJobs as $job ) {
				$jobValues = new ArdeAppender( ', ' );
				foreach( $this->extraKeys as $key ) {
					$jobValues->append( $job->extraKeyValues[ $key ] );
				}
				$jobValues->append( $job->id.', '.$job->subId.", 'v', ".$this->db->string( $job->value ).", NULL" );
				$values->append( '( '.$jobValues->s.' )' );
			}

			foreach( $this->setPositionJobs as $job ) {
				$jobValues = new ArdeAppender( ', ' );
				foreach( $this->extraKeys as $key ) {
					$jobValues->append( $job->extraKeyValues[ $key ] );
				}
				$jobValues->append( $job->id.', '.$job->subId.", 'pos', NULL, ".$job->position );
				$values->append( '( '.$jobValues->s.' )' );
			}

			$this->setValueJobs = array();
			$this->setPositionJobs = array();

			$this->db->query_sub( $this->sub, 'INSERT INTO', $this->tableName, '('.$fields->s.') VALUES '.$values->s.' ON DUPLICATE KEY UPDATE v = VALUES( v ), pos = VALUES( pos )' );

		}

		protected function rollDeleteJobs() {

			$t = $this->db->tableName( $this->tableName, $this->sub );
			
			$keys = array();
			foreach( $this->extraKeys as $key ) {
				$keys[ $key ] = new ArdeAppender( ', ' );
			}

			$keys[ 'id' ] = new ArdeAppender( ', ' );
			$keys[ 'subid' ] = new ArdeAppender( ', ' );
			$keys[ 'type' ]= new ArdeAppender( ', ' );

			foreach( $this->deleteValueJobs as $job ) {
				$keys[ 'id' ]->append( $job->id );
				$keys[ 'subid' ]->append( $job->subId );
				$keys[ 'type' ]->append( "'v'" );
				foreach( $this->extraKeys as $key ) {
					$keys[ $key ]->append( $job->extraKeyValues[ $key ] );
				}
			}

			foreach( $this->deletePositionJobs as $job ) {
				$keys[ 'id' ]->append( $job->id );
				$keys[ 'subid' ]->append( $job->subId );
				$keys[ 'type' ]->append( "'pos'" );
				foreach( $this->extraKeys as $key ) {
					$keys[ $key ]->append( $job->extraKeyValues[ $key ] );
				}
			}

			$this->deleteJobs = array();

			$where = new ArdeAppender( ' AND ' );
			foreach( $keys as $key => $values ) {
				$where->append( $t.'.'.$key.' = ELT( d.i, '.$values->s.' )' );
			}

			$this->db->query( 'DELETE '.$t.' FROM ( SELECT i from '.$this->db->tableName( 'du' ).' ORDER BY i LIMIT '.$keys[ 'id' ]->c.' ) AS d, '.$t.
								' WHERE '.$where->s );

		}


		public function install( $overwrite = false ) {

			$keyColumns = new ArdeAppender( ', ' );
			$columnDefs = new ArdeAppender( ', ' );

			foreach( $this->extraKeys as $key ) {
				$columnDefs->append( $key.' INT UNSIGNED NOT NULL' );
				$keyColumns->append( $key );
			}

			$columnDefs->append( 'id INT UNSIGNED NOT NULL' );
			$columnDefs->append( 'subid INT UNSIGNED NOT NULL' );
			$columnDefs->append( "type ENUM( 'v', 'pos' )" );
			$keyColumns->append( 'id' );
			$keyColumns->append( 'subid' );
			$keyColumns->append( 'type' );

			$this->db->createTable( $this->sub, $this->tableName, '( '.$columnDefs->s.', v TEXT CHARACTER SET '.$this->db->getCharset().' COLLATE '.$this->db->getCollation().', pos INT UNSIGNED, UNIQUE( '.$keyColumns->s.' ) )', $overwrite );

		}

		public function uninstall() {
			$this->db->dropTableIfExists( $this->sub, $this->tableName );
		}

	}


?>