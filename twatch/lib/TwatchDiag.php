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
    
	class TwatchDiag {
		public $makeDailyTasks;
		public $dbDiagInfo;
		
		public function load() {
			global $twatch;
			
			$taskM = new TwatchTaskManager();
			$this->makeDailyTasks = $taskM->getTasks( 'TwatchMakeDailyTasks' );
			
			$units = array();
			$units[] = TwatchDbDict::getDbAccessUnitInfo( $twatch->db );
			
			$units[] = TwatchCounter::getDbAccessUnitInfo();
			
			$units[] = TwatchLatest::getDbAccessUnitInfo();
			
			if( $twatch->state->get( TwatchState::PATH_ANALYZER_INSTALLED ) ) {
				$pathAnalyzer = $twatch->config->get( TwatchConfig::PATH_ANALYZER );
				$units[] = $pathAnalyzer->getDbAccessUnitInfo();
			}
			
			
			$this->dbDiagInfo = $twatch->db->getDiagnosticInfo( $units );	 
		}

		public function jsObject() {

			$ts = new ArdeAppender( ', ' );
			foreach( $this->makeDailyTasks as $task ) {
				$ts->append( $task->jsObject() );
			}

			return 'new TwatchDiag( [ '.$ts->s.' ], '.$this->dbDiagInfo->jsObject().' )';
		}
		
	}
?>