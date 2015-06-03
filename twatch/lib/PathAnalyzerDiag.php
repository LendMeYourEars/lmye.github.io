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
    
	class TwatchPathAnalyzerDiag {
		private $installed;
		private $cleanupTasks;
		private $pathsCount;
		private $nextCleanup;
		
		public function load() {
			global $twatch;
			
			$this->installed = $twatch->state->get( TwatchState::PATH_ANALYZER_INSTALLED );
			if( !$this->installed ) return;
			$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
			foreach( $websites as $website ) {
				if( !$website->parent ) {
					$dbPath = new TwatchDbPathAnalyzer( $twatch->db, $website->getSub() );
					$this->pathsCount[ $website->name ] = $dbPath->getPathsCount();		
				}
			}
			$taskM = new TwatchTaskManager();
			
			$this->cleanupTasks = $taskM->getTasks( 'TwatchCleanupPaths' );
			$queuedCleanupTasks = $taskM->getQueuedTasks( 'TwatchCleanupPaths' );
			$this->cleanupTasks = array_merge( $this->cleanupTasks, $queuedCleanupTasks );
			
			$this->nextCleanup = $twatch->state->get( TwatchState::PATH_NEXT_CLEANUP_ROUND );
		}
		
		public function jsObject() {
			if( !$this->installed ) return 'new PathAnalyzerDiag( false )';
			$s = 'new PathAnalyzerDiag( true, ';
			$s .= ' { ';
			$i = 0;
			foreach( $this->pathsCount as $websiteName => $counts ) {
				$s .= ($i?', ':'')."'".ArdeJs::escape( $websiteName )."': { ";
				$j = 0;
				foreach( $counts as $deleteRound => $count ) {
					$s .= ($j?', ':'').$deleteRound.': new PathCount( '.$count->count.', '.$count->unique.' )';
					++$j;
				}
				$s .= " }";
				++$i;
			}
			$s .= ' }, [ ';
			$i = 0;
			foreach( $this->cleanupTasks as $cleanupTask ) {
				$s .= ($i?', ':'').'new PathCleanupTask('.$cleanupTask->jsParams().')';
				++$i;
			}
			$s .= ' ], "'.$this->nextCleanup.'" ';
			$s .= ')';
			return $s;
		}
		
		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			if( !$this->installed ) {
				$p->pn( '<'.$tagName.' installed="false"'.$extraAttrib.' />' );
				return;
			}
			$p->pl( '<'.$tagName.' installed="true" next_cleanup="'.$this->nextCleanup.'"'.$extraAttrib.'>' );
			$p->pl( '	<paths_count>', 1 );
			foreach( $this->pathsCount as $websiteName => $counts ) {
				$p->pl( '<website name="'.$websiteName.'">', 1 );
				foreach( $counts as $deleteRound => $count ) {
					$p->pl( '<round no="'.$deleteRound.'" count="'.$count->count.'" unique="'.$count->unique.'" />' );
				}
				$p->rel();
				$p->pl( '</website>' );
			}
			$p->rel();
			$p->pl( '	</paths_count>' );
			$p->pl( '	<cleanup_tasks>', 1 );
			foreach( $this->cleanupTasks as $cleanupTask ) {
				$cleanupTask->printXml( $p, 'task', '' );
				$p->nl();
			}
			$p->rel();
			$p->pl( '	</cleanup_tasks>' );
			$p->pl( '</'.$tagName.'>' );
		}
	}
?>