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

	require_once dirname(__FILE__).'/ArdeSerializer.php';
	require_once dirname(__FILE__).'/../db/DbArdeDisTaskManager.php';

	class ArdeDisTaskManager {

		const TASK_SPACE = 10;

		var $db;
		var $sub;
		var $now;
		private $dbTasks;

		function __construct( $db, $sub, ArdeTime $now ) {
			$this->db = $db;
			$this->sub = $sub;
			$this->dbTasks = new ArdeDbDisTaskManager( $db );
			$this->now = $now;
		}

		function addTasks( $tasks ) {
			$vs = new ArdeAppender( ',' );

			foreach( $tasks as $k => $v ) {
				if( $tasks[$k]->due <= $this->now->ts ) {
					$tasks[$k]->run();
				} else {
					$v = ArdeSerializer::dataToString( $tasks[$k] );
					$vs->append( '( '.$tasks[$k]->due.", ".$this->db->string( get_class( $tasks[$k] ), 'latin1' ).", ".$this->db->string($v)." )" );
				}
			}
			if( $vs->c ) {
				$this->db->query( 'INSERT INTO', 't', '(due,type,v) VALUES '.$vs->s );
			}
		}

		public function install( $overwrite ) {
			$dbTaskManager = new ArdeDbDisTaskManager( $this->db );
			$dbTaskManager->install( $overwrite );
		}

		public function uninstall() {
			$dbTaskManager = new ArdeDbDisTaskManager( $this->db );
			$dbTaskManager->uninstall();
		}

		function queueTasks( $tasks ) {
			$vs = new ArdeAppender( ',' );
			$todayTasks = array();

			foreach( $tasks as $k => $v ) {
				if( $tasks[$k]->due < $this->now->dayOffset(1)->getDayStart() + 300 ) {
					$todayTasks[] = $tasks[$k];

				} else {
					$v = ArdeSerializer::dataToString( $tasks[$k] );
					$vs->append( '( '.$tasks[$k]->due.", ".$this->db->string( get_class( $tasks[$k] ), 'latin1' ).", ".$this->db->string($v)." )" );
				}
			}

			$this->addTasks( $todayTasks );

			if( $vs->c ) {
				$this->db->query( 'INSERT INTO', 'tq', '(due,type,v) VALUES '.$vs->s );
			}
		}

		function getQueuedTasksDueToday( ArdeTime $dt ) {
			$res = $this->db->query( 'SELECT id,due,v FROM', 'tq',
									'WHERE due>='.$dt->getDayStart().' AND due<'.$dt->dayOffset(1)->getDayStart().
									' ORDER BY due,id' );
			return $this->returnTasks( $res );

		}

		function popTasks( $ts ) {
			$res = $this->db->query( 'SELECT id,due,v FROM','t'
									,"WHERE due<=".( $ts + self::TASK_SPACE )
									." ORDER BY due,id" );
			return $this->returnTasks( $res, $ts );
		}

		function returnTasks( $res, $dt = 0 ) {
			$ids = new ArdeAppender(',');
			$tasks = array();
			while( $r = mysql_fetch_row( $res ) ) {
				if( $dt && ((int)$r[1]) > $dt ) return array();
				$ids->append( $r[0] );
				$tasks[] = array( 'due' => (int)$r[1], 'v' => $r[2] );
			}
			if( !$ids->c ) return array();
			$this->db->query( 'DELETE '.$this->db->table( $dt?'t':'tq' ).' FROM '.$this->db->table( 'du' ).' AS tu, '.$this->db->table( $dt?'t':'tq' )
									.' WHERE tu.i <= '.$ids->c.' AND '.$this->db->table( $dt?'t':'tq' ).'.id = elt( tu.i, '.$ids->s.' )' );
			if( $this->db->affected_rows() != $ids->c ) return array();
			$otasks = array();
			foreach( $tasks as $k => $v ) {
				try {
					$o = ArdeSerializer::stringToData( $tasks[$k]['v'] );
				} catch( ArdeException $e ) {
					ArdeException::reportError( $e );
					continue;
				}
				$o->due = (int)$tasks[$k]['due'];
				$otasks[] = $o;
			}
			return $otasks;
		}

		public function getTasks( $type = null ) {
			return $this->dbTasks->getTasks( $type );
		}

		public function getQueuedTasks( $type = null ) {
			return $this->dbTasks->getQueuedTasks( $type );
		}

		public function getAllTasks( $type = null ) {
			return array_merge( $this->dbTasks->getTasks( $type ), $this->dbTasks->getQueuedTasks( $type ) );
		}

		public function deleteAllTasks( $type ) {
			$this->dbTasks->deleteTasks( $type );
			$this->dbTasks->deleteQueuedTasks( $type );
		}
		public function deleteTask( $taskId, $inQueue ) {
			$this->dbTasks->deleteTask( $taskId, $inQueue );
		}

	}

	abstract class ArdeDisTask implements ArdeSerializable {
		public $due;
		public $taskId;
		public $inQueue = false;

		public function jsParams() {
			$due = new TwatchTime( $this->due );
			return " '".$due->getString( TwatchTime::STRING_FULL )."', ".($this->inQueue?'true':'false').' ';
		}

		public function jsObject() {
			return 'new TwatchTask('.$this->jsParams().')';
		}


		abstract public function run();
	}

	class ArdeDisTaskScheduler {
		const SPACE = 1800;

		var $tasks = array();
		var $start;
		var $end;
		public function __construct( $start, $end ) {
			$this->start = $start;
			$this->end = $end;
		}
		function addTasks( &$tasks ) {
			foreach( $tasks as $k => $v ) {
				$this->tasks[] = &$tasks[$k];
			}
		}

		function scheduleTasks() {
			$start = $this->start + self::SPACE;
			$end = $this->end - self::SPACE;
			$c = count( $this->tasks );
			if( $c == 0 ) return;
			if( $c == 1 ) {
				$this->tasks[0]->due = $end;
				return;
			}
			$this->tasks[$c-1]->due = $end;
			if( $c == 2 ) {
				$this->tasks[0]->due = $start;
				return;
			}
			$space = (int)floor( ($end-$start) / ($c-1) );
			for( $i = 0; $i < $c - 1; $i++ ) {
				$this->tasks[$i]->due = $start + $i * $space;
			}
		}
	}
?>