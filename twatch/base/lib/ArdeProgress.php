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
  
	require_once $ardeBase->path( 'db/DbArdeProgress.php' );
	
	class ArdeProgress {
		
		protected $dbProgress;
		
		public function __construct( ArdeDb $db, $tableName, $channelId = null ) {
			$this->dbProgress = new ArdeDbProgress( $db, $tableName, $channelId );
		}
		
		public function set( $progress ) {
			if( $progress > 1 ) $progress = 1;
			if( $progress < 0 ) $progress = 0;
			$this->dbProgress->set( $progress );
		}
		
		public function start() {
			$this->cleanup();
			$this->set( 0 );
		}
		
		public function get() {
			return $this->dbProgress->get();
		}
		
		public function cleanup() {
			$this->dbProgress->cleanup( 3600 );
		}
		
		public function install( $overwrite ) {
			$this->dbProgress->install( $overwrite );
		}
		
		public function uninstall() {
			$this->dbProgress->uninstall();
		}
	}
?>