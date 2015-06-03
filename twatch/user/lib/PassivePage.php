<?php
	require_once $ardeBase->path( 'lib/ArdeAppPage.php' );
	
	global $ardeUser;
	
	class ArdeUserPassivePage extends ArdeAppPage {
		function __construct() {
			global $ardeUser;
			parent::__construct( $ardeUser );
		}
		
		public function printInFooter( ArdePrinter $p ) {
			global $ardeUser;
			$p->pn( 'ArdeUser '.$ardeUser->version.' Copyright &copy;2009-2010 Arash Dejkam</div>' );
		}
	}
	
	$ardeUser->applyOverrides( array( 'ArdeUserPassivePage' => true ) );
?>