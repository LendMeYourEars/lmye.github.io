<?php
	class TwatchUserPassivePage extends TwatchUserPassivePageParent {
		public function printInFooter( ArdePrinter $p ) {
			global $ardeUser, $twatch;
			$ardeUser->settings[ 'to_twatch' ] = ArdeUserTwatchPlugin::$object->settings[ 'to_twatch' ];
			
			require_once $ardeUser->extPath( 'twatch', 'lib/Global.php' );
			
			$p->pn( 'TraceWatch '.$twatch->version.' Copyright &copy;'.$twatch->getCopyrightYears().' Arash Dejkam</div>' );
		}
	}
?>