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




	header( 'Cache-Control: no-store, no-cache, must-revalidate' );
	header( 'Cache-Control: post-check=0, pre-check=0', false );
	header( 'Pragma: no-cache' );

	foreach( $_POST as $key => $value ) {
		$_GET[ $key ] = $value;
	}

	if( isset( $_GET[ 'twatch_profile' ] ) ) $twatchProfile = $_GET[ 'twatch_profile' ];
	require_once dirname(__FILE__).'/../api/LogRequest.php';

	if( !$twatch->settings[ 'allow_js_logging' ] ) die();
	if( $twatch->settings[ 'down' ] ) die( $twatch->settings[ 'down_message' ] );


	if( !isset( $_GET[ 'page' ] ) ) die( 'page not specified' );

	if( !preg_match( '/^[^\/]+\:\/\/[^\/]+(\/.*|)$/', $_GET[ 'page' ], $matches ) ) die( 'page not valid' );

	$data = array( 'page' => $matches[1] );

	if( isset( $_GET[ 'ref' ] ) ) {
		$data[ 'referrer' ] = $_GET[ 'ref' ];
	} else {
		$data[ 'referrer' ] = $twatchRemoveData;
	}

	$pageParams = !isset( $_GET[ 'strip_page_params' ] );

	$cookie = !isset( $_GET[ 'no_cookies' ] );

	if( isset( $_GET[ 'website_id' ] ) ) {
		$websiteId = $_GET[ 'website_id' ];
	} else {
		$websiteId = 1;
	}

	foreach( $_GET as $key => $value ) {
		if( !isset( $data[ $key ] ) ) $data[ $key ] = $value;
	}

	twatchLogRequest( $cookie, $websiteId,
		$twatch->settings[ 'unauthorized_log_errors' ],
		$twatch->settings[ 'unauthorized_show_errors' ],
		$twatch->settings[ 'unauthorized_muted_errors' ],
		$pageParams, $data );
?>