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
    
	$ardeUser->path( 'lib/General.php' );
	
	ardeUserMakeUserData();
	
	function ardeUserMakeUserData() {
		$du = array();
		
		$du[ ArdeUserData::ADMINISTRATE ][0] = false;
		$du[ ArdeUserData::VIEW_ERRORS ][0] = false;
		ArdeUserData::$defaultProperties = $du;
	}
?>