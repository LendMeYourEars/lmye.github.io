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
		$twatchProfile = $_GET[ 'profile' ];
	}
	
	require_once dirname(__FILE__).'/Global.php';
	
	if( $twatch->settings[ 'down' ] ) die( $twatch->settings[ 'down_message' ] );
	
	require_once $twatch->path( 'lib/Page.php' );
	require_once $ardeBase->path( 'lib/ArdeXmlPrinter.php' );
	require_once $ardeBase->path( 'lib/ArdeException.php' );
	require_once $ardeBase->path( 'lib/ArdeJs.php' );
	
	$p = new ArdeXmlPrinter( true, false );
	ArdeException::startErrorSystem( $p, $p );
	if( !$twatch->settings[ 'unauthorized_show_errors' ] ) {
		$p->setHideErrors( true );
	}
	if( $twatch->settings[ 'unauthorized_muted_errors' ] ) {
		$p->setMutedErrors( true );
	}

?>