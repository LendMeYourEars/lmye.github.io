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
	
	$twatch->makeParentClass( 'AdminPathAnalyzerPage', 'TwatchAdminPage' );
	
	class AdminPathAnalyzerPage extends AdminPathAnalyzerPageParent {
		
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Admin: Path Analyzer'; }
		
		protected function getSelectedLeftButton() { return 'path_analyzer'; }
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="../js/AdminPathAnalyzer.js"></script>' );
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch, $ardeBase;
			
			require $twatch->path( 'lib/PathAnalyzerDiag.php' );

			$entities = &$twatch->config->getList( TwatchConfig::ENTITIES );
			$pathAnalyzer = &$twatch->config->get( TwatchConfig::PATH_ANALYZER ); 
			$defPathAnalyzer = $twatch->config->getDefault( TwatchConfig::PATH_ANALYZER );
			$installed = $twatch->state->get( TwatchState::PATH_ANALYZER_INSTALLED );
			
			$pathAnalyzerDiag = new TwatchPathAnalyzerDiag();
			$pathAnalyzerDiag->load();
		
			require_once $twatch->path( 'lib/AdminGeneral.php' );
			
			$selectedUser = getSelectedUser( true );
			
			$p->pl( '<h1>Path Analyzer</h1>' );
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
			$this->initJsPage( $p );
			$p->rel();
			$ents = new ArdeAppender( ', ' );
			foreach( $entities as $entity ) {
				$ents->append( $entity->id.": '".$entity->name."'" );
			}
			
			
			
			$p->pl( '	entities = { '.$ents->s.' };' );
			$p->pl( '	pathAnalyzer = new AdminPathAnalyzer( '.$pathAnalyzer->adminJsObject().', '.($installed?'true':'false').', '.$defPathAnalyzer->adminJsObject().', '.BoolValueWithDefault::getFromProperty( $selectedUser->data, TwatchUserData::VIEW_PATH_ANALYSIS )->jsObject().' );' );
			$p->pl( '	pathAnalyzer.insert();' );
			if( !$this->configMode )  {
				$p->pl( '	diagHolder = new DiagHolder( '.$pathAnalyzerDiag->jsObject().' );' );
				$p->pl( '	diagHolder.insert();' );
			}
			$p->pl( '/*]]>*/</script>' );
			
		}
	}
	
	$twatch->applyOverrides( array( 'AdminPathAnalyzerPage' => true ) );
	
	$page = $twatch->makeObject( 'AdminPathAnalyZerPage' );
	
	$page->render( $p );
	
?>