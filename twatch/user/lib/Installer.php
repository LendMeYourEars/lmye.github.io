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
    
	global $ardeUser;
	
	require_once $ardeUser->path( 'lib/User.php' );
	require_once $ardeUser->path( 'data/DataGlobal.php' );
	
	class ArdeUserInstaller {
		
		public function install( ArdePrinter $p, $overwrite ) {
			global $ardeUser;

			$p->pn( '<p>Installing user manager configuration... ' );
			$ardeUser->config = new ArdeUserConfig( $ardeUser->db );
			$ardeUser->config->install( $overwrite );
			$ardeUser->config->addDefaults( ArdeUserConfig::$defaultProperties );
			$ardeUser->config->applyAllChanges();
			$ardeUser->config->set( $ardeUser->makeInstanceId(), ArdeUserConfig::INSTANCE_ID );
			$def = array( ArdeUserConfig::PLUGIN_VERSIONS => array() );
			$ardeUser->config->addDefaults( $def );
			$ardeUser->config->applyAllChanges();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>installing user manager errors log... ' );
			$errorReporter = new ArdeUserErrorLogger();
			$errorReporter->install( $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>installing user manager user data... ' );
			$userData = new ArdeUserData( null );
			$userData->install( $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>Installing user manager... ' );
			$ardeUsers = new ArdeUsers();
			$ardeUsers->install( $overwrite );
			$p->pl( '<span class="good">Successful</span></p>' );
			
			$p->pl( '<p>Setting user manager version... ' );
			$ardeUser->config->set( $ardeUser->version, ArdeUserConfig::VERSION );
			$p->pl( '<span class="good">successful</span></p>' );
		}
		
		
		public function uninstall( ArdePrinter $p ) {
			global $ardeUser;
			
			$p->pn( '<p>uninstalling user manager user data... ' );
			$userData = new ArdeUserData( null );
			$userData->uninstall();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>Uninstalling user manager... ' );
			$ardeUsers = new ArdeUsers();
			$ardeUsers->uninstall();
			$p->pl( '<span class="good">Successful</span></p>' );
			
			$p->pn( '<p>uninstalling errors log... ' );
			$errorReporter = new ArdeUserErrorLogger();
			$errorReporter->uninstall();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>Uninstalling user manager configuration... ' );
			$ardeUser->config = new ArdeUserConfig( $ardeUser->db );
			$ardeUser->config->uninstall();
			$p->pl( '<span class="good">Successful</span></p>' );
			
		}
		
		public function update( ArdePrinter $p ) {
			global $ardeUser;
			
			if( !$ardeUser->db->tableExists( 'c' ) ) {
				$p->pl( '<p><b>Reinstalling user manager...</b></p>' );
				$this->install( $p, true );
				$p->pl( '<div class="block" style="text-align:center;font-weight:bold"><p><span class="fixed">User Manager Successfully Updated</span></p></div>' );
			} else {
				$p->pl( '<div class="block" style="text-align:center;font-weight:bold"><p><span class="fixed">Nothing to do</span></p></div>' );
			}
		}
		
	}
?>