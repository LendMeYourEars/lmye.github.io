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
	
	$twatch->makeParentClass( 'AdminWebsitesPage', 'TwatchAdminPage' );
	
	class AdminWebsitesPage extends AdminWebsitesPageParent {
		
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Websites'; }
		
		protected function getSelectedLeftButton() { return 'websites'; }
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="../js/Website.js"></script>' );
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch, $ardeBase, $ardeUser;

			$p->pl( '<div class="help" style="float:right" /><a href="http://www.tracewatch.com/doc/websites/">Help<img src="'.$twatch->baseUrl( $this->getToRoot(), 'img/help.png' ).'" alt="" /></a></div>' );
			
			$p->pl( '<h1>Websites</h1>' );
			
			$ids = new ArdeAppender( ', ' );
			$names = new ArdeAppender( ', ' );
			
			foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $id => $website ) {
				if( $website->parent ) continue;
				if( !$this->selectedUser->hasPermission( TwatchUserData::VIEW_WEBSITE, $id ) ) continue;
				$ids->append( $id );
				$names->append( ArdeJs::string( $website->name ) );
			}
			
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
			$p->rel();
			$p->pl( '	Permission.ViewWebsiteId = '.TwatchUserData::VIEW_WEBSITE.';' );
			$p->pl( '	websiteList = new WebsiteList( null, '.$this->selectedUser->data->get( TwatchUserData::DEFAULT_WEBSITE ).', [ '.$ids->s.' ], [ '.$names->s.' ] );' );
			$p->pl( '	websiteList.insert();', 0 );
			if( $ardeUser->user->hasPermission( TwatchUserData::ADMINISTRATE ) ) {
				foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $k => $w ) {
					if( $w->parent ) {
						$perm = 'null';
					} else {
						$perm = $this->selectedUser->getPermission( TwatchUserData::VIEW_WEBSITE, $k )->jsObject();
					}
					$p->pl( 'websiteList.addItem( '.$w->js_object( $perm ).' );' );
				}
			}
			$p->rel();
			$p->pl( '/*]]>*/</script>' );
		}
		
	}
	
	$twatch->applyOverrides( array( 'AdminWebsitesPage' => true ) );
	
	$page = $twatch->makeObject( 'AdminWebsitesPage' );
	
	$page->render( $p );
	
	
?>