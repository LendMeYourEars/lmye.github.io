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
	require_once dirname(__FILE__).'/../../data/DataStatsPage.php';
	
	
	loadConfig( array( StatsPage::$defaults ) );
	requirePermission( TwatchUserData::ADMINISTRATE );
	
	
	$counters = &$twatch->config->getList( TwatchConfig::COUNTERS );
	

	if( !isset( $_POST['a'] ) ) throw new TwatchException( 'Action was not sent' );
	
	
	
	function counterFromParams( $new ) {
		global $config, $counters;
		if( !$new ) {
			$id = ArdeParam::int( $_POST, 'i' );
			if( !isset( $counters[ $id ] ) ) throw new TwatchException( 'Counter with id '.$id.' not found' );
		} else {
			$id = null;
		}

		$type = ArdeParam::int( $_POST, 't' );
		if( !isset( TwatchCounter::$typeStrings[ $type ] ) ) throw new TwatchException( 'invalid counter type '.$type );
		
		
		$name = ArdeParam::str( $_POST, 'n' );
		
		$periodTypes = ArdeParam::intArr( $_POST, 'pt', ' ' );
		if( !count( $periodTypes ) ) throw new TwatchUserError( 'there should be at least one period type' );
		
		foreach( $periodTypes as $periodType ) {
			if( !isset( TwatchPeriod::$typeStrings[ $periodType ] ) ) throw new TwatchException( 'invalid period type '.$type );
		}
		
		$delete = ArdeParam::assocIntInt( $_POST, 'd', ' ' );
		foreach( $delete as $periodType => $num ) {
			if( $periodType == TwatchPeriod::ALL ) throw new TwatchException( "You can't auto delete ALL period" );
			if( !in_array( $periodType, $periodTypes ) ) throw new TwatchException( 'Period Type '.$periodType.' specified for delete not used' );
			if( $num <= 0 ) throw new TwatchException( 'Invalid delete age' );
		}
		
		$when = TwatchExpression::fromParam( ArdeParam::str( $_POST, 'w' ) );
		
		$res = $when->isValid();

		if( $res !== true ) {
			$e = new TwatchUserError( '"when" has Syntax Error' );
			$e->safeExtras[] = $res;
			throw $e;
		}
			
		if( $type == TwatchCounter::TYPE_SINGLE ) { 
			return new TwatchSingleCounter( $id, $name, $periodTypes, $when->a, $delete );
		}
		
		$ts = ArdeParam::assocIntStr( $_POST, 'tr', ' ' );
		$trim = array();
		foreach( $ts as $periodType => $t ) {
			if( $periodType == TwatchPeriod::ALL ) throw new TwatchException( "You can't trim ALL period" );
			if( !in_array( $periodType, $periodTypes ) ) throw new TwatchException( 'Period Type '.$periodType.' specified for trim not used' );
			$tes = explode( '_', $t );
			if( count( $tes ) != 2 ) throw new TwatchException( 'invalid trim value '.$t );
			$trim[ $periodType ] = array( (int)$tes[0],(int)$tes[1] );
		}
		
		$ats = ArdeParam::assocIntStr( $_POST, 'atr', ' ' );
		$activeTrim = array();
		foreach( $ats as $periodType => $t ) {
			if( !in_array( $periodType, $periodTypes ) ) throw new TwatchException( 'Period Type '.$periodType.' specified for trim not used' );
			$tes = explode( '_', $t );
			if( count( $tes ) != 2 ) throw new TwatchException( 'invalid trim value '.$t );
			$activeTrim[ $periodType ] = array( (int)$tes[0],(int)$tes[1] );
		}
		
		if( $new ) {
			$entityId = ArdeParam::int( $_POST, 'ei' );
		} else {
			$entityId = $counters[ $id ]->entityId;
		}
		
		if( $type == TwatchCounter::TYPE_LIST ) {
			return new TwatchListCounter( $id, $name, $periodTypes, $when->a, $delete, $trim, $entityId, $activeTrim );
		}
		
		if( $new ) {
			$groupEntityId = ArdeParam::int( $_POST, 'gi' );
		} else {
			$groupEntityId = $counters[ $id ]->groupEntityId;
		}
		
		
		return new TwatchGroupedCounter( $id, $name, $periodTypes, $when->a, $delete, $trim, $entityId, $groupEntityId, $activeTrim );
	}
	
	if( $_POST['a'] == 'set_vis' ) {
		
		$id = ArdeParam::int( $_POST, 'i', 0 );
		if( !$twatch->config->propertyExists( TwatchConfig::COUNTERS, $id ) ) throw new TwatchUserError( 'counter with id '.$id.' not found' );
		require_once $twatch->path( 'lib/AdminGeneral.php' );
		$selectedUser = getSelectedUser( false );
		if( $selectedUser->isRoot() ) throw new TwatchUserError( 'can\'t change counter visibility for root user.' );
		BoolWithDefAction::fromParams( TwatchUserData::VIEW_COUNTER, $id, $_POST )->run( $selectedUser->data );
		
		successful( $p );
		
	} elseif( $_POST['a'] == 'add' ) {
		
		$counter = counterFromParams( true );
		$counter->id = $twatch->config->getNewSubId( TwatchConfig::COUNTERS );
		$counter->adding();
		$twatch->config->addToTop( $counter, TwatchConfig::COUNTERS, $counter->id );
		$counter->install();
		
		if( !$xhtml ) {
			$counter->printXml( $p, 'result' );
			$p->nl();
		}
		
	} elseif( $_POST['a'] == 'change' ) {
		
		$counter = counterFromParams( false );
		if( $counters[ $counter->id ]->isEquivalent( $counter ) ) throw new TwatchUserError( 'nothing to change' );
		$counters[ $counter->id ]->removing();
		$counter->adding();
		$counters[ $counter->id ]->update( $counter );
		$twatch->config->setInternal( TwatchConfig::COUNTERS, $counter->id );
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'restore' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $counters[ $id ] ) ) throw new TwatchException( 'counter with id '.$counter->id.' not found' );
		$defCounter = $twatch->config->getDefault( TwatchConfig::COUNTERS, $id );
		if( $counters[ $id ]->isEquivalent( $defCounter ) ) throw new TwatchUserError( 'already using defaults' );
		$counters[ $id ]->removing();
		$defCounter->adding();
		$twatch->config->restoreDefault( TwatchConfig::COUNTERS, $id );
		if( !$xhtml ) {
			$counters[ $id ]->printXml( $p, 'result' );
			$p->nl();
		}
		
	} elseif( $_POST['a'] == 'restore_deleted' ) {
		
		$delDefs = $twatch->config->getDeletedDefaults( TwatchConfig::COUNTERS );
		$twatch->config->restoreDeletedDefaults( TwatchConfig::COUNTERS, TwatchConfig::RESTORE_POS_INSERT );
		
		foreach( $delDefs as $id => $pos ) {
			$counters[ $id ]->install();
			$counters[ $id ]->adding();
		}
		
		if( !$xhtml ) {
			$p->pl( '<result>', 1 );
			foreach( $delDefs as $id => $pos ) {
				$counters[ $id ]->printXml( $p, 'counter', ' pos="'.$pos.'" ' );
				$p->nl();		
			}
			$p->rel();
			$p->pl( '</result>' );
		}
		
	} elseif( $_POST['a'] == 'delete' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $counters[ $id ] ) ) throw new TwatchException( 'counter with id '.$id.' not found' );
		
		
		$counterUsed = $counters[ $id ]->isUsed();
		
		if( $counterUsed !== false ) throw new TwatchUserError( 'you can\'t delete counter "'.$counters[ $id ]->name.'" because it is used in '.$counterUsed );
		
		$counters[ $id ]->uninstall();
		$counters[ $id ]->removing();
		$twatch->config->remove( TwatchConfig::COUNTERS, $id );
		
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'reset' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $counters[ $id ] ) ) throw new TwatchException( 'counter with id '.$id.' not found' );
		
		try {
			$wasAvailable = $twatch->state->get( TwatchState::COUNTERS_AVAIL, $id )->isAvailable();
		} catch( ArdeException $e ) {
			ArdeException::reportError( $e );
			$wasAvailable = false;
		}
		
		$counters[ $id ]->uninstall();
		$counters[ $id ]->removing();
		$counters[ $id ]->install();
		$counters[ $id ]->adding();
		
		if( $wasAvailable ) $counters[ $id ]->start();
		
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'cleanup' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $counters[ $id ] ) ) throw new TwatchException( 'counter with id '.$id.' not found' );
		$counters[ $id ]->cleanup();
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'stop' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $counters[ $id ] ) ) throw new TwatchException( 'counter with id '.$id.' not found' );
		$counters[ $id ]->stop();
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'start' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $counters[ $id ] ) ) throw new TwatchException( 'counter with id '.$id.' not found' );
		$counters[ $id ]->start();
		if( !$xhtml ) $p->pl( '<successful />' );
	
	} elseif( $_POST['a'] == 'start_all' ) {
		TwatchCounter::startAll();
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'stop_all' ) { 

		foreach( $counters as $counter ) {
			if( $twatch->state->get( TwatchState::COUNTERS_AVAIL, $counter->id )->isAvailable() ) {
				$counter->stop();
			}
		}
		if( !$xhtml ) $p->pl( '<successful />' );
	
	} elseif( $_POST['a'] == 'reset_all' ) {
		$wasAvailable = array();
		foreach( $counters as $counter ) {
			try {
				if( $twatch->state->get( TwatchState::COUNTERS_AVAIL, $counter->id )->isAvailable() ) {
					$wasAvailable[ $counter->id ] = true;
				}
			} catch( ArdeException $e ) {
				ArdeException::reportError( $e );
			}
		}
		
		TwatchCounter::fullUninstall();
		TwatchCounter::fullInstall( true );
		
		foreach( $counters as $counter ) {
			if( isset( $wasAvailable[ $counter->id ] ) ) $counter->start();
		}
		
		if( !$xhtml ) $p->pl( '<successful />' );

	} elseif( $_POST['a'] == 'get_diag' ) {
		
		require_once dirname(__FILE__).'/../../lib/CountersDiag.php';
		$countersDiag = new TwatchCountersDiagInfo();
		$countersDiag->load();
		if( !$xhtml ) {
			$countersDiag->printXml( $p, 'result' );
			$p->nl();
		}

	} elseif( $_POST['a'] == 'get_availability' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $counters[ $id ] ) ) throw new TwatchException( 'counter with id '.$id.' not found' );
		$avail = $twatch->state->get( TwatchState::COUNTERS_AVAIL, $id );
		if( !$xhtml ) {
			$avail->printXml( $p, 'result' );
			$p->nl();
		}
		
	} elseif( $_POST['a'] == 'change_when' ) {
		
		$when = TwatchExpression::fromParam( ArdeParam::str( $_POST, 'w' ) );
		$res = $when->isValid();

		if( $res !== true ) {
			$e = new TwatchUserError( '"when" has Syntax Error' );
			$e->safeExtras[] = $res;
			throw $e;
		}
		if( ardeEquivOrderedArrays( $when->a, $twatch->config->get( TwatchConfig::COUNTERS_WHEN ) ) ) {
			throw new TwatchUserError( 'Nothing to Change' );
		}
		$oldWhen = new TwatchExpression( $twatch->config->get( TwatchConfig::COUNTERS_WHEN ), null );
		$oldWhen->uninstall();
		$when->install();
		$twatch->config->set( $when->a, TwatchConfig::COUNTERS_WHEN );
		
		if( !$xhtml ) {
			$p->pl( '<successful />' );
		}
		
	} else {
		throw new TwatchException( 'unknown action '.$_POST['a'] );
	}

	$p->end();
?>
