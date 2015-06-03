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

	class ArdeUserTwatchPlugin extends ArdeUserPlugin {
		
		public function init() {
			$setting = array();
			include $this->path( 'settings.php' );
			$this->settings = $settings;
		}
		
		public function getOverrides() {
			return array( 
				 'ArdeUserPassivePage' => new ArdeClassOverride( 'TwatchUserPassivePage', $this->path( 'PassivePage.php' ) )
				,'LoginPage' => new ArdeClassOverride( 'TwatchUserLoginPage', $this->path( 'LoginPage.php' ) )
			);
		}
		
		public function getName() {
			return 'TraceWatch';
		}
		
		public function getVersion() {
			return '0.100';
		}
		
		public function afterUserDeleted( $userId ) {
			global $ardeUser, $twatch;
			
			$ardeUser->settings[ 'to_twatch' ] = $this->settings[ 'to_twatch' ];
			
			require_once $ardeUser->extPath( 'twatch', 'lib/Global.php' );
			require_once $twatch->path( 'lib/General.php' );
			$twatch->db = new ArdeDb( $twatch->settings );
			$twatch->db->connect();

			$twatch->userData = new TwatchUserData( null, null );
			$twatch->userData->clearUser( $userId );
		}
		
	}
	
	$pluginObject = ArdeUserTwatchPlugin::$object = new ArdeUserTwatchPlugin();
?>