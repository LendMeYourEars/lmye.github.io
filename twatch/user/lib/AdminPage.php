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

	$ardeUser->makeParentClass( 'ArdeUserAdminPage', 'ArdeUserPage' );

	class ArdeUserAdminPage extends ArdeUserAdminPageParent {

		protected function isAdminPage() { return true; }
		
		protected function init() {
			parent::init();
		}

		protected function loadLocale() {
			global $ardeUser;
			if( $this->selectedLocaleId != 'English' ) {
				$ardeUser->loadLocale( 'English' );
			}
		}
		
		protected function getSelectedTopButton() { return 2; }
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			global $ardeUser;
			parent::printInHtmlHead( $p );
			$this->printBaseJsIncludes( $p );
		}
		
		protected function printBaseJsIncludes( ArdePrinter $p ) {
			global $ardeUser;
			$p->pl( '<script type="text/javascript" src="'.$ardeUser->baseUrl( $this->getToRoot(), 'js/ArdeClass.js' ).'"></script>' );
			$p->pl( '<script type="text/javascript" src="'.$ardeUser->baseUrl( $this->getToRoot(), 'js/ArdeRequest.js' ).'"></script>' );
			$p->pl( '<script type="text/javascript" src="'.$ardeUser->baseUrl( $this->getToRoot(), 'js/ArdeComponent.js' ).'"></script>' );
		}
		
		public function initJsPage( ArdePrinter $p, $texts = array() ) {
			global $ardeUser;
			$p->pl( "baseUrl = '".$ardeUser->baseUrl( $this->getToRoot(), '/' )."';" );
			$p->pl( "ardeUserUrl = '".$ardeUser->url( $this->getToRoot(), '/' )."';" );
			$p->pl( "ardeUserProfile = '".ArdeJs::escape( $ardeUser->profile )."';" );
			$p->pl( 'ardeLocale = '.$ardeUser->locale->jsObject( $texts, $ardeUser->config->get( ArdeUserConfig::DEFAULT_LANG ) ).";" );
		}
		
		protected function getLeftButtons() {
			global $ardeUser;

			$url = new ArdeUrlWriter();
			$url->setParam( 'lang', $this->selectedLocaleId, $ardeUser->config->get( ArdeUserConfig::DEFAULT_LANG ) );
			$url->setParam( 'profile', $ardeUser->profile, 'default' );
			$leftButtons = array(
				 array( 'Main', $url->getAddressUrl( $ardeUser->url( $this->getToRoot(), 'admin/' ) ) )
			);
			return $leftButtons;
		}
	}

	$ardeUser->applyOverrides( array( 'ArdeUserAdminPage' => true ) );
?>