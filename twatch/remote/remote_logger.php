<?php
	header( 'Cache-Control: no-store, no-cache, must-revalidate' );
	header( 'Cache-Control: post-check=0, pre-check=0', false );
	header( 'Pragma: no-cache' );

	if( isset( $_GET[ 'twatch_profile' ] ) ) $twatchProfile = $_GET[ 'twatch_profile' ];

	require_once dirname(__FILE__).'/../lib/Global.php';

	if( !$twatch->settings[ 'allow_remote_logging' ] ) die();
	if( $twatch->settings[ 'down' ] ) die( $twatch->settings[ 'down_message' ] );

	require_once $GLOBALS[ 'twatch' ]->path( 'lib/Logger.php' );

	require_once $ardeBase->path( 'lib/ArdeXmlPrinter.php' );
	require_once $ardeBase->path( 'lib/ArdeJs.php' );

	$xhtml = isset( $_GET['xhtml'] );

	$p = new ArdeXmlPrinter( $xhtml, !$xhtml, 'response' );
	ArdeException::startErrorSystem( $p, $p );

	$p->setHideErrors( !$twatch->settings[ 'unauthorized_show_errors' ] );
	$p->setMutedErrors( $twatch->settings[ 'unauthorized_muted_errors' ] );

	$p->start( true );

	if( $xhtml ) {
		$p->pl( '<body>', 1 );
	}

	$twatch->db = new ArdeDb( $twatch->settings );
	$twatch->db->connect();

	if( $twatch->settings[ 'unauthorized_log_errors' ] ) {
		ArdeException::setGlobalReporter( new TwatchErrorLogger( ArdeException::getGlobalReporter() ) );
	}

	if( isset( $_GET['twatch_website_int_id'] ) ) {
		$websiteId = (int)$_GET['twatch_website_int_id'];
		unset( $_GET['twatch_website_int_id'] );
	} else {
		$websiteId = ArdeParam::str( $_GET, 'twatch_website_id' );
		unset( $_GET['twatch_website_id'] );
	}

	$request = new TwatchRequest( $websiteId );

	$request->data = $_GET;

	$request->log();


	if( $scookie = $request->getScookieToSet() ) {
		if( !$xhtml ) {
			$p->pl( '<scookie value="'.ardeXmlEntities( $scookie ).'" />' );
		} else {
		}
	}
	if( $pcookie = $request->getPcookieToSet() ) {
		if( !$xhtml ) {
			$p->pl( '<pcookie value="'.ardeXmlEntities( $pcookie ).'" />' );
		} else {
		}
	}

	if( $xhtml ) {
		$p->relnl();
		$p->pl( '</body>' );
	}

	$p->end();
?>