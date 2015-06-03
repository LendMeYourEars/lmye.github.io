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
	
	require_once $ardeBase->path( 'lib/ArdeAppPage.php' );
	
	class TwatchPassivePage extends ArdeAppPage {

		function __construct() {
			global $twatch;
			parent::__construct( $twatch );	
		}
		
		protected function printInFooter( ArdePrinter $p ) {
			global $twatch;
			$p->pn( '<a href="http://www.tracewatch.com/">TraceWatch</a> '.$twatch->version.' Copyright &copy;'.$twatch->getCopyrightYears().' Arash Dejkam</div>' );
		}
		
	}
	
?>