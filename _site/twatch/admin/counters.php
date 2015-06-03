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
	
	$twatch->makeParentClass( 'AdminCountersPage', 'TwatchAdminPage' );
	
	class AdminCountersPage extends AdminCountersPageParent {
		
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Admin: Counters'; }
		
		protected function getSelectedLeftButton() { return 'counters'; }
		
		protected function printBaseJsIncludes( ArdePrinter $p ) {
			global $twatch;
			parent::printBaseJsIncludes( $p );
			$p->pl( '<script type="text/javascript" src="'.$twatch->baseUrl( $this->getToRoot(), 'js/ArdeExpression.js' ).'"></script>' );
		}
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="../js/AdminCounters.js"></script>' );
		}
		
		protected function init() {
			global $twatch;
			require_once $twatch->path( 'lib/StatsPage.php' );
			require_once $twatch->path( 'data/DataStatsPage.php' );
			$this->addExtraConfig( StatsPage::$defaults );
			parent::init();
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch, $ardeUser;

			$p->pl( '<div class="help" style="float:right" /><a href="http://www.tracewatch.com/doc/advanced/counters/">Help<img src="'.$twatch->baseUrl( $this->getToRoot(), 'img/help.png' ).'" alt="" /></a></div>' );
			
			$counters = &$twatch->config->getList( TwatchConfig::COUNTERS );
			$entities = &$twatch->config->getList( TwatchConfig::ENTITIES );
			
			$ents = new ArdeAppender( ', ' );
			foreach( $entities as $entity ) {
				$ents->append( $entity->id.": '".ArdeJs::escape( $entity->name )."'" );
			}
			
			$p->pl( '<h1>Counters</h1>' );
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
			$this->initJsPage( $p );
			TwatchExpression::printJsInputElements( $p );
			$p->rel();
			$p->pl( '	entities = { '.$ents->s.' };' );
			$when = new TwatchExpression( $twatch->config->get( TwatchConfig::COUNTERS_WHEN ), null );
			$p->pl( '	countersHolder = new CountersHolder( '.$when->jsObject().' );' );
			$p->pl( '	countersHolder.insert();', 0 );
			
			foreach( $counters as $id => $counter ) {
				$perm = $this->selectedUser->getPermission( TwatchUserData::VIEW_COUNTER, $id )->jsObject();
				$p->pl( 'countersHolder.addCounter( '.$counter->jsObject( $perm ).' );' );
			}
			$p->rel();
			$p->pl( '/*]]>*/</script>' );
			
		}
	}
	
	$twatch->applyOverrides( array( 'AdminCountersPage' => true ) );
	
	$page = $twatch->makeObject( 'AdminCountersPage' );
	$page->render( $p );
?>