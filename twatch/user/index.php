<?php
	require_once dirname(__FILE__).'/lib/PageGlobal.php';
	
	$ardeUser->makeParentClass( 'IndexPage', 'ArdeUserPage' );
	
	class IndexPage extends IndexPageParent {

		protected function getPageTitle() { return 'Your Account'; }
		
		protected function getToRoot() { return '.'; }
		
		
		protected function printBody( ArdePrinter $p ) {
			global $ardeUser;
			if( $ardeUser->user->id == ArdeUser::USER_PUBLIC ) {
				$p->pl( '<p>'.( $ardeUser->locale->text( 'You are not logged in' ) ).'.</p>' );
				return;
			}
			$p->pl( '<p>'.($ardeUser->locale->text( 'You are logged in as {username}', array( 'username' => '<span class="fixed">'.$ardeUser->user->name.'</span>' ) ) ).'</p>' );
		}
	}

	$ardeUser->applyOverrides( array( 'IndexPage' => true ) );

	$page = $ardeUser->makeObject( 'IndexPage' );

	$page->render( $p );
	
?>