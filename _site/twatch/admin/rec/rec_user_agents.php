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
	
	loadConfig();
	
	requirePermission( TwatchUserData::ADMINISTRATE );
	
	$userAgents = &$twatch->config->getList( TwatchConfig::USER_AGENTS );
	
	if( !isset( $_POST['a'] ) ) throw new TwatchException( 'Action was not sent' );
	

	if( $_POST['a'] == 'add' ) {
		
		$userAgent = TwatchUserAgent::fromParams( $_POST, true );
		$twatch->config->addToBottom( $userAgent, TwatchConfig::USER_AGENTS, $userAgent->id );
		TwatchUserAgent::invalidateCache();
		if( !$xhtml ) {
			$userAgent->printXml( $p, 'result' );
			$p->nl();
		}
	
	} elseif( $_POST['a'] == 'change' ) {
		
		$userAgent = TwatchUserAgent::fromParams( $_POST, false );
		if( $userAgent->isEquivalent( $userAgents[ $userAgent->id ] ) ) throw new TwatchUserError( 'nothing to change' );
		$invalidCache = $userAgent->pattern != $userAgents[ $userAgent->id ]; 
		$twatch->config->set( $userAgent, TwatchConfig::USER_AGENTS, $userAgent->id );
		if( $invalidCache ) TwatchUserAgent::invalidateCache();
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'restore' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $userAgents[ $id ] ) ) throw new TwatchException( 'user agent '.$id.' not found' );
		if( !$twatch->config->hasDefault( TwatchConfig::USER_AGENTS, $id ) ) throw new TwatchException( 'user agent '.$id.' does not have a default' );
		$invalidCache = $userAgents[ $id ]->pattern != $twatch->config->getDefault( TwatchConfig::USER_AGENTS, $id )->pattern;
		$twatch->config->restoreDefault( TwatchConfig::USER_AGENTS, $id );
		if( $invalidCache ) TwatchUserAgent::invalidateCache();
		if( !$xhtml ) {
			$userAgents[ $id ]->printXml( $p, 'result' );
			$p->nl();
		}
		
	} elseif( $_POST['a'] == 'delete' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( $id == TwatchUserAgent::UNKNOWN ) throw new TwatchException( 'you can\'t delete the \'unknown\' user agent' );
		if( !isset( $userAgents[ $id ] ) ) throw new TwatchException( 'user agent '.$id.' not found' );
		$twatch->config->remove( TwatchConfig::USER_AGENTS, $id );
		TwatchUserAgent::invalidateCache();
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'up' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( $id == TwatchUserAgent::UNKNOWN ) throw new TwatchException( 'you can\'t move the \'unknown\' user agent' );
		if( !isset( $userAgents[ $id ] ) ) throw new TwatchException( 'user agent '.$id.' not found' );
		reset( $userAgents );
		next( $userAgents );
		if( key( $userAgents ) == $id ) throw new TwatchException( "you can't move a user agent lower that the unknown user agent" );
		$twatch->config->moveUp( TwatchConfig::USER_AGENTS, $id ); 
		TwatchUserAgent::invalidateCache();
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'down' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( $id == TwatchUserAgent::UNKNOWN ) throw new TwatchException( 'you can\'t move the \'unknown\' user agent' );
		if( !isset( $userAgents[ $id ] ) ) throw new TwatchException( 'user agent '.$id.' not found' );
		$twatch->config->moveDown( TwatchConfig::USER_AGENTS, $id );
		TwatchUserAgent::invalidateCache();
		if( !$xhtml ) $p->pl( '<successful />' );

	} elseif( $_POST['a'] == 'restore_deleted' ) {
		
		$delDefs = $twatch->config->getDeletedDefaults( TwatchConfig::USER_AGENTS );
		$twatch->config->restoreDeletedDefaults( TwatchConfig::USER_AGENTS, TwatchConfig::RESTORE_POS_INSERT );

		if( count( $delDefs ) ) TwatchUserAgent::invalidateCache();
		if( !$xhtml ) {
			$p->pl( '<result>', 1 );
			foreach( $delDefs as $id => $pos ) {
				$userAgents[ $id ]->printXml( $p, 'user_agent', ' pos="'.$pos.'" ' );
				$p->nl();		
			}
			$p->rel();
			$p->pl( '</result>' );
		}
	} elseif( $_POST['a'] == 'test' ) {
		$str = ArdeParam::str( $_POST, 's' );
		$userAgentId = TwatchUserAgent::match( $str );
		$p->pl( "<result>matches ".ardeXmlEntities($twatch->config->get( TwatchConfig::USER_AGENTS, $userAgentId )->name)." User Agent</result>" );
		
	} else {
		throw new TwatchException( 'unknown action '.$_POST['a'] );
	}
	
	$p->end();
?>