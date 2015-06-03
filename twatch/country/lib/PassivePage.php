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

	class ArdeCountryPassivePage extends ArdeAppPage {

		function __construct() {
			global $ardeCountry;
			parent::__construct( $ardeCountry );	
		}
		
		public function printInFooter( ArdePrinter $p ) {
			global $ardeCountry;
			$p->pn( 'ArdeCountry '.$ardeCountry->version.' Copyright &copy;2009-2010 Arash Dejkam</div>' );
		}
	}
?>