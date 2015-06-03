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
    
	require_once dirname(__FILE__).'/../lib/RecGlobalHead.php';
	require_once $twatch->path( 'lib/EntityV.php' );
	require_once $twatch->path( 'lib/EntityPassiveGene.php' );
	
	if( !isset( $_POST['a'] )) throw new TwatchException( 'Action was not sent' );
	
	
	loadConfig();
	
	requirePermission( TwatchUserData::VIEW_REPORTS );
	
	$entities = &$twatch->config->getList( TwatchConfig::ENTITIES );
	
	if( $_POST['a'] == 'get_values' ) {
		
		if( !isset( $_POST['i'] ) ) throw new TwatchException( 'entity id not specified' );
		$entityId = (int)$_POST['i']; 
		if( !isset( $entities[ $entityId ] ) || !$entities[ $entityId ]->isViewable( $ardeUser->user ) ) throw new TwatchUserError( 'invalid entity id '.$entityId );
		
		$entity = $entities[ $entityId ];
		
		if( !isset( $_POST['c'] ) ) throw new TwatchException( 'count not specified' );
		$count = (int)$_POST['c'];
		if( $count <= 0 ) throw new TwatchException( 'invalid count' );
		
		if( !isset( $_POST['o'] ) ) throw new TwatchException( 'offset not specified' );
		$offset = (int)$_POST['o'];
		if( $offset < 0 ) throw new TwatchException( 'invalid offset' );
		
		if( !isset( $_POST['b'] ) ) {
			$beginWith = null;
		} else {
			$beginWith = $_POST[ 'b' ];
		}
		
		if( !isset( $_POST['w'] ) ) {
			$websiteId = null;
		} else {
			$websiteId = (int)$_POST['w'];
			if( $websiteId ) {
				if( !$twatch->config->propertyExists( TwatchConfig::WEBSITES, $websiteId ) ) throw new TwatchUserError( 'invalid website '.$websiteId );
				if( !$ardeUser->user->hasPermission( TwatchUserData::VIEW_WEBSITE, $websiteId ) ) throw new TwatchUserError( 'invalid website '.$websiteId );
			} else {
				$websiteId = null;
			}
		}
		
		
		$ids = $entity->getValueIds( $offset, $count + 1, $beginWith, $websiteId );
		if( count( $ids ) > $count ) {
			$more = true;
			array_pop( $ids );
		} else {
			$more = false;
		}
		
		$entVGen = new TwatchEntityVGen();
		
		$entityVs = array();
		foreach( $ids as $id ) {
			$entityVs[] = $entVGen->make( $entityId, $id );
		}
		
		$entVGen->finalizeEntityVs();
		
		if( !$xhtml ) {
			
			$p->pl( '<result more="'.($more?'true':'false').'" >', 1 );
			foreach( $entityVs as $entityV ) {
				$entityV->printXml( $p, 'entity_v', new TwatchEntityView( true, false, false, EntityV::STRING_SELECT ) );
				$p->nl();
			}
			$p->rel();
			$p->pl( '</result>' );
			
		} else {
		}

	} elseif( $_POST['a'] == 'add' ) {
		if( !$ardeUser->user->hasPermission( TwatchUserData::ADMINISTRATE ) ) throw new ArdeUserError( 'Permission Denied' );
		$entityId = ArdeParam::int( $_POST, 'ei' );
		if( !$twatch->config->propertyExists( TwatchConfig::ENTITIES, $entityId ) ) throw new ArdeException( 'entity '.$entityId.' does not exist' );
		$entity = $twatch->config->get( TwatchConfig::ENTITIES, $entityId );
		if( !$entity->gene->allowExplicitAdd() ) throw new ArdeException( 'you can not explicitly add values to "'.$entity->name.'" entity' );
		$str = ArdeParam::str( $_POST, 's' );
		
		$gene = $entity->gene->getPassiveGene( new TwatchDbPassiveDict( $twatch->db ), TwatchEntityPassiveGene::MODE_ADD_ONLY, TwatchEntityPassiveGene::CONTEXT_EXPLICIT_ADD );
		
		$entityVId = $gene->getStringEntityVId( $str );

		$entityV = TwatchEntityVGen::makeFinalized( $entityId, $entityVId );
		if( !$xhtml ) {
			$entityV->printXml( $p, 'result', new TwatchEntityView( true, false, false, EntityV::STRING_SELECT ) );
		}
		
	} else {
		throw new TwatchException( 'action "'.$_POST['a'].'" not recognized' );
	}
	
	if( $xhtml ) {
		$p->rel();
		$p->pl( '</body>' );
	}
	
	$p->end();
?>