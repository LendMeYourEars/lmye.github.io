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
	
	$twatch->makeParentClass( 'AdminStatsPagesPage', 'TwatchAdminPage' );
	
	class AdminStatsPagesPage extends AdminStatsPagesPageParent {
		
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Admin: Stats Pages'; }
		
		protected function getSelectedLeftButton() { return 'stats_pages'; }
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="../js/AdminStatsPage.js"></script>' );
		}
		
		protected function init() {
			global $twatch, $ardeBase;
			require_once $twatch->path( 'lib/StatsPage.php' );
			require_once $twatch->path( 'lib/EntityV.php' );
			require_once $twatch->path( 'data/DataStatsPage.php' );
			parent::init();
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch, $ardeBase, $ardeUser;
			
			if( !isset( $_GET[ 'page' ] ) ) $id = 1;
			else $id = (int)$_GET[ 'page' ];
		
			$counters = $twatch->config->getList( TwatchConfig::COUNTERS );
			
			$selectedUser = getSelectedUser( true, StatsPage::$defaults );
			
			$statsPages = $selectedUser->data->getList( TwatchUserData::STATS_PAGES );
			if( !isset( $statsPages[ $id ] ) ) throw new TwatchException( 'Stats page with id '.$id.' not found' );
			$statsPage = $statsPages[ $id ];
			
			$counterSs = new ArdeAppender( ', ' );
			foreach( $counters as $counter ) {
				if( !$counter->isViewable( $selectedUser ) ) continue;
				$counterSs->append( $counter->id.': '.$counter->minimalJsObject() );
			}
			
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
			TwatchEntityView::InitJs( $p );
			$p->rel();
			$p->pl( '	counters = { '.$counterSs->s.' };' );
			$p->pl( '	statsPagesHolder = new StatsPagesHolder( '.BoolValueWithDefault::getFromProperty( $selectedUser->data, TwatchUserData::VIEW_STATS )->jsObject().' );' );
			$p->pl( '	statsPagesHolder.insert();', 0 );
			foreach( $statsPages as $statsPage ) {
				$p->pl( 'statsPage = '.$statsPage->adminJsObject().';' );
				$p->pl( 'statsPagesHolder.addStatsPage( statsPage );', 0 );
				foreach( $statsPage->sCounterVs as $singleCView ) {
					$p->pl( $singleCView->adminJsObject( 'statsPage' ).';' );
				}
				foreach( $statsPage->lCounterVs as $listCView ) {
					$p->pl( $listCView->adminJsObject( 'statsPage' ).';' );
				}
				$p->rel();
			}
			$p->rel();
			$p->pl( '/*]]>*/</script>' );
			
		}
	}
	
	$twatch->applyOverrides( array( 'AdminStatsPagesPage' => true ) );
	
	$page = $twatch->makeObject( 'AdminStatsPagesPage' );
	
	$page->render( $p );
	
	
?>