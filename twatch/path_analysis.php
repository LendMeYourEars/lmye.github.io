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
    
	require_once dirname(__FILE__).'/lib/PageGlobal.php';	

	$twatch->makeParentClass( 'PathAnalysisPage', 'TwatchPage' );
		
	class PathAnalysisPage extends PathAnalysisPageParent {
		
		protected function getToRoot() { return '.'; }
		
		protected function getTitle() { return 'Path Analysis'; }
		
		protected function getSelectedTopButton() { return 2; }
		
		protected function init() {
			global $ardeUser;
			parent::init();
			
			if( !$ardeUser->user->data->get( TwatchUserData::VIEW_PATH_ANALYSIS ) ) {
				throw new TwatchUserError( 'Not Found' );
			}
		}
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="js/PathAnalysis.js"></script>' );
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch, $ardeBase;

			$pathAnalyzer = $twatch->config->get( TwatchConfig::PATH_ANALYZER );
		
			$p->pl( '<div class="margin_std" style="direction:ltr">' );
			$p->pl( '	<script type="text/javascript">/*<![CDATA[*/' );
			$p->hold(2);
			$this->initJsPage( $p );
			$p->rel();
			$p->pl( '		pathAnalyzer = '.$pathAnalyzer->jsObject( 1000, 600, 'rec/rec_paths.php', $this->websiteId ).';' );
			$p->pl( '		pathAnalyzer.insert();' );
			$p->pl( '	/*]]>*/</script>' );
			$p->pl( '</div>' );
			
		}
		

	}
	
	$twatch->applyOverrides( array( 'PathAnalysisPage' => true ) );
	
	$page = $twatch->makeObject( 'PathAnalysisPage' );
	
	$page->render( $p );
	
?>