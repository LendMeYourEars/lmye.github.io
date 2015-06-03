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

	require_once dirname(__FILE__).'/../lib/Global.php';

	class TwatchRemoveData {}
	$GLOBALS[ 'twatchRemoveData' ] = new TwatchRemoveData();

	if( !$GLOBALS['twatch']->settings[ 'down' ] ) {
		require_once $GLOBALS[ 'twatch' ]->path( 'lib/Logger.php' );

		function twatchLogRequest( $cookie = false, $websiteId = 1, $logErrors = true, $showErrors = false, $mutedErrors = true, $pageParams = true, $data = array() ) {
			global $twatch, $ardeBase;

			$p = new ArdePrinter( false, false );

			ArdeException::startErrorSystem( null, $p );

			$p->setHideErrors( !$showErrors );
			$p->setMutedErrors( $mutedErrors );

			try {

				twatchConnect();


				if( $logErrors ) {
					ArdeException::setGlobalReporter( new TwatchErrorLogger( ArdeException::getGlobalReporter() ) );
				}


				$request = new TwatchRequest( $websiteId );

				if( !isset( $data[ 'referrer' ] ) ) {
					if( isset( $_SERVER[ 'HTTP_REFERER' ] ) ) $request->data[ 'referrer' ] = $_SERVER[ 'HTTP_REFERER' ];
				}


				if( !isset( $data[ 'ip' ] ) ) {
					if( isset( $_SERVER[ 'REMOTE_ADDR' ] ) ) $request->data[ 'ip' ] = $_SERVER[ 'REMOTE_ADDR' ];
				}

				if( !isset( $data[ 'fip' ] ) ) {
					if( !empty( $_SERVER[ 'HTTP_CLIENT_IP' ] ) ) $request->data[ 'fip' ] = $_SERVER[ 'HTTP_CLIENT_IP' ];
					elseif( !empty( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ) $request->data[ 'fip' ] = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
				}

				if( !isset( $data[ 'page' ] ) ) {
					$request->data[ 'page' ] = ardeRequestUri();
					if( $request->data[ 'page' ] === null ) $request->data[ 'page' ] = '-';
				}

				if( !isset( $data[ 'agent' ] ) ) {
					if( isset( $_SERVER[ 'HTTP_USER_AGENT' ] ) ) $request->data[ 'agent' ] = $_SERVER[ 'HTTP_USER_AGENT' ];
				}

				if( !isset( $data[ 'admin' ] ) ) {
					if( isset( $_COOKIE[ $twatch->settings[ 'cookie_prefix' ].'_admin' ] ) ) {
						$request->data[ 'admin' ] = $_COOKIE[ $twatch->settings[ 'cookie_prefix' ].'_admin' ];
					}
				}

				if( $cookie ) {
					if( !isset( $data[ 'scookie' ] ) && isset( $_COOKIE[ $twatch->settings[ 'cookie_prefix' ].'_scookie' ] ) )
						$request->data[ 'scookie' ] = $_COOKIE[ $twatch->settings[ 'cookie_prefix' ].'_scookie' ];
					if( !isset( $data[ 'pcookie' ] ) && isset( $_COOKIE[ $twatch->settings[ 'cookie_prefix' ].'_pcookie' ] ) )
						$request->data[ 'pcookie' ] = $_COOKIE[ $twatch->settings[ 'cookie_prefix' ].'_pcookie' ];
				}

				foreach( $data as $key => $value ) {
					if( !$value instanceof TwatchRemoveData ) $request->data[ $key ] = $value;
				}

				if( !$pageParams && isset( $request->data[ 'page' ] ) ) {
					$request->data[ 'page' ] = ardeRemoveUrlParams( $request->data[ 'page' ] );
				}

				$request->log();

				if( $cookie ) {
					if( $scookie = $request->getScookieToSet() ) {
						setcookie( $twatch->settings[ 'cookie_prefix' ].'_scookie', $scookie, 0, $request->getCookieFolder(), $request->getCookieDomain() );
					}
					if( $pcookie = $request->getPcookieToSet() ) {
						setcookie( $twatch->settings[ 'cookie_prefix' ].'_pcookie', $pcookie, time()+86400*3650, $request->getCookieFolder(), $request->getCookieDomain() );
					}
				}
			} catch( ArdeException $e ) {
				ArdeException::reportError( $e );
			}

			ArdeException::restoreErrorSystem();
		}
	} else {
		function twatchLogRequest() {}
	}
?>
