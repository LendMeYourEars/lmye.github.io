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
	require_once $twatch->path( 'lib/EntityV.php' );
	loadConfig();
	
	requirePermission( TwatchUserData::ADMINISTRATE );
	
	$visitorTypes = &$twatch->config->getList( TwatchConfig::VISITOR_TYPES );
	
	if( !isset( $_POST['a'] ) ) throw new TwatchException( 'Action was not sent' );
	

	if( $_POST['a'] == 'add_id' ) {
		
		$visitorTypeId = ArdeParam::int( $_POST, 'i');
		if( !isset( $visitorTypes[ $visitorTypeId ] ) ) throw new TwatchException( 'visitor type '.$visitorTypeId.' not found' );
		$entityId = ArdeParam::int( $_POST, 'ei' );
		if( !$twatch->config->propertyExists( TwatchConfig::ENTITIES, $entityId ) ) throw new TwatchException( 'entity '.$entityId.' not found' );
		$entityVId = ArdeParam::u32( $_POST, 'evi', 1 );
		
		$visitorTypes[ $visitorTypeId ]->addIdentifier( $entityId, $entityVId );
		
		if( !$xhtml ) {
			$entityV = TwatchEntityVGen::makeFinalized( $entityId, $entityVId );
			$entityV->printXml( $p, 'result', new TwatchEntityView( true, false, false, EntityV::STRING_SELECT ) );
			$p->nl();
		}
	
	} elseif( $_POST['a'] == 'remove_id' ) {
		
		$entityId = ArdeParam::int( $_POST, 'ei' );
		if( !$twatch->config->propertyExists( TwatchConfig::ENTITIES, $entityId ) ) throw new TwatchException( 'entity '.$entityId.' not found' );
		$entityVId = ArdeParam::u32( $_POST, 'evi', 1 );
		
		TwatchVisitorType::removeIdentifier( $entityId, $entityVId );
		
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'change' ) {
		
		$visitorType = TwatchVisitorType::fromParams( $_POST, false );
		if( $visitorType->isEquivalent( $visitorTypes[ $visitorType->id ] ) ) throw new TwatchUserError( 'nothing to change' );
		
		$visitorTypes[ $visitorType->id ]->removing();
		$visitorType->adding();
		
		$twatch->config->set( $visitorType, TwatchConfig::VISITOR_TYPES, $visitorType->id );
		
		if( !$xhtml ) $p->pl( '<successful />' );
	
	} elseif( $_POST['a'] == 'restore' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $visitorTypes[ $id ] ) ) throw new TwatchException( 'visitor type '.$id.' not found' );
		$visitorTypes[ $id ]->removing();
		$twatch->config->getDefault( TwatchConfig::VISITOR_TYPES, $id )->adding();
		$twatch->config->restoreDefault( TwatchConfig::VISITOR_TYPES, $id );
		
		if( !$xhtml ) {
			$visitorTypes[ $id ]->printXml( $p, 'result' );
			$p->nl();
		}
		
	} elseif( $_POST['a'] == 'reset' ) {
		
		TwatchVisitorType::fullUninstall();
		TwatchVisitorType::fullInstall( true );
		
		if( !$xhtml ) {
			$p->pl( '<result>', 1 );
			foreach( $visitorTypes as $visitorType ) {
				$visitorType->printXml( $p, 'visitor_type' );
				$p->nl();
			}
			$p->rel();
			$p->pl( '</result>' );
		}
		
	} else {
		throw new TwatchException( 'unknown action '.$_POST['a'] );
	}
	
	$p->end();
?>