<?php
	require_once dirname(__FILE__).'/lib/PageGlobal.php';
	
	$ardeUser->makeParentClass( 'LogoutPage', 'ArdeUserPage' );
	
	class LogoutPage extends LogoutPageParent {
		
		protected $loggedIn;
		
		protected function getPageTitle() { return 'Logout'; }
		
		protected function getToRoot() { return '.'; }
		
		protected function getSelectedTopButton() {
			return 2;
		}
		
		protected function init() {
			global $ardeUser;
			parent::init();
			$this->loggedIn = false;
	
			$ardeUser->db = new ArdeDb( $ardeUser->settings );
			$ardeUser->db->connect();
			
			$users = new ArdeUsers();
			
			if( $ardeUser->user->id !== ArdeUser::USER_PUBLIC ) {
				$this->loggedIn = true;
				
				
				$users->removeUserRandom( $ardeUser->user );
				
				$ardeUser->user = new ArdeUserPublic();
				ArdeUserApp::loadUserData();
				
				setcookie( $ardeUser->settings[ 'cookie_prefix' ], '', time() - 7*86400, $ardeUser->settings[ 'cookie_folder' ], $ardeUser->settings[ 'cookie_domain' ] );
			}
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $ardeUser;
			if( $this->loggedIn ) {
				$p->pl( '<p><span class="good">'.$ardeUser->locale->text( 'You successfully logged out' ).'</span></p>' );
			} else {
				$p->pl( '<p><span class="fixed">'.$ardeUser->locale->text( 'You are not logged in' ).'</span></p>' );
			}	
		}
	}
	
	$ardeUser->applyOverrides( array( 'LogoutPage' => true ) );

	$page = $ardeUser->makeObject( 'LogoutPage' );

	$page->render( $p );
	
?>