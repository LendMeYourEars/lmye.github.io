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
	require_once $twatch->path( 'lib/AdminPage.php' );

	$twatch->makeParentClass( 'UserGroupsPage', 'TwatchAdminPage' );

	class UserGroupsPage extends UserGroupsPageParent {
		
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Admin: User Groups'; }
		
		protected function getSelectedLeftButton() { return 'user_groups'; }
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="../js/UserGroups.js"></script>' );
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch, $ardeUser;

			$p->pl( '<div class="help" style="float:right" /><a href="http://www.tracewatch.com/doc/advanced/users_and_groups/">Help<img src="'.$twatch->baseUrl( $this->getToRoot(), 'img/help.png' ).'" alt="" /></a></div>' );
			
			$p->pl( '<h1>User Groups</h1>' );
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
			$p->pl( '	userGroups = new UserGroupsHolder( '.ArdeJs::bool( $ardeUser->user->hasPermission( TwatchUserData::ADMINISTRATE ) ).' ).insert();' );
			foreach( $twatch->config->getList( TwatchConfig::USER_GROUPS ) as $userGroup ) {
				$p->pl( '	userGroups.insertFirstItem( '.$userGroup->adminJsObject().' );' );
			}
			$p->pl( '/*]]>*/</script>' );
		}
	}

	$twatch->applyOverrides( array( 'UserGroupsPage' => true ) );

	$page = $twatch->makeObject( 'UserGroupsPage' );
	$page->render( $p );
?>