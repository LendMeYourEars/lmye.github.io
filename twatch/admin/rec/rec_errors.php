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
    
	$adminConfig = true;
	require_once dirname(__FILE__).'/../../lib/RecGlobalHead.php';
	
	loadConfig();
	
	requirePermission( TwatchUserData::VIEW_ERRORS );

	if( !isset( $_POST['a'] ) ) throw new TwatchException( 'Action was not sent' );
	

	if( $_POST['a'] == 'clear' ) {
		
		$errorLogger = new TwatchErrorLogger();
		$errorLogger->clear();
		if( !$xhtml ) {
			$p->pl( '<successful />' );
		}
		
	} else {
		throw new TwatchException( 'unknown action '.$_POST['a'] );
	}
	
	$p->end();
?>