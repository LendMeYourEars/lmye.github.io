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

	require_once $twatch->path( 'lib/Progress.php' );

	requirePermission( TwatchUserData::VIEW_REPORTS );
	
	$channelId = ArdeParam::int( $_POST, 'ch', 0 );
	
	$progress = new TwatchProgress( $channelId );
	$num = $progress->get();
	if( !$xhtml ) {
		$p->pl( '<result>'.$num.'</result>' );
	}
	
	
	$p->end();
?>