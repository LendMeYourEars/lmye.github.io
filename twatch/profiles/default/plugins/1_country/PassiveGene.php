<?php
	class TwatchEntCountryPasvGene extends TwatchEntityPassiveGene {
		public function __construct( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			parent::__construct( $dict, $mode, $context );
			if( isset( TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ] ) ) {
				$GLOBALS[ 'ardeCountryProfile' ] = TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ];
			}
			require_once TwatchCountryPlugin::$object->ardeCountryPath( 'lib/Global.php' );
			require_once $ardeCountry->path( 'lib/Country.php' );
		}
		
		public function getStringEntityVId( $string, TwatchWebsite $website = null  ) {
			return ArdeCountry::getNameId( $string );
		}
	}
?>