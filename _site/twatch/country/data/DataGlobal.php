<?php
	require_once $ardeCountry->path( 'lib/General.php' );
	
	ardeCountryMakeDefDataGlobal();
	
	function ardeCountryMakeDefDataGlobal() {
		$conf = array();
		
		$conf[ ArdeCountryConfig::VERSION ][ 0 ] = '0.106';
		$conf[ ArdeCountryConfig::INSTANCE_ID ][ 0 ] = 0;
		$conf[ ArdeCountryConfig::DB_SOURCE ][ 0 ] = ArdeCountryApp::SOURCE_SOFTWARE77;
		ArdeCountryConfig::$defaultProperties = $conf;
	}
?>