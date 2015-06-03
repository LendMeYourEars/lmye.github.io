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
    
	$adminConfig = true;
	require_once dirname(__FILE__).'/../../lib/RecGlobalHead.php';
	
	if( !isset( $_POST['a'] )) throw new TwatchException( 'Action was not sent' );
	
	loadConfig();
	
	$pathAnalyzer = &$twatch->config->get( TwatchConfig::PATH_ANALYZER ); 
	$entities = &$twatch->config->getList( TwatchConfig::ENTITIES );
	
	$installed = $twatch->state->get( TwatchState::PATH_ANALYZER_INSTALLED );

	if( $_POST['a'] == 'set_vis' ) {
		require_once $twatch->path( 'lib/AdminGeneral.php' );
		$selectedUser = getSelectedUser( true );
		requireConfigPermission( $selectedUser );
		
		BoolWithDefAction::fromParams( TwatchUserData::VIEW_PATH_ANALYSIS, 0, $_POST )->run( $selectedUser->data );
		
		successful( $p );
		
	} elseif( $_POST['a'] == 'change_sampling' ) {
		requirePermission( TwatchUserData::ADMINISTRATE );
		if( !isset( $_POST[ 'ms' ] ) ) throw new TwatchException( 'max samples per day not specified' );
		$maxSamples = (int)$_POST[ 'ms' ];
		if( $maxSamples <= 0 ) throw new TwatchException( 'invalid max samples per day' ); 
		
		if( !isset( $_POST[ 'pt' ] ) ) throw new TwatchException( 'per task not specified' );
		$perTask = (int)$_POST[ 'pt' ];
		if( $perTask <= 0 ) throw new TwatchException( 'invalid per task' );
		
		if( $perTask == $pathAnalyzer->perTask && $maxSamples == $pathAnalyzer->maxSamples ) {
			throw new TwatchUserError( 'nothing to change' );
		}
		
		$pathAnalyzer->perTask = $perTask;
		$pathAnalyzer->maxSamples = $maxSamples;
		
		$twatch->config->setInternal( TwatchConfig::PATH_ANALYZER );
		
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'change_structure' ) {
		requirePermission( TwatchUserData::ADMINISTRATE );
		throw new TwatchUserError( 'Sorry, this feature is not ready yet.' );
		if( !isset( $_POST[ 'd' ] ) ) throw new TwatchException( 'depth not specified' );
		$depth = (int)$_POST[ 'd' ];
		if( $depth < 0 ) throw new TwatchException( 'invalid depth' );
		if ($depth < 2 ) throw new TwatchUserError( 'depth should be at least 2' );
		if( !isset( $_POST[ 'ds' ] ) ) throw new TwatchException( 'data columns not specified' );
		$dataColumns = array();
		if( !empty( $_POST[ 'ds' ] ) ) {
			$ds = explode( '|', $_POST[ 'ds' ] );
			foreach( $ds as $d ) {
				$entityId = (int)$d;
				if( !isset( $entities[ $entityId ] ) ) throw new TwatchException( 'invalid entity id '.$entityId );
				$dataColumns[] = $entityId;
			}
		}
		if( ardeEquivArrays( $dataColumns, $pathAnalyzer->dataColumns ) && $depth == $pathAnalyzer->depth ) {
			throw new TwatchUserError( 'nothing to change' );
		}
		
		$wasInstalled = $installed;
		if( $installed ) {
			terminate();
		}
		
		$pathAnalyzer->dataColumns = $dataColumns;
		$pathAnalyzer->depth = $depth;
		
		$twatch->config->setInternal( TwatchConfig::PATH_ANALYZER );
		
		if( $wasInstalled ) {
			start();
		}
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'change_cleanup' ) {
		requirePermission( TwatchUserData::ADMINISTRATE );
		if( !isset( $_POST['cc'] ) ) throw new TwatchException( 'Cleanup Cycle not specified' );
		$cleanupCycle = (int)$_POST['cc'];
		if( $cleanupCycle < 0 ) throw new TwatchException( 'invalid cleanup cycle' );
		if( $cleanupCycle < 86400 * 2 ) throw new TwatchUserError( 'cleanup cycle must be at least 2 days' );
		
		if( !isset( $_POST['plf'] ) ) throw new TwatchException( 'Paths Live For not specified' );
		$pathsLiveFor = (int)$_POST['plf'];
		if( $pathsLiveFor < 0 ) throw new TwatchException( 'invalid paths live for' );
		if( $pathsLiveFor == 0 ) throw new TwatchUserError( 'paths should live for at least one cycle' );
		
		if( $cleanupCycle == $pathAnalyzer->cleanupCycle && $pathsLiveFor == $pathAnalyzer->pathsLiveFor ) {
			throw new TwatchUserError( 'nothing to change' );
		}
		
		$pathAnalyzer->cleanupCycle = $cleanupCycle;
		$pathAnalyzer->pathsLiveFor = $pathsLiveFor;
		
		$twatch->config->setInternal( TwatchConfig::PATH_ANALYZER );
		
		if( !$xhtml ) $p->pl( '<successful />' );
	} elseif( $_POST['a'] == 'terminate' ) {
		requirePermission( TwatchUserData::ADMINISTRATE );
		terminate();
		
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'start' ) {
		requirePermission( TwatchUserData::ADMINISTRATE );
		start();
		
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'reset' ) {
		requirePermission( TwatchUserData::ADMINISTRATE );
		terminate();
		start();
		
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'restore' ) {
		requirePermission( TwatchUserData::ADMINISTRATE );
		$wasInstalled = $installed;
		if( $installed ) {
			terminate();
		}
		$twatch->config->restoreDefault( TwatchConfig::PATH_ANALYZER );
		if( $wasInstalled ) {
			start();
		}
		$pathAnalyzer = $twatch->config->get( TwatchConfig::PATH_ANALYZER );
		if( !$xhtml ) {
			$pathAnalyzer->printXml( $p, 'result' );
			$p->nl();
		}
	} elseif( $_POST['a'] == 'get_diag' ) {
		requirePermission( TwatchUserData::ADMINISTRATE );
		require_once dirname(__FILE__).'/../../lib/PathAnalyzerDiag.php';
		$diag = new TwatchPathAnalyzerDiag();
		$diag->load();

		if( !$xhtml ) {
			$diag->printXml( $p, 'result', '' );
			$p->nl();
		}
	} else {
		throw new ArdeException( 'action "'.$_POST['a'].'" not recognized.' );
	}
	
	function terminate() {
		global $twatch, $pathAnalyzer, $installed;
		
		TwatchPathAnalyzer::fullUninstall();
		
		$installed = false;
	}
	
	function start() {
		global $twatch, $pathAnalyzer, $installed;
		
		
		if( $installed ) throw new TwatchUserError( 'system indicates that path analyzer is already installed and is running' );
		

		TwatchPathAnalyzer::fullInstall( true );
		
	}
	
	if( $xhtml ) {
		$p->rel();
		$p->pl( '</body>' );
	}
	
	$p->end();
?>