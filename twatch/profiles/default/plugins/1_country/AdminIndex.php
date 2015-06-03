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
		
	class CountryAdminIndexPage extends CountryAdminIndexPageParent {
		protected function printContents( ArdePrinter $p ) {
			global $ardeUser;
			parent::printContents( $p );
			if( $ardeUser->user->hasPermission( TwatchUserData::ADMINISTRATE ) ) {
				$p->pl( '<hr />' );
				$p->pl( '<p>' );
				$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
				$p->pl( "	clearCouCacheButton = new ArdeRequestButton( 'Clear IP-to-Country Cache' );" );
				$p->pl( '	clearCouCacheButton.insert();' );
				$p->pl( '	clearCouCacheButton.onclick = function() {' );
				$p->pl( "		clearCouCacheButton.request( twatchFullUrl( 'rec/rec_general.php' ), 'a=arde_country_clear_cache' );" );
				$p->pl( '	};' );
				$p->pl( '/*]]>*/</script>' );
			}
		}
	}
?>