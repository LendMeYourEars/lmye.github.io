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
	if( $majorPhpVersion < 5 ) die( "PHP version on this server is \"".$phpVersion."\". TraceWatch 0.3 needs PHP 5 or higher. You can't install TraceWatch 0.3x on this server. Upgrade your server or install TraceWatch 0.234." );

	require_once dirname(__FILE__).'/../lib/PassivePageHead.php';

	class TwatchInstallPage extends TwatchPassivePage {

		public $rootUser = null;

		protected function getTitle() { return 'Install TraceWatch'; }
		
		protected function getToRoot() { return '..'; }
		
		public function init() {
			global $ardeBase, $twatch, $ardeUser, $ardeUserProfile;

			$ardeUserProfile = $twatch->settings[ 'user_profile' ];
			require_once $twatch->extPath( 'user', 'lib/Global.php' );
			require_once $ardeBase->path( 'lib/ArdeJs.php' );
			require_once $ardeUser->path( 'lib/User.php' );
			require_once $twatch->path( 'lib/TimeZone.php' );
			require_once $twatch->path( 'data/DataGlobal.php' );

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
		
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			global $twatch;
			parent::printInHtmlHead( $p );
			if( !isset( $_GET[ 'run' ] ) ) {
				$p->pl( '<script type="text/javascript" src="'.$twatch->baseUrl( $this->getToRoot(), 'js/ArdeClass.js' ).'"></script>' );
				$p->pl( '<script type="text/javascript" src="'.$twatch->baseUrl( $this->getToRoot(), 'js/ArdeRequest.js' ).'"></script>' );
				$p->pl( '<script type="text/javascript" src="'.$twatch->baseUrl( $this->getToRoot(), 'js/ArdeComponent.js' ).'"></script>' );
				$p->pl( '<script type="text/javascript" src="'.$twatch->url( $this->getToRoot(), 'js/Global.js' ).'"></script>' );
			}
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch;
			if( !isset( $_GET[ 'run' ] ) ) {

				$twatch->config = new TwatchConfig( null );
				$twatch->config->addDefaults( TwatchConfig::$defaultProperties );
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
				$p->pl( '<p><input type="hidden" name="run" value="" /><input type="submit" value="Install TraceWatch" /></p>' );
				$p->pl( '</form>' );
			} elseif( isset( $_GET[ 'overwrite' ] ) && !isset( $_POST[ 'confirmed' ] ) ) {
				$this->printConfirm( $p );

			} else {

				$this->run( $p );

				$p->pl( '<div class="block" style="text-align:center;font-weight:bold"><p><span class="fixed">TraceWatch Installed Successfully</span></p></div>' );

				$this->rootUser->terminateSession();
			}
		}
		

		

		public function printInsideForm( ArdePrinter $p ) {
			global $twatch, $ardeBase, $twatchProfile;

			$profiles = $twatch->getProfiles();
			if( count( $profiles ) > 1 ) {
				$p->pl( '<p>Profile: <select name="profile">' );
				foreach( $profiles as $id => $name ) {
					$p->pl( '<option value="'.$id.'"'.( $id == $twatch->profile ? 'selected="selected"' : '' ).'>'.$name.'</option>' );
				}
				$p->pl( '</select></p>' );
			}
			$p->pl( '<p><label><input type="checkbox" name="install_user" checked="true" /> install user manager</label></p>' );
			$p->pl( '<p><label><input type="checkbox" name="start_counters" checked="true" /> start counters</label></p>' );
			$p->pl( '<p><label><input type="checkbox" name="overwrite" /> overwrite if tables already exist</label></p>' );
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
			$tz = TwatchTimeZone::fromConfig();
			$p->pl( '	timeZone = '.$tz->jsObject( 'tz_', true ).';' );
			$p->pl( '	timeZone.computerClicked();' );
			$p->pl( '	timeZone.insert();' );
			$p->pl( '/*]]>*/</script>' );



		}

		public function printConfirm( ArdePrinter $p ) {


			$p->pl( '<form method="POST">' );
			$p->pl( '<p>are you sure? since you have checked overwrite tables, if TwaceWatch is already installed on your server all of it\'s data will be lost.</p>' );
			$p->pl( '<p><input type="hidden" name="confirmed" value="" /><input type="submit" value="I\'m Sure" /></p>' );
			$p->pl( '</form>' );
		}

		public function run( ArdePrinter $p ) {
			global $twatch, $ardeUser, $ardeBase;

			try { set_time_limit( 600 ); } catch( Exception $e ) {}

			if( $twatch->settings[ 'disable_output_buffering' ] ) {
				while ( ob_get_level() > 0 ) {
					try { ob_end_flush(); } catch( Exception $e ) {}
				}
				try{ ob_implicit_flush(); } catch( Exception $e ) {}
			}

			if( isset( $_GET[ 'install_user' ] ) ) {
				require_once $ardeUser->path( 'lib/Installer.php' );
				$installer = new ArdeUserInstaller();
				$installer->install( $p, isset( $_GET[ 'overwrite' ] ) );
			}

			require_once $twatch->path( 'lib/Installer.php' );
			require_once $twatch->path( 'lib/General.php' );

			$twatch->db = new ArdeDb( $twatch->settings );
			$twatch->db->connect();

			$twatch->config = new TwatchConfig( $twatch->db );
			$twatch->state = new TwatchState( $twatch->db );

			$installer = new TwatchInstaller();


			$tz = TwatchTimeZone::fromParams( $_GET, 'tz_' );



			$installer->install( $p, isset( $_GET[ 'keep_config' ] ), isset( $_GET[ 'overwrite' ] ), isset( $_GET[ 'start_counters' ] ), $tz, isset( $_GET[ 'use_iptc'] ) );

		}


	}

	$twatch->applyOverrides( array( 'TwatchInstallPage' => true ) );

	$page = $twatch->makeObject( 'TwatchInstallPage' );

	$page->render( $p );





?>