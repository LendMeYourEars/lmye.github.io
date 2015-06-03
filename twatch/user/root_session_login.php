<?php
	$updatePage = true;
	require_once dirname(__FILE__).'/lib/PassivePageHead.php';
	require_once $ardeUser->path( 'lib/User.php' );
	require_once $ardeBase->path( 'lib/ArdeJs.php' );
	
	$ardeUser->makeParentClass( 'RootLoginPage', 'ArdeUserPassivePage' );
	
	class RootLoginPage extends RootLoginPageParent {
		const NO_POST_VARS = 0;
		const SUCCESSFUL_LOGIN = 1;
		const INVALID_USER = 2;
		
		protected $status;
		
		protected function getTitle() { return 'Root Login'; }
		
		protected function getToRoot() { return '.'; }
		
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
				
				$user = $users->getRootByUnPass( $username, $password );
				
				if( $user === null ) {
					
					$this->status = self::INVALID_USER;
					
				} else {
				
					$random = $users->makeRootSessionRandom();
					
					setcookie( $ardeUser->settings[ 'cookie_prefix' ].'_root_session', $random, null, $ardeUser->settings[ 'cookie_folder' ], $ardeUser->settings[ 'cookie_domain' ] );
					
					$this->status = self::SUCCESSFUL_LOGIN;
					
					if( isset( $_GET[ 'back' ] ) ) {
						//echo $_GET[ 'back' ];
						ardeRedirect( $_GET[ 'back' ] );
					}
				}
				
			}
		}
		
		protected function printBody( ArdePrinter $p ) {
			if( $this->status == self::SUCCESSFUL_LOGIN ) {
					$p->pl( '<p><span class="good">Successfully Logged In</span></p>' );
			} else {
				$p->pl( '<div class="block">' );
				$p->pl( '<form method="POST">' );
				$p->pl( '	<table class="form" cellpadding="0" cellspacing="0" border="0">' );
				$p->pl( '		<tr>' );
				$p->pl(	'			<td class="head">root username:</td>' );
				$p->pl( '			<td class="tail">' );
				$p->pl( '				<input id="uninput" name="username" />' );
				$p->pl( '				<script type="text/javascript">/*<![CDATA[*/' );
				$p->pl( '					uninput = document.getElementById( "uninput" );' );
				$p->pl( '					uninput.focus();' );
				$p->pl( '				/*]]>*/</script>' );
				$p->pl( '			</td>' );
				$p->pl( '		</tr>' );
				$p->pl( '		<tr><td class="head">root password:</td><td class="tail"><input name="password" type="password" /></td></tr>' );
				$p->pl( '	</table>' );
				$p->pl( '	<p><input type="submit" value="login" /></p>' );
				$p->pl( '</form>' );
				if( $this->status == self::INVALID_USER ) {
					$p->pl( '<p><span class="critical">Invalid Username or Password</span></p>' );	
				}
				$p->pl( '</div>' );
				
			}
		}
	}
	
	$ardeUser->applyOverrides( array( 'RootLoginPage' => true ) );

	$page = $ardeUser->makeObject( 'RootLoginPage' );

	$page->render( $p );
?>