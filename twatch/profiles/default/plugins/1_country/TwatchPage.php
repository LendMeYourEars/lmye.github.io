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
	
	class TwatchCountryPage extends TwatchCountryPageParent {
		public function initJsPage( ArdePrinter $p, $texts = array() ) {
			global $twatch;
			parent::initJsPage( $p, $texts );
			$p->pl( "ardeCountryUrl = '".TwatchCountryPlugin::$object->ardeCountryUrl( $this->getToRoot(), '' )."';" );
		}
	}
?>