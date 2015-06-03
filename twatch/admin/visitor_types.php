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
	
	$twatch->makeParentClass( 'AdminVisitorTypesPage', 'TwatchAdminPage' );
	
	class AdminVisitorTypesPage extends AdminVisitorTypesPageParent {
		
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Admin: Visitor Types'; }
		
		protected function getSelectedLeftButton() { return 'visitor_types'; }
		
		protected function printBaseJsIncludes( ArdePrinter $p ) {
			global $twatch;
			parent::printBaseJsIncludes( $p );
			$p->pl( '<script type="text/javascript" src="'.$twatch->baseUrl( $this->getToRoot(), 'js/ArdeExpression.js' ).'"></script>' );
		}
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="../js/VisitorType.js"></script>' );
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch;
			
			require_once $twatch->path( 'lib/EntityV.php' );
			
			$p->pl( '<div class="help" style="float:right;" /><a href="http://www.tracewatch.com/doc/advanced/visitor_types/">Help<img src="'.$twatch->baseUrl( $this->getToRoot(), 'img/help.png' ).'" alt="" /></a></div>' );
			
			$entities = new ArdeAppender( ', ' );
			foreach( $twatch->config->getList( TwatchConfig::ENTITIES ) as $entity ) {
				$entities->append( $entity->id.": ".$entity->minimalJsObject() );
			}
			
			$p->pl( '<h1>Visitor Types</h1>' );
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
			$this->initJsPage( $p );
			TwatchExpression::printJsInputElements( $p );
			$p->rel();
			$p->pl( '	entities = { '.$entities->s.' };' );
			$p->pl( '	visitorTypesHolder = new VisitorTypesHolder();' );
			$p->pl( '	visitorTypesHolder.insert();', 0 );
			foreach( $twatch->config->getList( TwatchConfig::VISITOR_TYPES ) as $visitorType ) {
				$p->pl( 'visitorTypesHolder.addItem( '.$visitorType->adminJsObject().' );' );
			}
			$p->pl( '/*]]>*/</script>' );
			
		}
	}
	
	$twatch->applyOverrides( array( 'AdminVisitorTypesPage' => true ) );
	
	$page = $twatch->makeObject( 'AdminVisitorTypesPage' );
	$page->render( $p );
?>