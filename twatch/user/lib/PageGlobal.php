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

	if( isset( $_GET[ 'profile' ] ) ) {
		
		$ardeUserProfile = $_GET[ 'profile' ];
	}
	require_once dirname(__FILE__).'/Global.php';
	
	
	if( !isset( $updatePage ) && $ardeUser->settings[ 'down' ] ) die( $ardeUser->settings[ 'down_message' ] );
	
	require_once $ardeBase->path( 'lib/ArdeJs.php' );
	
	require_once $ardeUser->path( 'lib/Page.php' );
	require_once $ardeUser->path( 'lib/User.php' );
	
	$p = new ArdeXmlPrinter( true, false );
	ArdeException::startErrorSystem( $p, $p );
	
	if( !$ardeUser->settings[ 'unauthorized_show_errors' ] ) {
		$p->setHideErrors( true );
	}
	if( $ardeUser->settings[ 'unauthorized_muted_errors' ] ) {
		$p->setMutedErrors( true );
	}
	
	
	$page = new ArdeUserPage();
?>