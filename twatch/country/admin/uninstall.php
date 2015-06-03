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

	require_once $ardeCountry->extPath( 'user', 'lib/Global.php' );
	require_once $ardeUser->path( 'lib/User.php' );

	class ArdeCountryUninstallPage extends ArdeCountryPassivePage {
		
		protected $rootUser;
		
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Uninstall ArdeCountry'; }
		
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
				$p->pl( '<p><input type="hidden" name="run" value="" /><input type="submit" value="Uninstall IP-to-Country" /></p>' );
				$p->pl( '</form>' );
			} else {
			
				$ardeCountry->db = new ArdeDb( $ardeCountry->settings );
				$ardeCountry->db->connect();
				
				require_once $ardeCountry->path( 'lib/Installer.php' );
				$installer = new ArdeCountryInstaller();
				$installer->uninstall( $p );
				
				$this->rootUser->terminateSession();
			}
		}
	}
	
	$page = new ArdeCountryUninstallPage();

	$page->render( $p );

?>