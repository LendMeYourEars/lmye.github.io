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
		
	class TwatchCountryInstallPage extends TwatchCountryInstallPageParent {
		
		public function printInsideForm( ArdePrinter $p ) {
			
			global $ardeCountry;
			
			if( isset( TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ] ) ) {
				$GLOBALS[ 'ardeCountryProfile' ] = TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ];
			}
			require_once TwatchCountryPlugin::$object->ardeCountryPath( 'lib/Global.php' );
			require_once TwatchCountryPlugin::$object->ardeCountryPath( 'lib/General.php' );
			
			parent::printInsideForm( $p );
			$p->pl( '<p><label><input type="checkbox" name="install_iptc" checked="true" /> install ip to country</label></p>' );
			$ardeCountry->printSourceSelector( $p );
		}
		
		public function run( ArdePrinter $p ) {
			global $ardeCountry;
			parent::run( $p );
			if( isset( $_GET[ 'install_iptc' ] ) ) {
				if( isset( TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ] ) ) {
					$GLOBALS[ 'ardeCountryProfile' ] = TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ];
				}
				require_once TwatchCountryPlugin::$object->ardeCountryPath( 'lib/Global.php' );
				require_once $ardeCountry->path( 'lib/Installer.php' );
				
				$ardeCountry->db = new ArdeDb( $ardeCountry->settings );
				$ardeCountry->db->connect();
				
				if( !isset( $_GET[ 'iptc_source' ] ) ) throw new ArdeUserError( 'source not specified' );
				$source = (int)$_GET[ 'iptc_source' ];
				
				$installer = new ArdeCountryInstaller();
				$installer->install( $p, $source, isset( $_GET[ 'overwrite' ] ) );
			}
		}
		
	} 
?>