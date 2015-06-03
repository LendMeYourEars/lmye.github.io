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
	
	class TwatchUninstallPage extends TwatchPassivePage {
		
		public $rootUser;
		
		protected function getTitle() { return 'Uninstall TraceWatch'; }
		
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

		}
		
		protected function printBody( ArdePrinter $p ) {
			$p->setMutedErrors( false );
			
			if( !isset( $_GET[ 'run' ] ) ) {
				
				$p->pl( '<form method="GET">' );
				$this->printInsideForm( $p );
				$p->pl( '<p><input type="hidden" name="run" value="" /><input type="submit" value="Uninstall TraceWatch" /></p>' );
				$p->pl( '</form>' );
				
			} elseif( !isset( $_POST[ 'confirmed' ] ) ) {
				
				$this->printConfirm( $p );
				
			} else {
				$this->run( $p );
				
				$p->pl( '<div class="block" style="text-align:center"><p><span class="fixed">TraceWatch Uninstalled Successfully</span></p></div>' );
				
				$this->rootUser->terminateSession();
				
			}
			

		}
		
		
		
		public function printInsideForm( ArdePrinter $p ) {
			global $twatch, $twatchProfile;
			$profiles = $twatch->getProfiles();
			if( count( $profiles ) > 1 ) {
				$p->pl( '<p>Profile: <select name="profile">' );
				foreach( $profiles as $id => $name ) {
					$p->pl( '<option value="'.$id.'"'.( $id == $twatch->profile ? 'selected="selected"' : '' ).'>'.$name.'</option>' );
				}
				$p->pl( '</select></p>' );
			}
			$p->pl( '<p><label><input type="checkbox" name="uninstall_user" /> uninstall user manager</label></p>' );
			
		}
		
		public function printConfirm( ArdePrinter $p ) {
			$p->pl( '<form method="POST">' );
			$p->pl( '<p>are you sure? this will completely remove tracewatch from your website.</p>' );
			$p->pl( '<p><input type="hidden" name="confirmed" value="" /><input type="submit" value="I\'m Sure" /></p>' );
			$p->pl( '</form>' );
		}
		
		public function run( ArdePrinter $p ) {
			global $twatch, $ardeUser, $ardeBase;
			

			require_once $twatch->path( 'lib/Installer.php' );
			require_once $twatch->path( 'lib/General.php' );
			
			$twatch->db = new ArdeDb( $twatch->settings );
			$twatch->db->connect();
			
			$twatch->now = new TwatchTime();
			
			$twatch->config = new TwatchConfig( $twatch->db );
			$twatch->state = new TwatchState( $twatch->db );
			
			$twatch->config->addDefaults( TwatchConfig::$defaultProperties );
			$twatch->config->applyAllChanges();
			
			$twatch->state->addDefaults( TwatchState::$defaultProperties );
			$twatch->state->addDefaults( TwatchState::$extraDefaults );
			$twatch->state->applyAllChanges();
			
			$installer = new TwatchInstaller();
			$installer->uninstall( $p, isset( $_GET[ 'keep_config' ] ) );
			
			if( isset( $_GET[ 'uninstall_user' ] ) ) {
				require_once $ardeUser->path( 'lib/Installer.php' );
				$installer = new ArdeUserInstaller();
				$installer->uninstall( $p );
			}
			
		}
	}
	
	$twatch->applyOverrides( array( 'TwatchUninstallPage' => true ) );
	
	$page = $twatch->makeObject( 'TwatchUninstallPage' );

	$page->render( $p );
	
?>