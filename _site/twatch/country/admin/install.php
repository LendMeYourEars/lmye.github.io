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

	$updatePage = true;
	
	$phpVersion = phpversion();
	$majorPhpVersion = (int)$phpVersion[0];
	if( $majorPhpVersion < 5 ) die( "PHP version on this server is \"".$phpVersion."\". ArdeCountry needs PHP 5 or higher." );

	require_once dirname(__FILE__).'/../lib/PassivePageHead.php';

	require_once $ardeCountry->extPath( 'user', 'lib/Global.php' );
	require_once $ardeCountry->path( 'lib/Country.php' );
	require_once $ardeUser->path( 'lib/User.php' );

	class ArdeCountryInstallPage extends ArdeCountryPassivePage {
		
		protected $rootUser;
		
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Install ArdeCountry'; }
		
		protected function init() {
			global $ardeUser, $ardeCountry;
			$ardeUser->db = new ArdeDb( $ardeUser->settings );
			$ardeUser->db->connect();
		
			$this->rootUser = ArdeUser::getRootSessionUser();
			if( $this->rootUser === null ) {
				ardeRedirect( $ardeCountry->extUrl( '..', 'user', 'root_session_login.php?back='.urlencode( ardeRequestUri() ) ) );
				return false;
			}
			
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $ardeCountry;
			
			$p->setMutedErrors( false );
			if( !isset( $_GET[ 'run' ] ) ) {
				$p->pl( '<form method="GET">' );
				$profiles = $ardeCountry->getProfiles();
				if( count( $profiles ) > 1 ) {
					$p->pl( '<p>Profile: <select name="profile">' );
					foreach( $profiles as $id => $name ) {
						$p->pl( '<option value="'.$id.'"'.( $id == $ardeCountry->profile ? 'selected="selected"' : '' ).'>'.$name.'</option>' );
					}
					$p->pl( '</select></p>' );
				}
				
				$ardeCountry->printSourceSelector( $p );
				$p->pl( '<p><label><input type="checkbox" name="overwrite" /> overwrite if tables already exist</label></p>' );
				$p->pl( '<p><input type="hidden" name="run" value="" /><input type="submit" value="Install IP-to-Country" /></p>' );
				$p->pl( '</form>' );
			} else {
				try { set_time_limit( 600 ); } catch( Exception $e ) {}
				if( $ardeCountry->settings[ 'disable_output_buffering' ] ) {
					while ( ob_get_level() > 0 ) {
						try { ob_end_flush(); } catch( Exception $e ) {}
					}
					try{ ob_implicit_flush(); } catch( Exception $e ) {}
				}
				$ardeCountry->db = new ArdeDb( $ardeCountry->settings );
				$ardeCountry->db->connect();
		
				require_once $ardeCountry->path( 'lib/Installer.php' );
				$installer = new ArdeCountryInstaller();
				
				if( !isset( $_GET[ 'iptc_source' ] ) ) throw new ArdeUserError( 'source not specified' );
				$source = (int)$_GET[ 'iptc_source' ];
				
				$installer->install( $p, $source, isset( $_GET[ 'overwrite' ] ) );
		
				$this->rootUser->terminateSession();
			}
		}
	}
	
	$page = new ArdeCountryInstallPage();

	$page->render( $p );

?>