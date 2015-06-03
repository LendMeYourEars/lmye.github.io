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
  
	require_once $ardeBase->path( 'lib/ArdeProgress.php' );
	
	class TwatchProgress extends ArdeProgress {
		public function __construct( $channelId = null ) {
			global $twatch;
			parent::__construct( $twatch->db, 'pr', $channelId );
		}
	}
?>