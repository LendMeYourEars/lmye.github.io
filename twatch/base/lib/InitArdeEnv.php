<?php
	require_once dirname(__FILE__).'/Global.php';
	
	require_once $ardeBase->path( 'lib/ArdeXmlPrinter.php' );
	
	require_once $ardeBase->path( 'lib/General.php' );
	
	
	$p = new ArdeXmlPrinter( true, false );
	
	ArdeException::startErrorSystem( $p, $p );
	
?>