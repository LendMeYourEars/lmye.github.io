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
	
	require_once dirname(__FILE__).'/../db/DbLogger.php';
	require_once dirname(__FILE__).'/../db/DbReader.php';

	
	
	
	
	ignore_user_abort( true );
	
	loadConfig();
	
	if( !$ardeUser->user->hasPermission( TwatchUserData::VIEW_IPS ) ) throw new ArdeUserError( 'You don\'t have permission' );
	requirePermission( TwatchUserData::VIEW_REPORTS );
	
	$i = ArdeParam::u32( $_POST, 'i' );
	
	$ip = long2ip( $i );
	
	$dbrDict = new TwatchDbrDict( $twatch->db );
	
	$dbrDict->get( TwatchDict::IP, $i );
	
	$dbrDict->rollGet();
	$res = $dbrDict->getResult( TwatchDict::IP, $i );
	if( $res === false ) {
		$domain = 'unresolved';
	} else {
		if( $res->extra == '' ) {
			$domain = gethostbyaddr( $ip );
			if( $domain == $ip ) $domain = 'unresolved';
			$dbDict = new TwatchDbDict( $twatch->db );
			$dbDict->setExtra( TwatchDict::IP, $i, $domain );
		} else {
			$domain = $res->extra;
		}
	}
	
	if( !$xhtml ) {
		$p->pl( '<result>'.$domain.'</result>' );
	}

	$p->end();
?>
