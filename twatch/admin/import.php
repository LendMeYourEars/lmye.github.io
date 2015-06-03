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


	$twatch->makeParentClass( 'AdminImportPage', 'TwatchAdminPage' );

	class AdminImportPage extends AdminImportPageParent {
	
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Admin: Import'; }
		
		protected function getSelectedLeftButton() { return 'import'; }
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			global $twatch;
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="'.$twatch->baseUrl( $this->getToRoot(), 'js/ProgressBar.js' ).'"></script>' );
			$p->pl( '<script type="text/javascript" src="'.$twatch->url( $this->getToRoot(), 'js/Import.js' ).'"></script>' );
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch;

			$files = new ArdeAppender( ', ' );
			$dir = opendir( $twatch->path( 'store' ) );
			while( $file = readdir( $dir ) ) {
				if( $file == '..' || $file == '.' ) continue;
				$files->append( "'".ArdeJs::escape( $file )."'" );
			}

			$counterSs = new ArdeAppender( ', ' );
			foreach( $twatch->config->getList( TwatchConfig::COUNTERS ) as $counter ) {
				if( !$counter->allowImport() ) continue;
				$counterSs->append( $counter->id.': '.$counter->minimalJsObject() );
			}

			$websites = new ArdeAppender( ', ' );
			foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $website ) {
				if( $website->parent ) continue;
				$websites->append( $website->getId().': '.ArdeJs::string( $website->name ) );
			}

			$minYear = 2004;
			$maxYear = $twatch->now->getYear();

			$p->pl( '<h1>Import</h1>' );
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
			$this->initJsPage( $p );
			$p->rel();
			$p->pl( '	minYear = '.$minYear.';' );
			$p->pl( '	maxYear = '.$maxYear.';' );
			$p->pl( '	counters = { '.$counterSs->s.' };' );
			$p->pl( '	websites = { '.$websites->s.' };' );
			$p->pl( '	importer = new Importer( [ '.$files->s.' ] );' );
			$p->pl( '	importer.insert();' );
			$p->pl( '/*]]>*/</script>' );
			
		}
	}

	$twatch->applyOverrides( array( 'AdminImportPage' => true ) );

	$page = $twatch->makeObject( 'AdminImportPage' );
	$page->render( $p );
?>