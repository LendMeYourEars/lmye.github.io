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

	$phpVersion = phpversion();
	$majorPhpVersion = (int)$phpVersion[0];
	if( $majorPhpVersion < 5 ) die( "PHP version on this server is \"".$phpVersion."\". ArdeUser needs PHP 5 or higher." );

	require_once dirname(__FILE__).'/../lib/PassivePageHead.php';
	
	require_once $ardeUser->path( 'lib/General.php' );
	require_once $ardeUser->path( 'data/DataGlobal.php' );
	require_once $ardeUser->path( 'lib/User.php' );
	require_once $ardeBase->path( 'lib/ArdeJs.php' );
	
	class ArdeUserInstallPage extends ArdeUserPassivePage {

		public $rootUser = null;

		protected function getTitle() {
			return 'Install ArdeUser';
		}
		
		protected function getToRoot() {
			return '..';
		}
		
		public function init() {
			global $ardeUser, $ardeUserProfile;

			$ardeUser->db = new ArdeDb( $ardeUser->settings );
			$ardeUser->db->connect();

			$this->rootUser = ArdeUser::getRootSessionUser();
			
			$url = new ArdeUrlWriter( '../root_session_login.php' );
			$url->setParam( 'back', ardeRequestUri() )->setParam( 'profile', $ardeUserProfile, 'default' );
			
			if( $this->rootUser === null ) {
				ardeRedirect( $url->getUrl() );
				return false;
			}

		}
		
		
		
		public function printBody( ArdePrinter $p ) {
			global $ardeUser;
			if( !isset( $_GET[ 'run' ] ) ) {

				try {
					$phpVersion = phpversion();
				} catch( Exception $e ) {
					$phpVersion = 'UNKNOWN';
				}
				try {
					$mysqlVersion = mysql_get_server_info();
				} catch( Exception $e ) {
					$mysqlVersion = 'UNKNOWN';
				}
				if( isset( $_SERVER[ 'SERVER_SOFTWARE' ] ) ) {
					$serverName = $_SERVER[ 'SERVER_SOFTWARE' ].' - '.php_sapi_name();
				} else {
					$serverName = "UNKNOWN";
				}

				$p->pl( '<p>Your <b>PHP</b> version is <span class="fixed">'.$phpVersion.'</span></p>' );
				$p->pl( '<p>Your <b>MySql</b> server version is <span class="fixed">'.$mysqlVersion.'</span></p>' );
				$p->pl( '<p>Your <b>HTTP</b> server is <span class="fixed">'.$serverName.'</span></p>' );
				$p->pl( '<form method="GET">' );
				$this->printInsideForm( $p );
				$p->pl( '<p><input type="hidden" name="run" value="" /><input type="submit" value="Install ArdeUser" /></p>' );
				$p->pl( '</form>' );
				
			} elseif( isset( $_GET[ 'overwrite' ] ) && !isset( $_POST[ 'confirmed' ] ) ) {
				
				$this->printConfirm( $p );

			} else {
				$this->run( $p );

				$p->pl( '<div class="block" style="text-align:center;font-weight:bold"><p><span class="fixed">ArdeUser Installed Successfully</span></p></div>' );

				$this->rootUser->terminateSession();
			}
		}
		

		public function printInsideForm( ArdePrinter $p ) {
			global $ardeBase, $ardeUser, $ardeUserProfile;

			$profiles = $ardeUser->getProfiles();
			if( count( $profiles ) > 1 ) {
				$p->pl( '<p>Profile: <select name="profile">' );
				foreach( $profiles as $id => $name ) {
					$p->pl( '<option value="'.$id.'"'.( $id == $ardeUser->profile ? 'selected="selected"' : '' ).'>'.$name.'</option>' );
				}
				$p->pl( '</select></p>' );
			}
			$p->pl( '<p><label><input type="checkbox" name="overwrite" /> overwrite if tables already exist</label></p>' );
		}

		public function printConfirm( ArdePrinter $p ) {
			$p->pl( '<form method="POST">' );
			$p->pl( '<p>are you sure? since you have checked overwrite tables, if ArdeUser is already installed on your server all of it\'s data will be lost.</p>' );
			$p->pl( '<p><input type="hidden" name="confirmed" value="" /><input type="submit" value="I\'m Sure" /></p>' );
			$p->pl( '</form>' );
		}

		public function run( ArdePrinter $p ) {
			global $ardeUser, $ardeBase;

			try { set_time_limit( 600 ); } catch( Exception $e ) {}

			if( $ardeUser->settings[ 'disable_output_buffering' ] ) {
				while ( ob_get_level() > 0 ) {
					try { ob_end_flush(); } catch( Exception $e ) {}
				}
				try{ ob_implicit_flush(); } catch( Exception $e ) {}
			}

			require_once $ardeUser->path( 'lib/Installer.php' );
			$installer = new ArdeUserInstaller();
			$installer->install( $p, isset( $_GET[ 'overwrite' ] ) );

		}


	}

	$ardeUser->applyOverrides( array( 'ArdeUserInstallPage' => true ) );

	$page = $ardeUser->makeObject( 'ArdeUserInstallPage' );

	$page->render( $p );





?>