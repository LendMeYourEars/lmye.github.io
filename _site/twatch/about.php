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

	$twatch->makeParentClass( 'AboutPage', 'TwatchPage' );
		
	class AboutPage extends AboutPageParent {
		
		protected function getTitle() { return 'About TraceWatch'; }
		
		protected function getSelectedTopButton() { return 4; }
		
		protected function getToRoot() { return '.'; }
		
		protected function printBody( ArdePrinter $p ) {
			$p->pl( '<div class="block alt" lang="en-US" xml:lang="en-US" style="direction:ltr;font-size:1.2em;text-align:center">' );
			$this->printContents( $p );
			$p->pl( '</div>' );
		}
		
		protected function printContents( ArdePrinter $p ) {
			global $twatch;
			$p->pl( '<h2>TraceWatch '.$twatch->version.'</h2>' );
			$p->pl( '<h3>Website Statistics and Traffic Analysis Software</h3>' );
			$p->pl( '<p>Author: <span class="fixed" style="font-size:1.1em">Arash Dejkam</span></p>' );
			$p->pl( '<p>Copyright &copy;2004-2010 Arash Dejkam, All Right Reserved.</p>' );
			$p->pl( '<p>For more information visit <a style="font-weight:bold" href="http://www.tracewatch.com/">WWW.TRACEWATCH.COM</a></p>' );
			$p->pl( '<hr />');
			$p->pl( '<p style="text-align:left">This program is distributed in the hope that it will be useful,' );
			$p->pl( 'but WITHOUT ANY WARRANTY, without even the implied warranty of' );
			$p->pl( 'MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.</p>' );
		}
	}
	
	$twatch->applyOverrides( array( 'AboutPage' => true ) );
	
	$page = $twatch->makeObject( 'AboutPage' );
	
	$page->render( $p );
	
	
?>
