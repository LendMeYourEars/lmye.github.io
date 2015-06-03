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
		
	class CountryAboutPage extends CountryAboutPageParent {
		
		protected function printContents( ArdePrinter $p ) {
			global $twatch, $ardeCountry;
			if( isset( TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ] ) ) {
				$GLOBALS[ 'ardeCountryProfile' ] = TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ];
			}
			require_once TwatchCountryPlugin::$object->ardeCountryPath( 'lib/Global.php' );
			require_once $ardeCountry->path( 'lib/General.php' );
			require_once $ardeCountry->path( 'data/DataGlobal.php' );
			
			$ardeCountry->db = new ArdeDb( $ardeCountry->settings );
			$ardeCountry->db->connect();
			
			$ardeCountry->config = new ArdeCountryConfig( $ardeCountry->db );
			$ardeCountry->config->addDefaults( ArdeCountryConfig::$defaultProperties );
			$ardeCountry->config->applyAllChanges();
			
			parent::printContents( $p );
			$p->pl( '<hr />');
			$ardeCountry->printDbCopyright( $p, $ardeCountry->config->get( ArdeCountryConfig::DB_SOURCE ) );
		}
		
	}
?>
