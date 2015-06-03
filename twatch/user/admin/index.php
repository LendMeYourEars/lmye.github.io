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
    
	
	require_once dirname(__FILE__).'/../lib/PageGlobal.php';
	require_once $ardeUser->path( 'lib/AdminPage.php' );
	
	$ardeUser->makeParentClass( 'AdminIndexPage', 'ArdeUserAdminPage' );
	
	class AdminIndexPage extends AdminIndexPageParent {
		
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Administrate'; }
		
		protected function getSelectedLeftButton() { return 0; }
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			global $ardeUser;
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="'.$ardeUser->url( $this->getToRoot(), 'js/AdminUser.js' ).'">' );
		}

		protected function printBody( ArdePrinter $p ) {
			global $ardeUser;
			
			$usersManager = new ArdeUsers();
			$usersCount = $usersManager->getUsersCount();
			$users = $usersManager->getUsers( 0, ArdeUser::$perPage );
			$p->pl( '<div style="font-size:1.1em;background:#a00;color:#fff;margin-left:10px;padding:10px;padding-top:0px;margin-top:10px;border:1px solid #000;"><p><b>[IMPORTANT]</b> Multi user functionality in TraceWatch is a candidate to become an <b>exclusive</b> feature in future <b>commercial pro version of TraceWatch</b>, If you are not willing to go pro in future please do not rely on this feature.</p></div>' );
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
			$this->initJsPage( $p );
			$p->rel();
			$p->pl( '	User.RootUserId = '.ArdeUser::USER_ROOT.';' );
			$p->pl( '	users = new UsersHolder( '.ArdeUser::$perPage.', '.$usersCount.' );' );
			$p->pl( '	users.insert();', 0 );
			foreach( $users as $user ) {
				$p->pl( 'users.addItem( '.$user->jsObject().' );' );
			}
			$p->rel();
			$p->pl( '	users.updatePrevNextButtons();' );
			$p->pl( '	users.newUser.usernameInput.element.focus();' );
			$p->pl( '/*]]>*/</script>' );
		}
		
	} 
	
	$ardeUser->applyOverrides( array( 'AdminIndexPage' => true ) );
	
	$page = $ardeUser->makeObject( 'AdminIndexPage' );
	
	$page->render( $p );

	
	
?>