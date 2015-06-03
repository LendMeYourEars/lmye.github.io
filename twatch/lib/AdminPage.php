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

	$twatch->makeParentClass( 'TwatchAdminPage', 'TwatchPage' );
	
	class TwatchAdminPage extends TwatchAdminPageParent {

		protected $selectedUser;
		
		protected $configMode = false;
		
		protected $selectedUserExtraDef = null;
		
		protected function init() {
			global $ardeUser, $twatch;
			
			$this->adminPage = true;
			parent::init();
			
			if( $ardeUser->user->hasPermission( TwatchUserData::CONFIG ) && !$ardeUser->user->hasPermission( TwatchUserData::VIEW_ADMIN ) ) {
				$this->configMode = true;
			}
			
			require_once $ardeUser->path( 'lib/UserAdmin.php' );
			require_once $twatch->path( 'lib/AdminGeneral.php' );
			
			$this->selectedUser = getSelectedUser( true, $this->selectedUserExtraDef );
			
		}
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			global $twatch;
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="'.$twatch->url( $this->getToRoot(), 'js/AdminGlobal.js' ).'"></script>' );
			$p->pl( '<script type="text/javascript">', 1 );
			$this->initJsPage( $p );
			$p->rel();
			$p->pl( '</script>' );
		}
		
		protected function getSelectedTopButton() { return 3; }
		
		public function initJsPage( ArdePrinter $p, $texts = array() ) {
			global $ardeUser;
			parent::initJsPage( $p, $texts );
			$p->pl( 'User.PublicUserId = '.ArdeUser::USER_PUBLIC.';' );
			$p->pl( 'User.PublicGroupId = '.ArdeUserGroup::GROUP_PUBLIC.';' );
			$p->pl( 'User.AdminGroupId = '.ArdeUserGroup::GROUP_ADMIN.';' );
			$p->pl( 'User.RootUserId = '.ArdeUser::USER_ROOT.';' );
			$p->pl( 'selectedUser = '.$this->selectedUser->baseAdminJsObject().';' );
			$p->pl( 'twatchUser = '.$ardeUser->user->baseAdminJsObject().';' );
			$p->pl( 'configMode = '.ArdeJs::bool( $this->configMode ).';' );
		}

		protected function printWebsiteSelectOptions( ArdePrinter $p ) {
			global $twatch, $ardeUser;
			$url = ArdeUrlWriter::getCurrentRelative();
			$url->removeParam( 'website' );
			$p->pl( '<option value="'.$url->getUrl().'" '.( !$this->websiteId ? 'selected="selected"' : '' ).'>All Websites</option>' );
			parent::printWebsiteSelectOptions( $p );
		}
		
		protected function getDefaultWebsiteId() {
			return 0;
		}
		

		protected function loadLocale() {
			global $twatch;
			if( $this->selectedLocaleId != 'English' ) {
				$twatch->loadLocale( 'English' );
			}
		}


		protected function printHeader( ArdePrinter $p ) {
			global $ardeUser;
			parent::printHeader( $p );
			
			if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_ADMIN ) ) {
				$p->pl( '<div class="user_select">' );
				$p->pl( '	<b>Selected User:</b> ' );
				
				$p->pl( '	<script type="text/javascript">new UserSelect( '.$this->selectedUser->baseJsObject().' ).insert();</script>' );
				$p->pl( '</div>' );
			}
		}
		
		protected function getLeftButtons() {
			global $twatch, $ardeUser;

			$url = new ArdeUrlWriter();
			$this->setUrlParams( $url, true );
			if( $ardeUser->user->hasPermission( TwatchUserData::ADMINISTRATE ) ) {
				if( $this->selectedUser instanceof ArdeUserGroup ) {
					$url->removeParam( 'user' );
					$url->setParam( 'group', $this->selectedUser->id, 0 );
				} else {
					$url->removeParam( 'group' );
					$url->setParam( 'user', $this->selectedUser->id );
				}
			} else {
				$url->removeParam( 'user' );
				$url->removeParam( 'group' );
			}
			
			$leftButtons = array();
			$leftButtons[ 'main' ] = array( 'Main', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/' ) ) );
			$leftButtons[ 'websites' ] = array( 'Websites',$url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/websites.php' ) ) );
			$leftButtons[ 'stats_pages' ] = array( 'Stats Pages', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/stats_pages.php' ) ) );
			$leftButtons[ 'latest' ] = array( 'Latest Visitors', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/latest.php' ) ) );
			$leftButtons[ 'path_analyzer' ] = array( 'Path Analyzer', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/path_analyzer.php' ) ) );
			if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_ADMIN ) ) {
				$leftButtons[ 'counters' ] = array( 'Counters', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/counters.php' ) ) );
				$leftButtons[ 'entities' ] = array( 'Entities', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/entities.php' ) ) );
				$leftButtons[ 'user_agents' ] = array( 'User Agents', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/user_agents.php' ) ) );
				$leftButtons[ 'search_engines' ] = array( 'Web Areas', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/search_engines.php' ) ) );
				$leftButtons[ 'visitor_types' ] = array( 'Visitor Types', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/visitor_types.php' ) ) );
				$leftButtons[ 'user_groups' ] = array( 'Users and Groups', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/user_groups.php' ) ) );
			}
			if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_ERRORS ) ) {
				$leftButtons[ 'errors' ] = array( 'Errors', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/errors.php' ) ) );
			}
			if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_ADMIN ) ) {
				$leftButtons[ 'install' ] = array( 'Install', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/install.php' ) ) );
				$leftButtons[ 'uninstall' ] = array( 'Uninstall', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/uninstall.php' ) ) );
				$leftButtons[ 'admin_cookie' ] = array( 'Admin Cookie', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/admin_cookie.php' ) ) );
				$leftButtons[ 'comments' ] = array( 'Comments', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/comments.php' ) ) );
				$leftButtons[ 'import' ] = array( 'Import', $url->getAddressUrl( $twatch->url( $this->getToRoot(), 'admin/import.php' ) ) );
			}
			return $leftButtons;
		}
	}

	$twatch->applyOverrides( array( 'TwatchAdminPage' => true ) );
?>