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
	
	$twatch->makeParentClass( 'AdminSearchEnginesPage', 'TwatchAdminPage' );
	
	class AdminSearchEnginesPage extends AdminSearchEnginesPageParent {
		
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Admin: Web Areas'; }
		
		protected function getSelectedLeftButton() { return 'search_engines'; }
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="../js/PatternedObject.js"></script>' );
			$p->pl( '<script type="text/javascript" src="../js/SearchEngine.js"></script>' );
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch;
			
			$p->pl( '<h1>Web areas</h1>' );
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
			$this->initJsPage( $p );
			$p->rel();
			$p->pl( '	searchEnginesHolder = new SearchEnginesHolder();' );
			$p->pl( '	searchEnginesHolder.insert();', 0 );
			foreach( $twatch->config->getList( TwatchConfig::SEARCH_ENGINES ) as $searchEngine ) {
				$p->pl( 'searchEnginesHolder.insertFirstItem( '.$searchEngine->jsObject().' )' );
			}
			$p->rel();
			$p->pl( '/*]]>*/</script>' );
		}
	}
	
	$twatch->applyOverrides( array( 'AdminSearchEnginesPage' => true ) );
	
	$page = $twatch->makeObject( 'AdminSearchEnginesPage' );
	$page->render( $p );
?>