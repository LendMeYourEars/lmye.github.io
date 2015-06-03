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
    
	class CountryAdminGeneralRec extends CountryAdminGeneralRecParent {
		
		public function performAction( ArdePrinter $p, $action ) {
			if( $action == 'arde_country_clear_cache' ) {
				global $twatch;
				$res = $twatch->db->query( 'SELECT UNIX_TIMESTAMP()' );
				$r = $twatch->db->fetchRow( $res );
				$twatch->state->set( (int)$r[0], TwatchCountryPlugin::$object->startId + TwatchCountryPlugin::STATE_CACHE_VALID );
				if( !$this->xhtml ) {
					$p->pl( '<successful />' );
				}
			} else {
				return parent::performAction( $p, $action );
			}
		}
		
	}
?>