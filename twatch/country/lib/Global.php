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

	global $ardeBase, $ardeCountry, $ardeCountryProfile;
	if( !isset( $ardeCountryProfile ) ) $ardeCountryProfile = 'default';

	if( !isset( $ardeBase ) ) {
		require_once dirname(__FILE__).'/../'.ardeCountryGetSetting( $ardeCountryProfile, 'to_base' ).'/lib/Global.php';
	}

	function ardeCountryGetSetting( $profile, $name ) {
		$settings = array();
		include dirname(__FILE__).'/../profiles/'.$profile.'/settings.php';
		return $settings[ $name ];
	}

	class ArdeCountryApp extends ArdeAppWithPlugins {

		const SOURCE_SOFTWARE77 = 1;
		const SOURCE_WEB_HOSTING_INFO = 2;
		
		
		public static $sourceNames = array(
			 self::SOURCE_SOFTWARE77 => 'software77.net'
			,self::SOURCE_WEB_HOSTING_INFO => 'ip-to-country.webhosting.info'
		);
		
		var $version = '0.106';

		var $name = 'ardeCountry';

		var $db;

		var $config;
		
		public function path( $target ) {
			return ardeSlashConcat( dirname(__FILE__).'/..', $target );
		}

		public function extPath( $appName, $target ) {
			return ardeSlashConcat( dirname(__FILE__).'/..', $this->settings[ 'to_'.$appName ], $target );
		}


		public function printSourceSelector( ArdePrinter $p ) {
			$p->pl( '<p>ip-to-country csv file source: <select name="iptc_source">' );
			foreach( self::$sourceNames as $id => $name ) {
				$p->pl( '	<option value="'.$id.'">'.$name.'</option>' );
			}
			$p->pl( '</select></p>' );
		}
		
		public function printDbCopyright( ArdePrinter $p, $source ) {
			if( $source == ArdeCountryApp::SOURCE_SOFTWARE77 ) {
				$p->pl( '<p style="text-align:left">This Software uses the IP-to-Country Database provided by software77.net, available at <a href="http://software77.net/geo-ip/">http://software77.net/geo-ip/</a></p>' );
				$p->pl( '<p style="text-align:left">IP-to-Country Database is Copyright &copy;2002-2011 Webnet77.com All Rights Reserved.</p>' );
			} else {
				$p->pl( '<p style="text-align:left">This Software uses the IP-to-Country Database provided by WebHosting.Info (http://www.webhosting.info), available at <a href="http://ip-to-country.webhosting.info/">http://ip-to-country.webhosting.info</a></p>' );
				$p->pl( '<p style="text-align:left">IP-to-Country Database is Copyright &copy;2003 Direct Information Pvt. Ltd. All Rights Reserved.</p>' );
			}
		}
	}

	$ardeCountry = new ArdeCountryApp( $ardeBase );
	$ardeCountry->loadSettings( $ardeCountryProfile );
?>
