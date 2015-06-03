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
	
	$twatch->makeParentClass( 'AdminUserAgentsPage', 'TwatchAdminPage' );
	
	class AdminUserAgentsPage extends AdminUserAgentsPageParent {
		
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Admin: User Agents'; }
		
		protected function getSelectedLeftButton() { return 'user_agents'; }
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="../js/PatternedObject.js"></script>' );
			$p->pl( '<script type="text/javascript" src="../js/UserAgent.js"></script>' );
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch;
			
			$p->pl( '<div class="help" style="float:right;" /><a href="http://www.tracewatch.com/doc/advanced/user_agents/">Help<img src="'.$twatch->baseUrl( $this->getToRoot(), 'img/help.png' ).'" alt="" /></a></div>' );
			
			$p->pl( '<h1>User Agents</h1>' );
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
			$this->initJsPage( $p );
			$p->rel();
			$p->pl( '	userAgentsHolder = new UserAgentsHolder();' );
			$p->pl( '	userAgentsHolder.insert();', 0 );
			foreach( $twatch->config->getList( TwatchConfig::USER_AGENTS ) as $userAgent ) {
				$p->pl( 'userAgentsHolder.insertFirstItem( '.$userAgent->jsObject().' )' );
			}
			$p->rel();
			$p->pl( '/*]]>*/</script>' );

		}
	}
	
	$twatch->applyOverrides( array( 'AdminUserAgentsPage' => true ) );
	
	$page = $twatch->makeObject( 'AdminUserAgentsPage' );
	$page->render( $p );
	
?>