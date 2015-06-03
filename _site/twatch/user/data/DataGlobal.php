<?php
	require_once $ardeUser->path( 'lib/General.php' );
	
	ardeUserMakeDefDataGlobal();
	
	function ardeUserMakeDefDataGlobal() {
		$conf = array();
		
		$conf[ ArdeUserConfig::DEFAULT_LANG ][ 0 ] = 'English';
		$conf[ ArdeUserConfig::VERSION ][ 0 ] = '0.1';
		$conf[ ArdeUserConfig::INSTANCE_ID ][ 0 ] = 0;
		ArdeUserConfig::$defaultProperties = $conf;
	}
?>