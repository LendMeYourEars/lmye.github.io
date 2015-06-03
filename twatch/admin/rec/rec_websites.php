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
	
	require_once $ardeUser->path( 'lib/UserAdmin.php' );
	
	
	$websites = &$twatch->config->getList( TwatchConfig::WEBSITES );
	if( !isset( $_POST['a'] )) throw new TwatchException( 'Action was not sent' );

	function websiteFromParams() {
		if( !isset( $_POST['i'] ) ) $id = 0;
		else $id = (int)$_POST['i'];

		if( !isset( $_POST['n'] )) throw new TwatchException( 'Name was not specified' );
		$name = $_POST['n'];
		if( $name == '' ) throw new TwatchUserError( 'Name can not be empty' );

		if( !isset( $_POST['h'] )) throw new TwatchException( 'Handle was not specified' );
		$handle = $_POST['h'];
		if( $handle == '' ) throw new TwatchUserError( 'Handle can not be empty' );

		if( !isset( $_POST['p'] ) ) $parent = 0;
		else $parent = (int)$_POST['p'];

		if( !isset( $_POST['d'] ) ) $domains = '';
		else $domains = $_POST['d'];
		$doms = explode( '|', $domains );
		foreach( $doms as $k => $v ) {
			if( $v == '' ) unset( $doms[$k] );
		}

		
		$cookieDomain = ArdeParam::str( $_POST, 'cd' );
		$cookieFolder = ArdeParam::str( $_POST, 'cf' );

		return new TwatchWebsite( $id, $name, $handle, $parent, $doms, $cookieDomain, $cookieFolder );
	}

	if( $_POST['a'] == 'set_vis' ) {
		
		$id = ArdeParam::int( $_POST, 'i', 0 );
		if( !$twatch->config->propertyExists( TwatchConfig::WEBSITES, $id ) ) throw new TwatchUserError( 'website with id '.$id.' not found.' );
		if( $twatch->config->get( TwatchConfig::WEBSITES, $id )->parent ) throw new TwatchUserError( 'You can\'t set visibility for a sub-website.' );
		require_once $twatch->path( 'lib/AdminGeneral.php' );
		$selectedUser = getSelectedUser( false );
		if( $selectedUser->isRoot() ) throw new TwatchUserError( 'can\'t change website visibility for root user.' );
		BoolWithDefAction::fromParams( TwatchUserData::VIEW_WEBSITE, $id, $_POST )->run( $selectedUser->data );
		
		successful( $p );
		
	} elseif( $_POST['a'] == 'add' ) {

		$website = websiteFromParams();
		if( $website->parent ) {
			if( !isset( $websites[ $website->parent ] ) ) throw new TwatchException( 'parent website with id '.$website->parent.' not found.' );
		}
		$newId = $twatch->config->getNewSubId( TwatchConfig::WEBSITES );
		$website->setId( $newId );
		$twatch->config->addToBottom( $website, TwatchConfig::WEBSITES, $website->getId() );

		$taskManager = new TwatchTaskManager();
		$website->install( $taskManager, true );

		if( !$xhtml ) {
			$website->printXml( $p, 'result' );
			$p->nl();
		}

	} elseif( $_POST['a'] == 'up' ) {
		$id = ArdeParam::int( $_POST, 'i' );
		if( !$twatch->config->propertyExists( TwatchConfig::WEBSITES, $id ) ) throw new TwatchException( 'Website '.$id.' doesn\'t exist' );
		$website = &$twatch->config->get( TwatchConfig::WEBSITES, $id );
		if( $website->parent ) throw new TwatchException( 'You can\'t move a sub-website' );
		
		$i = 0;
		$j = null;
		$websites = &$twatch->config->getList( TwatchConfig::WEBSITES ); 
		foreach( $websites as &$cWebsite ) {
			if( $cWebsite === $website ) break;
			if( !$cWebsite->parent ) $j = $i;
			++$i;
		}
		if( $j !== null ) {
			$c = $i - $j;
		} else {
			$c = 1;
		}
		
		for( $i = 0; $i < $c; ++$i ) {
			$twatch->config->moveUp( TwatchConfig::WEBSITES, $id );
		}
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'down' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !$twatch->config->propertyExists( TwatchConfig::WEBSITES, $id ) ) throw new TwatchException( 'Website '.$id.' doesn\'t exist' );
		$website = &$twatch->config->get( TwatchConfig::WEBSITES, $id );
		if( $website->parent ) throw new TwatchException( 'You can\'t move a sub-website' );
		
		$c = 0;
		$websites = &$twatch->config->getList( TwatchConfig::WEBSITES ); 
		foreach( $websites as &$cWebsite ) {
			if( !$c ) {
				if( $website === $cWebsite ) $c = 1;
			} else {
				if( !$cWebsite->parent ) break;
				++$c;
			}
		}
		
		for( $i = 0; $i < $c; ++$i ) {
			$twatch->config->moveDown( TwatchConfig::WEBSITES, $id );
		}
		
		if( !$xhtml ) $p->pl( '<successful />' );

	} elseif( $_POST['a'] == 'change' ) {

		$website = websiteFromParams();
		if( $website->getId() <= 0 ) throw new TwatchException( 'id not set or invalid' );
		if( !isset( $websites[ $website->getId() ] ) ) throw new TwatchException( 'website with id '.$website->getId().' not found' );
		if( $websites[ $website->getId() ]->isEquivalent( $website ) ) throw new TwatchUserError( 'nothing to change' );
		$twatch->config->set( $website, TwatchConfig::WEBSITES, $website->getId() );
		if( !$xhtml ) {
			if( !$website->parent ) {
				require_once $twatch->path( 'lib/AdminGeneral.php' );
				getSelectedUser()->getPermission( TwatchUserData::VIEW_WEBSITE, $website->getId() )->printXml( $p, 'result' );
				$p->nl();
			} else {
				$p->pl( '<successful />' );
			}
		}

	} elseif( $_POST['a'] == 'delete' ) {

		if( !isset( $_POST['i'] ) ) throw new TwatchException( 'id was not specified' );
		$id = (int)$_POST['i'];
		if( $id == 1 ) throw new TwatchException( "you can't delete the default website" );
		if( !isset( $websites[ $id ] ) ) throw new TwatchException( "website with id ".$id." not found" );

		$ks = array_keys( $websites );
		foreach( $ks as $k ) {
			if( $websites[ $k ]->parent == $id ) $twatch->config->remove( TwatchConfig::WEBSITES, $k );
		}

		$taskManager = new TwatchTaskManager();
		$websites[ $id ]->uninstall( $taskManager );


		$twatch->config->remove( TwatchConfig::WEBSITES, $id );

		$ardeUser->user->data->clearWebsite( $id );
		$ardeUser->user->data->clearId( TwatchUserData::VIEW_WEBSITE, $id );
		
		$p->pl( '<successful />' );

	} else {
		throw new TwatchException( 'unknown action '.$_POST['a'] );
	}

	if( !$xhtml ) {

	} else {
		$p->pl( 'successful' );
		$p->rel();
		$p->pl( '</body>' );
	}

	$p->end();
?>
