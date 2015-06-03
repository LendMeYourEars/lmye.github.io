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
	
	$searchEngines = &$twatch->config->getList( TwatchConfig::SEARCH_ENGINES );
	
	if( !isset( $_POST['a'] ) ) throw new TwatchException( 'Action was not sent' );
	

	if( $_POST['a'] == 'add' ) {
		
		$searchEngine = TwatchSearchEngine::fromParams( $_POST, true );
		$twatch->config->addToBottom( $searchEngine, TwatchConfig::SEARCH_ENGINES, $searchEngine->id );
		TwatchSearchEngine::invalidateCache();
		if( !$xhtml ) {
			$searchEngine->printXml( $p, 'result' );
			$p->nl();
		}
	
	} elseif( $_POST['a'] == 'change' ) {
		
		$searchEngine = TwatchSearchEngine::fromParams( $_POST, false );
		if( $searchEngine->isEquivalent( $searchEngines[ $searchEngine->id ] ) ) throw new TwatchUserError( 'nothing to change' );
		$invalidCache = $searchEngine->pattern != $searchEngines[ $searchEngine->id ]; 
		$twatch->config->set( $searchEngine, TwatchConfig::SEARCH_ENGINES, $searchEngine->id );
		if( $invalidCache ) TwatchSearchEngine::invalidateCache();
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'restore' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $searchEngines[ $id ] ) ) throw new TwatchException( 'searchEngine '.$id.' not found' );
		if( !$twatch->config->hasDefault( TwatchConfig::SEARCH_ENGINES, $id ) ) throw new TwatchException( 'search engine '.$id.' does not have a default' );
		$invalidCache = $searchEngines[ $id ]->pattern != $twatch->config->getDefault( TwatchConfig::SEARCH_ENGINES, $id )->pattern;
		$twatch->config->restoreDefault( TwatchConfig::SEARCH_ENGINES, $id );
		if( $invalidCache ) TwatchSearchEngine::invalidateCache();
		if( !$xhtml ) {
			$searchEngines[ $id ]->printXml( $p, 'result' );
			$p->nl();
		}
		
	} elseif( $_POST['a'] == 'delete' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $searchEngines[ $id ] ) ) throw new TwatchException( 'searchEngine '.$id.' not found' );
		$twatch->config->remove( TwatchConfig::SEARCH_ENGINES, $id );
		TwatchSearchEngine::invalidateCache();
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'up' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $searchEngines[ $id ] ) ) throw new TwatchException( 'searchEngine '.$id.' not found' );
		$twatch->config->moveUp( TwatchConfig::SEARCH_ENGINES, $id ); 
		TwatchSearchEngine::invalidateCache();
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'down' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $searchEngines[ $id ] ) ) throw new TwatchException( 'searchEngine '.$id.' not found' );
		$twatch->config->moveDown( Twatchconfig::SEARCH_ENGINES, $id );
		TwatchSearchEngine::invalidateCache();
		if( !$xhtml ) $p->pl( '<successful />' );

	} elseif( $_POST['a'] == 'restore_deleted' ) {
		
		$delDefs = $twatch->config->getDeletedDefaults( TwatchConfig::SEARCH_ENGINES );
		$twatch->config->restoreDeletedDefaults( TwatchConfig::SEARCH_ENGINES, TwatchConfig::RESTORE_POS_INSERT );

		if( count( $delDefs ) ) TwatchSearchEngine::invalidateCache();
		if( !$xhtml ) {
			$p->pl( '<result>', 1 );
			foreach( $delDefs as $id => $pos ) {
				$searchEngines[ $id ]->printXml( $p, 'search_engine', ' pos="'.$pos.'" ' );
				$p->nl();		
			}
			$p->rel();
			$p->pl( '</result>' );
		}
	} elseif( $_POST['a'] == 'test' ) {
		$str = ArdeParam::str( $_POST, 's' );
		$res = TwatchSearchEngine::match( $str );
		if( $res === false ) $p->pl( "<result>doesn't match any search engine</result>" );
		else {
			if( $res instanceof TwatchSEKeyword ) {
				$p->pl( "<result>matches ".ardeXmlEntities( $twatch->config->get( TwatchConfig::SEARCH_ENGINES, $res->searchEngineId )->name )." Search Engine, keyword: [".ardeXmlEntities($res->keyword)."]</result>" );
			} else {
				$p->pl( "<result>matches ".ardeXmlEntities( $twatch->config->get( TwatchConfig::SEARCH_ENGINES, $res )->name )." Web Area</result>" );
			}
		}
		
	} else {
		throw new TwatchException( 'unknown action '.$_POST['a'] );
	}
	
	$p->end();
?>