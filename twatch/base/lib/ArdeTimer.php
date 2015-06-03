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

	class ArdeTimer {
		public static $global;
		public $items = array();

		public function start( $id, $str = null ) {
			if( !isset( $this->items[ $id ] ) ) $this->items[ $id ] = new ArdeTimerItem( $str );
			$this->items[ $id ]->start();
		}

		public function stop( $id ) {
			$this->items[ $id ]->stop();
		}

		public function sortByTotalDuration() {
			uasort( $this->items, array( 'ArdeTimer', 'totalDurationCmp' ) );
		}

		public static function totalDurationCmp( $item1, $item2 ) {
		    if ($item1->totalDuration == $item2->totalDuration ) {
		        return 0;
		    }
    		return ( $item1->totalDuration > $item2->totalDuration ) ? -1 : 1;
		}
	}

	class ArdeTimerItem {
		public $totalDuration = 0;
		public $hits = 0;
		public $str = null;

		private $start = null;

		public function __construct( $str ) {
			$this->str = $str;
		}

		public function start() {
			$this->start = ArdeTime::getMicrotime();
		}

		public function stop() {
			if( $this->start === null ) throw new ArdeException( 'end called on a timer item that was not started' );
			$this->totalDuration += ArdeTime::getMicrotime() - $this->start;
			unset( $this->start );
			++$this->hits;
		}


	}

	function ardeTimerMakeGlobal() {
		ArdeTimer::$global = new ArdeTimer();
	}

	function ardeTimerStart( $id, $str = null ) {
		if( !isset( ArdeTimer::$global ) ) return;
		ArdeTimer::$global->start( $id, $str );
	}

	function ardeTimerStop( $id ) {
		if( !isset( ArdeTimer::$global ) ) return;
		ArdeTimer::$global->stop( $id );
	}
?>