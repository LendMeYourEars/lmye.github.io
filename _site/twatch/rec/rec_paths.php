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
	
	require_once dirname(__FILE__).'/../lib/EntityV.php';
	require_once dirname(__FILE__).'/../lib/Reader.php';

	
	
	loadConfig();
	
	requirePermission( TwatchUserData::VIEW_REPORTS );
	
	if( !isset( $_POST[ 'w' ] ) ) $websiteId = 1;
	else $websiteId = (int)$_POST[ 'w' ];
	
	$website = $twatch->config->get( TwatchConfig::WEBSITES, $websiteId );
	$pathR = new TwatchPathReader( $website->getSub() );
	
	if( !isset( $_POST['do'] ) ) throw new ArdeException( 'data order not specified' );
	$orders = explode( '_', $_POST['do'] );
	
	if( count( $orders ) > 10 ) throw new ArdeException( 'data order too long' );
	foreach( $orders as $k => $v ) $orders[$k] = (int)$orders[$k];
	
	$test_orders = array_fill( 0, count( $orders ), 1 );

	foreach( $orders as $ord ) {
		if( !isset( $test_orders[$ord] ) ) throw new ArdeException( 'invalid data order' );
		unset( $test_orders[$ord] );
	} 
	if( count( $test_orders ) ) throw new ArdeException( 'invalid data order' );
	
	if( isset( $_POST['d'] ) ) {
		$data = explode( '_', $_POST['d'] );
		if( count( $data ) > 20 ) throw new ArdeException( 'too many data' );
		foreach( $data as $k => $v ) $data[$k] = (int)$data[$k];
		
		$res = $pathR->read( $orders, $data );
	
		if( !$xhtml ) {
			$res->printXml( $p, 'path_res_set' );
		} else {
		}
	} elseif( isset( $_POST['c'] ) ) {
		$column = (int)$_POST['c'];
		$res = $pathR->readColumn( $orders, $column );
		
		if( !$xhtml ) {
			$res->printXml( $p, 'path_res' );
		} else {
			$p->relnl();
			$p->pl( '</body>' );
		}
	} else {
		throw new ArdeException( 'no action specified' );
	}

	$p->end();
?>
