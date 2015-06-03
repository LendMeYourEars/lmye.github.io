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
    
	
	ini_set( 'display_errors', '1' );

	if( isset( $_GET[ 'profile' ] ) ) {
		$ardeUserProfile = $_GET[ 'profile' ];
	}

	
	require_once dirname(__FILE__).'/../lib/Global.php';
	require_once $ardeUser->path( 'lib/UserAdmin.php' );
	
	if( $ardeUser->settings[ 'down' ] ) die( $ardeUser->settings[ 'down_message' ] );
	
	require_once $ardeBase->path( 'lib/ArdeXmlPrinter.php' );
	require_once $ardeBase->path( 'lib/ArdeJs.php' );
	
	require_once $ardeUser->path( 'data/DataGlobal.php' );
	
	$xhtml = isset( $_GET['xhtml'] );
	
	$p = new ArdeXmlPrinter( $xhtml, !$xhtml, 'response' );
	ArdeException::startErrorSystem( $p, $p );
	
	$p->setHideErrors( !$ardeUser->settings[ 'unauthorized_show_errors' ] );
	$p->setMutedErrors( $ardeUser->settings[ 'unauthorized_muted_errors' ] );

	
	$p->start( 'application' );
	
	if( $xhtml ) {
		$p->pl( '<body>', 1 );
	}
	
	foreach( $_GET as $k => $v ) { $_POST[$k] = $v; }
	
	$ardeUser->db = new ArdeDb( $ardeUser->settings );
	$ardeUser->db->connect();
	
	ArdeUserApp::initUser();
	
	if( $ardeUser->user->hasPermission( ArdeUserData::VIEW_ERRORS ) ) {
		$p->setMutedErrors(  $ardeUser->settings[ 'authorized_muted_errors' ] );
		$p->setHideErrors( !$ardeUser->settings[ 'authorized_show_errors' ] );
	}
	
	if( ( $ardeUser->settings[ 'authorized_log_errors' ] && $ardeUser->user->hasPermission( ArdeUserData::VIEW_ERRORS ) ) ||
		( $ardeUser->settings[ 'unauthorized_log_errors' ] && !$ardeUser->user->hasPermission( ArdeUserData::VIEW_ERRORS ) ) ) {
		ArdeException::setGlobalReporter( new ArdeUserErrorLogger( ArdeException::getGlobalReporter() ) );
	}
	
	if( !$ardeUser->user->hasPermission( ArdeUserData::ADMINISTRATE ) ) {
		throw new ArdeUserError( "You don't have permission" );
	}

	$ardeUser->config = new ArdeUserConfig( $ardeUser->db );

	$ardeUser->config->addDefaults( ArdeUserConfig::$defaultProperties );

	function loadConfig( $extraDefConfig = array() ) {
		global $ardeUser;
		foreach( $extraDefConfig as $extraDef ) {
			$ardeUser->config->addDefaults( $extraDef );
		}
		$ardeUser->config->applyAllChanges();
		
		if( isset( $_GET[ 'lang' ] ) && $ardeUser->localeExists( $_GET[ 'lang' ] ) ) {
			$ardeUser->loadLocale( $_GET[ 'lang' ] );
		} else {
			$ardeUser->loadLocale( $ardeUser->config->get( ArdeUserConfig::DEFAULT_LANG ) );
		}

	}

	
	
?>