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
		
	class TwatchCountryUninstallPage extends TwatchCountryUninstallPageParent {
		
		public function printInsideForm( ArdePrinter $p ) {
			parent::printInsideForm( $p );
			$p->pl( '<p><label><input type="checkbox" name="uninstall_iptc" /> uninstall ip to country</label></p>' );
		}
		
		public function run( ArdePrinter $p ) {
			global $ardeCountry;
			parent::run( $p );
			if( isset( $_GET[ 'uninstall_iptc' ] ) ) {
				if( isset( TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ] ) ) {
					$GLOBALS[ 'ardeCountryProfile' ] = TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ];
				}
				require_once TwatchCountryPlugin::$object->ardeCountryPath( 'lib/Global.php' );
				require_once $ardeCountry->path( 'lib/Installer.php' );
				
				$ardeCountry->db = new ArdeDb( $ardeCountry->settings );
				$ardeCountry->db->connect();
				
				$installer = new ArdeCountryInstaller();
				$installer->uninstall( $p );
			}
		}
		
	} 
?>