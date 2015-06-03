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
	
	$twatch->makeParentClass( 'AdminLatestPage', 'TwatchAdminPage' );
	
	class AdminLatestPage extends AdminLatestPageParent {
		
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Admin: Latest Visitors Page'; }
		
		protected function getSelectedLeftButton() { return 'latest'; }
		
		protected function printBaseJsIncludes( ArdePrinter $p ) {
			global $twatch;
			parent::printBaseJsIncludes( $p );
			$p->pl( '<script type="text/javascript" src="'.$twatch->baseUrl( $this->getToRoot(), 'js/ArdeExpression.js' ).'"></script>' );
		}
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="../js/AdminLatest.js"></script>' );
		}
		
		
		
		protected function init() {
			global $twatch, $ardeBase;
			require_once $twatch->path( 'data/DataLatest.php' );
			parent::init();
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch, $ardeBase, $ardeUser;
			
			require_once $twatch->path( 'lib/AdminGeneral.php' );
			
			$selectedUser = getSelectedUser( true, TwatchLatestPage::$defaults );
			
			
			$entities = &$twatch->config->getList( TwatchConfig::ENTITIES );
			$latestPage = $selectedUser->data->get( TwatchUserData::LATEST_PAGE );
			$latest = $twatch->config->get( TwatchConfig::LATEST );
			$dataWriters = $twatch->config->getList( TwatchConfig::RDATA_WRITERS );
			
			
			
			$itemEntities = new ArdeAppender( ', ' );
			foreach( $dataWriters as $dataWriter ) {
				if( !isset( $entities[ $dataWriter->entityId ] ) ) continue;
				if( !$entities[ $dataWriter->entityId ]->isViewable( $ardeUser->user ) ) continue;
				$itemEntities->append( $dataWriter->entityId.': '.ArdeJs::string( $entities[ $dataWriter->entityId ]->name ) );
			}
			
			$p->pl( '<h1>Latest Visitors Page</h1>' );
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
			$p->pl( '	twatchPerUserWebsite().insert();' );
			TwatchEntityView::InitJs( $p );
			TwatchExpression::printJsInputElements( $p );
			$p->rel();
			
			$p->pl( '	itemEntities = { '.$itemEntities->s.' };' );
			$vts = new ArdeAppender( ', ' );
			foreach( $twatch->config->getList( TwatchConfig::VISITOR_TYPES ) as $visitorType ) {
				$vts->append( $visitorType->id.': '.$visitorType->jsObject() );
			}
			$p->pl( '	vTypes = { '.$vts->s.' };' );
			$p->pl( '	adminLatestPage = '.$latestPage->adminJsObject( BoolValueWithDefault::getFromProperty( $selectedUser->data, TwatchUserData::VIEW_LATEST )->jsObject() ).';' );
			$p->pl( '	adminLatestPage.insert();' );
			$p->pl( '/*]]>*/</script>' );
			if( !$this->configMode ) {
				
				$ents = new ArdeAppender( ', ' );
	
				
				
				foreach( $entities as $entity ) {
					$ents->append( $entity->id.": '".ArdeJs::escape( $entity->name )."'" );
				}
				
				$p->pl( '<h1>Latest Visitors</h1>' );
				
				$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
				$p->pl( '	entities = { '.$ents->s.' };' );
				$p->pl( '	twatchGlobalSettings().insert();' );
				$p->pl( '	adminLatest = '.$latest->jsObject().';' );
				$p->pl( '	adminLatest.insert();' );
				$p->pl( '/*]]>*/</script>' );
				
				$p->pl( '<h1>Data Writers</h1>' );
				$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
				$p->pl( '	dataWritersHolder = new DataWritersHolder();' );
				$p->pl( '	dataWritersHolder.insert();', 0 );
				foreach( $dataWriters as $dataWriter ) {
					$p->pl( 'dataWritersHolder.addItem( '.$dataWriter->jsObject().' );' );
				}
				$p->rel();
				$p->pl( '/*]]>*/</script>' );
			}
		}
		
	}
	
	$twatch->applyOverrides( array( 'AdminLatestPage' => true ) );
	
	$page = $twatch->makeObject( 'AdminLatestPage' );
	$page->render( $p );
?>