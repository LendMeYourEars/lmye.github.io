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
	require_once $twatch->path( 'data/DataLatest.php' );
	require_once $twatch->path( 'lib/AdminGeneral.php' );
	
	loadConfig();
	
	$latest = &$twatch->config->get( TwatchConfig::LATEST );
	
	if( !isset( $_POST['a'] )) throw new TwatchException( 'Action was not sent' );
	
	if( $_POST['a'] == 'set_vis' ) {

		$selectedUser = getSelectedUser( true, TwatchLatestPage::$defaults );
		requireConfigPermission( $selectedUser );
		
		BoolWithDefAction::fromParams( TwatchUserData::VIEW_LATEST, 0, $_POST )->run( $selectedUser->data );
		
		successful( $p );
		
	} elseif( $_POST['a'] == 'change_latest' ) {
		requirePermission( TwatchUserData::ADMINISTRATE );
		$newLatest = TwatchLatest::fromParams( $_POST );
		if( $newLatest->isEquivalent( $latest ) ) throw new TwatchUserError( 'nothing to change' );
		$latest = $newLatest;
		$twatch->config->setInternal( TwatchConfig::LATEST );
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'restore_latest' ) {
		requirePermission( TwatchUserData::ADMINISTRATE );
		$twatch->config->restoreDefault( TwatchConfig::LATEST );
		if( !$xhtml ) {
			$latest->printXml( $p, 'result' );
			$p->nl();
		}
		
	} elseif( $_POST['a'] == 'change_page' ) {
		
		$selectedUser = getSelectedUser( true, TwatchLatestPage::$defaults );
		requireConfigPermission( $selectedUser );
		
		$latestPage = &$selectedUser->data->get( TwatchUserData::LATEST_PAGE );
		$newLatestPage = TwatchLatestPage::fromParams( $_POST );
		if( $newLatestPage->isEquivalent( $latestPage ) ) throw new TwatchUserError( 'nothing to change' );
		$latestPage = $newLatestPage;
		$selectedUser->data->setInternal( TwatchUserData::LATEST_PAGE );
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'restore_page' ) {
		$selectedUser = getSelectedUser( true, TwatchLatestPage::$defaults );
		requireConfigPermission( $selectedUser );
		
		$latestPage = &$selectedUser->data->get( TwatchUserData::LATEST_PAGE );
		$selectedUser->data->restoreDefault( TwatchUserData::LATEST_PAGE );
		if( !$xhtml ) {
			$latestPage->printXml( $p, 'result' );
			$p->nl();
		}
	
	} elseif( $_POST['a'] == 'cleanup' ) {
		requirePermission( TwatchUserData::ADMINISTRATE );
		$latest->cleanup();
		if( !$xhtml ) $p->pl( '<successful />' );
	
	} elseif( $_POST['a'] == 'reset' ) {	
		requirePermission( TwatchUserData::ADMINISTRATE );
		$twatch->config->get( TwatchConfig::COOKIE_KEYS )->upgradeKeys();
		$twatch->config->setInternal( TwatchConfig::COOKIE_KEYS );
		$latest->uninstall();
		$latest->install( true );
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} else {
		throw new TwatchException( 'unknown action '.$_POST['a'] );
	}

	$p->end();
?>
