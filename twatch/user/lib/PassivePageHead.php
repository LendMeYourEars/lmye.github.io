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
	
	require dirname(__FILE__).'/../lib/Global.php';

	if( !isset( $updatePage ) && $ardeUser->settings[ 'down' ] ) die( $ardeUser->settings[ 'down_message' ] );

	
	require_once $ardeUser->path( 'lib/PassivePage.php' );
	
	$p = new ArdeXmlPrinter( true, false );
	
	if( !$ardeUser->settings[ 'unauthorized_show_errors' ] ) {
		$p->setHideErrors( true );
	}
	if( $ardeUser->settings[ 'unauthorized_muted_errors' ] ) {
		$p->setMutedErrors( true );
	}
	
	ArdeException::startErrorSystem( $p, $p );
	
	
?>