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
	
	class TwatchRemoveData {}
	$GLOBALS[ 'twatchRemoveData' ] = new TwatchRemoveData();

	function twatchRemoteLogRequest( $address, $cookie = false, $websiteId = 1, $pageParams = true, $data = array(), $cookiePrefix = 'twatch', $cookieDomain = null, $cookieFolder = '/' ) {
		global $twatch, $ardeBase, $twatchProfile;

		if( !isset( $data[ 'referrer' ] ) ) {
			if( isset( $_SERVER[ 'HTTP_REFERER' ] ) ) $data[ 'referrer' ] = $_SERVER[ 'HTTP_REFERER' ];
		}

		if( !isset( $data[ 'ip' ] ) ) {
			if( isset( $_SERVER[ 'REMOTE_ADDR' ] ) ) $data[ 'ip' ] = $_SERVER[ 'REMOTE_ADDR' ];
		}
		
		if( !isset( $data[ 'fip' ] ) ) {
			if( !empty( $_SERVER[ 'HTTP_CLIENT_IP' ] ) ) $data[ 'fip' ] = $_SERVER[ 'HTTP_CLIENT_IP' ];	
			elseif( !empty( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ) $data[ 'fip' ] = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
		}
		
		if( !isset( $data[ 'page' ] ) ) {
			$data[ 'page' ] = ardeRemoteRequestUri();
			if( $data[ 'page' ] === null ) $data[ 'page' ] = '-';
		}
		
		if( !isset( $data[ 'agent' ] ) ) {
			if( isset( $_SERVER[ 'HTTP_USER_AGENT' ] ) ) $data[ 'agent' ] = $_SERVER[ 'HTTP_USER_AGENT' ];
		}
		
		if( !isset( $data[ 'admin' ] ) ) {
			if( isset( $_COOKIE[ $cookiePrefix.'_admin' ] ) ) {
				$data[ 'admin' ] = $_COOKIE[ $cookiePrefix.'_admin' ];
			}
		}
		
		if( $cookie ) {
			if( !isset( $data[ 'scookie' ] ) && isset( $_COOKIE[ $cookiePrefix.'_scookie' ] ) )
				$data[ 'scookie' ] = $_COOKIE[ $cookiePrefix.'_scookie' ];
			if( !isset( $data[ 'pcookie' ] ) && isset( $_COOKIE[ $cookiePrefix.'_pcookie' ] ) )
				$data[ 'pcookie' ] = $_COOKIE[ $cookiePrefix.'_pcookie' ];
		}
		
		foreach( $data as $key => $value ) {
			if( $value instanceof TwatchRemoveData ) unset( $data[ $key ] );
		}
		
		if( !$pageParams && isset( $data[ 'page' ] ) ) {
			if( preg_match( '/^([^\?]+)\?.*$/ ', $data[ 'page' ], $matches ) ) { 
				$data[ 'page' ] = $matches[1];
			}
		}
		
		if( is_int( $websiteId ) ) {
			$params = 'twatch_website_int_id='.$websiteId;
		} else {
			$params = 'twatch_website_id='.urlencode( $websiteId );
		}
		
		if( isset( $twatchProfile ) ) {
			$params .= '&twatch_profile='.urlencode( $twatchProfile );
		}
		
		foreach( $data as $key => $value ) {
			$params .= '&'.$key.'='.urlencode( $value );
		}

		$doc = new DOMDocument();
		
		if( !$doc->load( $address.'?'.$params ) ) {
			trigger_error( "TraceWatch is unable to call ".$address, E_USER_WARNING );
		}
		
		
		if( $cookie ) {
			
			$rootElement = $doc->documentElement;
			
			$scookie = $rootElement->getElementsByTagName( 'scookie' )->item(0);
			if( $scookie !== null ) {
				if( $scookie->hasAttribute( 'value' ) ) {
					$scookie = $scookie->getAttribute( 'value' );
					setcookie( $cookiePrefix.'_scookie', $scookie, 0, $cookieFolder, $cookieDomain );
				}
			}
			
			$pcookie = $rootElement->getElementsByTagName( 'pcookie' )->item(0);
			if( $pcookie !== null ) {
				if( $pcookie->hasAttribute( 'value' ) ) {
					$pcookie = $pcookie->getAttribute( 'value' );
					setcookie( $cookiePrefix.'_pcookie', $pcookie, time()+86400*3650, $cookieFolder, $cookieDomain );
				}
			}

		}

	}
	
	function ardeRemoteRequestUri() {
		if( !empty( $_SERVER[ 'REQUEST_URI' ] ) ) {
			return $_SERVER[ 'REQUEST_URI' ];
		} else {
			if( !empty( $_SERVER[ 'PHP_SELF' ] ) ) {
				$s = $_SERVER[ 'PHP_SELF' ];
			} elseif( !empty( $_SERVER[ 'SCRIPT_NAME' ] ) ) {
				$s = $_SERVER[ 'SCRIPT_NAME' ];
			} else {
				return null;
			}
			if( !empty( $_SERVER['QUERY_STRING'] ) ) {
				$s .= '?'.$_SERVER[ 'QUERY_STRING' ];
			} else {
				$i = 0;
				foreach( $_GET as $k => $v ) {
					$s .= ($i?'&':'?').$k.'='.urlencode( $v );
					$i++;
				}
			}
			return $s;
		}
	}
?>
