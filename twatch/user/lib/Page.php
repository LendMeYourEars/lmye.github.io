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

	require_once $ardeUser->path( 'lib/PassivePage.php' );
	
	$ardeUser->makeParentClass( 'ArdeUserPage', 'ArdeUserPassivePage' );
	
	class ArdeUserPage extends ArdeUserPageParent {
		
		
		private $configData;
		
		public $selectedLocaleId;
		
		protected function isAdminPage() { return false; }
		
		public function __construct() {
			global $ardeUser;
			require_once $ardeUser->path( 'data/DataGlobal.php' );
			$this->configData = array( ArdeUserConfig::$defaultProperties );
			parent::__construct();
		}
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			global $ardeUser;
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="'.$ardeUser->url( $this->getToRoot(), 'js/Global.js' ).'"></script>' );
		}
		
		protected function hasLangCont() {
			return true;
		}
		
		protected function isRightToLeft() {
			global $ardeUser;
			return $ardeUser->locale->rightToLeft;
		}
		
		protected function getPageTitle() {
			return '';
		}
		
		protected function getTitle() {
			global $ardeUser;
			return $ardeUser->locale->text( $this->getPageTitle() );
		}
		
		protected function getSelectedTopButton() { return 0; }
		
		protected function getTopButtons() {
			global $ardeUser;
			$topButtons = array();
			
			$url = new ArdeUrlWriter();
			$url->setParam( 'lang', $this->selectedLocaleId, $ardeUser->config->get( ArdeUserConfig::DEFAULT_LANG ) );
			$url->setParam( 'profile', $ardeUser->profile, 'default' );
			
			$i = 0;
			$url->setAddress( $ardeUser->url( $this->getToRoot(), '' ) );
			$topButtons[ $i++ ] = array( $ardeUser->locale->text( 'Your Account' ), $url->getUrl() );

			if( $ardeUser->user->id == ArdeUser::USER_PUBLIC || $this instanceof LoginPage ) {
				$url->setAddress( $ardeUser->url( $this->getToRoot(), 'login.php' ) );
				$topButtons[ $i++ ] = array( $ardeUser->locale->text( 'Login' ), $url->getUrl() );
			}
			if( $ardeUser->user->id != ArdeUser::USER_PUBLIC || $this instanceof LogoutPage ) {
				$url->setAddress( $ardeUser->url( $this->getToRoot(), 'logout.php' ) );
				$topButtons[ $i++ ] = array( $ardeUser->locale->text( 'Logout' ), $url->getUrl() );				
			}

			if( $ardeUser->user->hasPermission( ArdeUserData::ADMINISTRATE ) ) {
				$url->setAddress( $ardeUser->url( $this->getToRoot(), 'admin/' ) );
				$topButtons[ $i++ ] = array( $ardeUser->locale->text( 'Administrate' ), $url->getUrl() );
			}
				
			return $topButtons;
		}
		
		protected function init() {
			global $ardeUser;

			$ardeUser->db = new ArdeDb( $ardeUser->settings );

			$ardeUser->db->connect();
			
			$ardeUser->config = new ArdeUserConfig( $ardeUser->db );
			
			foreach( $this->configData as $k => $v ) {
				$ardeUser->config->addDefaults( $this->configData[ $k ] );
			}
			$ardeUser->config->applyAllChanges();
			
			ArdeUserApp::initUser();
			
			if( isset( $_GET[ 'lang' ] ) && $ardeUser->localeExists( $_GET[ 'lang' ] ) ) {
				$this->selectedLocaleId = $_GET[ 'lang' ];
			} else {
				$this->selectedLocaleId = $ardeUser->config->get( ArdeUserConfig::DEFAULT_LANG );
			}
			$this->loadLocale();
			
			if( $this->isAdminPage() && !$ardeUser->user->hasPermission( ArdeUserData::ADMINISTRATE ) ) {
				$url = new ArdeUrlWriter( 'login.php' );
				$url->setParam( 'back', ardeRequestUri() )->setParam( 'profile', $ardeUser->profile, 'default' );
				
				if( $ardeUser->user->id == ArdeUser::USER_PUBLIC ) {
					ardeRedirect( $ardeUser->url( $this->getToRoot(), $url->getUrl() ) );
				} else {
					throw new ArdeUserError( 'You do not have permission.' );
				}
				return false;
			}
		}
				
		protected function loadLocale() {
			global $ardeUser;
			$ardeUser->loadLocale( $this->selectedLocaleId );
		}
		
		protected function printInLangCont( ArdePrinter $p ) {
			global $ardeUser;
			$p->pl( 'Language: <select id="lang_select">' );
			$url = ArdeUrlWriter::getCurrentRelative();
			$defaultId = $ardeUser->config->get( ArdeUserConfig::DEFAULT_LANG );

			foreach( $ardeUser->getLocaleIds() as $id ) {
				$url->setParam( 'lang', $id, $defaultId );
				$p->pl( '<option value="'.$url->getUrl().'"'.($id == $this->selectedLocaleId?' selected="selected"':'').'>'.$id.'</option>' );
			}
			$p->pl( '</select>' );
			$p->pl( '<script type="text/javascript">activateLinkSelect( "lang_select" );</script>' );
		}
		
		
		
	}
	
?>