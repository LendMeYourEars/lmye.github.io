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
	require_once dirname(__FILE__).'/../../data/DataLatest.php';
	
	loadConfig( array( TwatchLatestPage::$defaults ) );
	
	requirePermission( TwatchUserData::ADMINISTRATE );
	
	$entities = &$twatch->config->getList( TwatchConfig::ENTITIES );
	$dicts = &$twatch->config->getList( TwatchConfig::DICTS );
	
	if( !isset( $_POST['a'] )) throw new TwatchException( 'Action was not sent' );
	

	if( $_POST['a'] == 'add' ) {
		
		$prototypeDicts = array();
		$ds = ArdeParam::intArr( $_POST, 'd', ' ' );
		
		$entityName = ArdeParam::str( $_POST, 'n' );
		foreach( $ds as $d ) {
			$prototypeDicts[] = TwatchDict::fromParams( $_POST, 'd'.$d.'_', true, $entityName );
		}
		
		$entity = TwatchEntity::fromParams( $_POST, true );
		
		$entity->gene->init( $prototypeDicts );
		$twatch->config->addToTop( $entity, TwatchConfig::ENTITIES, $entity->id );
		$entity->gene->install();
		if( !$xhtml ) {
			$entity->printXml( $p, 'result' );
			$p->nl();
		}
		
	} elseif( $_POST['a'] == 'set_vis' ) {
		require_once $twatch->path( 'lib/AdminGeneral.php' );
		$selectedUser = getSelectedUser( false );
		$entityId = ArdeParam::int( $_POST, 'i' );
		if( !$twatch->config->propertyExists( TwatchConfig::ENTITIES , $entityId ) ) {
			throw new TwatchUserError( 'entity with id '.$id.' not found.' );
		}
		IntWithDefAction::fromParams( TwatchUserData::VIEW_ENTITY, $entityId, $_POST, 'v_' )->run( $selectedUser->data );
		if( !$xhtml ) $p->pl( '<successful />' );
	} elseif( $_POST['a'] == 'change' ) {
		
		$somethingChanged = false;
		$ds = ArdeParam::intArr( $_POST, 'd', ' ' );
		foreach( $ds as $d ) {
			$dict = TwatchDict::fromParams( $_POST, 'd'.$d.'_', false );
			if( !$dict->isEquivalent( $dicts[ $dict->id ] ) ) {
				$somethingChanged = true;

				$twatch->config->set( $dict, TwatchConfig::DICTS, $dict->id );
			}
		}
		
		$entity = TwatchEntity::fromParams( $_POST, false );
		if( !$entity->isEquivalent( $entities[ $entity->id ] ) ) {
			$somethingChanged = true;
			$twatch->config->set( $entity, TwatchConfig::ENTITIES, $entity->id );
		}
		if( !$somethingChanged ) throw new TwatchUserError( 'nothing to change' );
		if( !$xhtml ) $p->pl( '<successful />' );
		 
	} elseif( $_POST['a'] == 'restore' ) {
		$id = ArdeParam::int( $_POST, 'i' );
		$twatch->config->restoreDefault( TwatchConfig::ENTITIES, $id );
		
		if( !$xhtml ) {
			
			$entities[ $id ]->printXml( $p, 'result' );
			$p->nl();
		}
		
	} elseif( $_POST['a'] == 'get_vis' ) {
		
		require_once $twatch->path( 'lib/AdminGeneral.php' );
		$selectedUser = getSelectedUser( false );
		if( !$xhtml ) {
			$p->pl( '<visibility value="'.$selectedUser->data->get( TwatchUserData::VIEW_ENTITY, $id ).'"'
				.' is_default="'.( $selectedUser->data->isDefault( TwatchUserData::VIEW_ENTITY, $id )?'true':'false' ).'"'
				.' default="'.$selectedUser->data->getDefault( TwatchUserData::VIEW_ENTITY, $id ).'" />' );
		}
		
	} elseif( $_POST['a'] == 'restore_deleted' ) {
		
		$delDefs = $twatch->config->getDeletedDefaults( TwatchConfig::ENTITIES );
		$twatch->config->restoreDeletedDefaults( TwatchConfig::ENTITIES, TwatchConfig::RESTORE_POS_INSERT );
		
		foreach( $delDefs as $id => $pos ) {
			$entities[ $id ]->gene->install();
		}
		

		if( !$xhtml ) {
			$p->pl( '<result>', 1 );
			
			foreach( $delDefs as $id => $pos ) {
				$entities[ $id ]->printXml( $p, 'entity', ' pos="'.$pos.'" ' );
				$p->nl();		
			}
			$p->rel();
			$p->pl( '</result>' );
		}
		
	} elseif( $_POST['a'] == 'delete' ) {

		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $entities[ $id ] ) ) throw new TwatchException( 'entity '.$id.' does not exist' );
		$entityUsed = $entities[ $id ]->isUsed();
		if( $entityUsed !== false ) throw new TwatchUserError( 'you can\'t delete entity "'.$entities[ $id ]->name.'" because it is used in '.$entityUsed );
		$entities[ $id ]->gene->uninstall();
		$twatch->config->remove( TwatchConfig::ENTITIES, $id );
		
		$ardeUser->user->data->clearId( TwatchUserData::VIEW_ENTITY, $id );
		
		if( !$xhtml ) {
			$p->pl( '<successful />' );
		}
	
	} elseif( $_POST['a'] == 'start' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $entities[ $id ] ) ) throw new TwatchException( 'entity '.$id.' does not exist' );
		$entities[ $id ]->start();
		if( !$xhtml ) $p->pl( '<successful />' );
	
	} elseif( $_POST['a'] == 'stop' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $entities[ $id ] ) ) throw new TwatchException( 'entity '.$id.' does not exist' );
		$entities[ $id ]->stop();
		if( !$xhtml ) $p->pl( '<successful />' );
	
	} elseif( $_POST['a'] == 'get_diag' ) {
		
		require_once dirname(__FILE__).'/../../lib/EntitiesDiag.php';
		$entitiesDiag = new TwatchEntitiesDiagInfo();
		$entitiesDiag->load();
		if( !$xhtml ) {
			$entitiesDiag->printXml( $p, 'result' );
			$p->nl();
		}

	} else {
		throw new TwatchException( 'unknown action '.$_POST['a'] );
	}

	$p->end();
?>