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

	class ArdeDbDisTaskManager {
		private $db;

		function __construct( ArdeDb $db ) {
			$this->db = $db;
		}

		public function getTasks( $type = null ) {
			return $this->_getTasks( 't', $type );
		}

		public function getQueuedTasks( $type = null ) {
			$res = $this->_getTasks( 'tq', $type );
			foreach( $res as &$task ) {
				$task->inQueue = true;
			}
			return $res;
		}

		public function deleteTask( $taskId, $inQueue ) {
			$tableName = $inQueue?'tq':'t';
			$this->db->query( "DELETE FROM", $tableName, "WHERE id = ".$taskId );
			if( !$this->db->affectedRows() ) throw new TwatchException( 'task with id '.$taskId.' not found' );
		}


		private function _getTasks( $table, $type ) {
			if( $type !== null ) {
				$where = "WHERE type= _latin1'".$type."' ";
			} else {
				$where = '';
			}
			$res = $this->db->query( "SELECT id, due, v FROM", $table, $where."ORDER BY due" );
			$out = array();
			while( $r = $this->db->fetchRow( $res ) ) {
				$task = ArdeSerializer::stringToData( $r[2] );
				$task->taskId = (int) $r[0];
				$task->due = (int) $r[1];
				$out[] = $task;
			}
			return $out;
		}

		public function deleteTasks( $type ) {
			return $this->_deleteTasks( 't', $type );
		}

		public function deleteQueuedTasks( $type ) {
			return $this->_deleteTasks( 'tq', $type );
		}



		private function _deleteTasks( $table, $type ) {
			try {
				$this->db->query( "DELETE FROM", $table, " WHERE type='".$type."'" );
			} catch( ArdeException $e ) {
				if( $e->getCode() != ArdeDb::ERROR_TABLE_NOT_EXIST ) {
					throw $e;
				}
			}
		}

		public function install( $overwrite = true ) {
			$this->db->createTable( '', 't', '( id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, due INT UNSIGNED NOT NULL, type CHAR(64) CHARACTER SET latin1 COLLATE latin1_bin, v TEXT CHARACTER SET '.$this->db->getCharset().' COLLATE '.$this->db->getCollation().' NOT NULL, INDEX( due ) )', $overwrite );
			$this->db->createTable( '', 'tq', '( id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,due INT UNSIGNED NOT NULL, type CHAR(64) CHARACTER SET latin1 COLLATE latin1_bin, v TEXT CHARACTER SET '.$this->db->getCharset().' COLLATE '.$this->db->getCollation().' NOT NULL, INDEX( due ) )', $overwrite );
		}

		public function uninstall() {
			$this->db->dropTableIfExists( '', 't' );
			$this->db->dropTableIfExists( '', 'tq' );
		}

	}
?>