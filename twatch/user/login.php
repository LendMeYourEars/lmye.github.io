<?php
	require_once dirname(__FILE__).'/lib/PageGlobal.php';
	
	$ardeUser->makeParentClass( 'LoginPage', 'ArdeUserPage' );
	
	class LoginPage extends LoginPageParent {
		const NO_POST_VARS = 0;
		const SUCCESSFUL_LOGIN = 1;
		const INVALID_USER = 2;
		
		protected $status;
		
		protected function getPageTitle() { return 'Login'; }
		
		protected function getToRoot() { return '.'; }
		
		protected function getSelectedTopButton() { return 1; }
		
		protected function init() {
			global $ardeUser;
			parent::init();
			$this->status = self::NO_POST_VARS;
			
			if( isset( $_POST[ 'username' ] ) ) {
				
				$username = ArdeParam::str( $_POST, 'username' );
				$password = ArdeParam::str( $_POST, 'password' );
				
				$ardeUser->db = new ArdeDb( $ardeUser->settings );
				$ardeUser->db->connect();
				
				$users = new ArdeUsers();
				$user = $users->getUserByUnPass( $username, $password );
				
				if( $user === null ) {
					
					$this->status = self::INVALID_USER;
					
				} else {
		
					if( $user->random === null ) {
						$expires = 7*86400;
						$users->renewUserRandom( $user, $expires );
					}
					
					$ardeUser->user = $user;
					
					ArdeUserApp::loadUserData();
					
					setcookie( $ardeUser->settings[ 'cookie_prefix' ], $user->random, time() + $user->randomExpires, $ardeUser->settings[ 'cookie_folder' ], $ardeUser->settings[ 'cookie_domain' ] );
		
					
					$this->status = self::SUCCESSFUL_LOGIN;
					
					if( isset( $_GET[ 'back' ] ) ) {
						ardeRedirect( $_GET[ 'back' ] );
					}
				}
				
			}
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $ardeUser;

			if( $this->status == self::SUCCESSFUL_LOGIN ) {
				$p->pl( '<p><span class="good">'.( $ardeUser->locale->text( 'Successfully Logged In' ) ).'</span></p>' );
			} else {
				$p->pl( '<div class="block">' );
				$p->pl( '<form method="POST">' );
				$p->pl( '	<table class="form" cellpadding="0" cellspacing="0" border="0">' );
				$p->pl( '		<tr>' );
				$p->pl(	'			<td class="head">'.$ardeUser->locale->text( 'username' ).':</td>' );
				$p->pl( '			<td class="tail">' );
				$p->pl( '				<input id="uninput" name="username" />' );
				$p->pl( '				<script type="text/javascript">/*<![CDATA[*/' );
				$p->pl( '					uninput = document.getElementById( "uninput" );' );
				$p->pl( '					uninput.focus();' );
				$p->pl( '				/*]]>*/</script>' );
				$p->pl( '			</td>' );
				$p->pl( '		</tr>' );
				$p->pl( '		<tr><td class="head">'.$ardeUser->locale->text( 'password' ).':</td><td class="tail"><input name="password" type="password" /></td></tr>' );
				$p->pl( '	</table>' );
				$p->pl( '	<p><input type="submit" value="'.$ardeUser->locale->text( 'login' ).'" /></p>' );
				$p->pl( '</form>' );
				if( $this->status == self::INVALID_USER ) {
					$p->pl( '<p><span class="critical">'.( $ardeUser->locale->text( 'Invalid Username or Password' ) ).'</span></p>' );	
				}
				$p->pl( '</div>' );
				
				
			}
		}
	}

	$ardeUser->applyOverrides( array( 'LoginPage' => true ) );

	$page = $ardeUser->makeObject( 'LoginPage' );

	$page->render( $p );
	
?>