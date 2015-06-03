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
    
	require_once dirname(__FILE__).'/../lib/PassivePageHead.php';
	
	class TwatchLoggerTestPage extends TwatchPassivePage {
		
		public $rootUser;
		
		protected function getTitle() { return 'Test TraceWatch Logger'; }
		
		protected function getToRoot() { return '..'; }
		
		protected function init() {
			global $ardeBase, $twatch, $ardeUser, $ardeUserProfile;
			
			$ardeUserProfile = $twatch->settings[ 'user_profile' ];
			require_once $twatch->extPath( 'user', 'lib/Global.php' );
			require_once $ardeUser->path( 'lib/User.php' );
			require_once $ardeBase->path( 'lib/ArdeJs.php' );
			
			$ardeUser->db = new ArdeDb( $ardeUser->settings );
			$ardeUser->db->connect();
		
			$this->rootUser = ArdeUser::getRootSessionUser();
			$url = new ArdeUrlWriter( 'root_session_login.php' );
			$url->setParam( 'back', ardeRequestUri() )->setParam( 'profile', $ardeUserProfile, 'default' );
			if( $this->rootUser === null ) {
				ardeRedirect( $twatch->extUrl( $this->getToRoot(), 'user', $url->getUrl() ) );
				return false;
			}

			if( isset( $_POST[ 'run' ] ) ) {
				require_once $twatch->path( 'api/LogRequest.php' );
				
				twatchLogRequest( true, 1, false, true, false, true );
			}
		}
		

		protected function printBody( ArdePrinter $p ) {
			$p->setMutedErrors( false );
			
			if( !isset( $_POST[ 'run' ] ) ) {
				
				$p->pl( '<form method="POST">' );
				$p->pl( '<p><input type="hidden" name="run" value="true" /><input type="submit" value="Make a Test Request" /></p>' );
				$p->pl( '</form>' );
				
			} else {
				
				$p->pl( '<div class="block" style="text-align:center"><p><span class="fixed">Test Completed.</span></p></div>' );
				
				$this->rootUser->terminateSession();
				
			}
			

		}
		

	}
	
	$twatch->applyOverrides( array( 'TwatchLoggerTestPage' => true ) );
	
	$page = $twatch->makeObject( 'TwatchLoggerTestPage' );

	$page->render( $p );
	
?>