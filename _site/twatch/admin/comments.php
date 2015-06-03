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

	$twatch->makeParentClass( 'AdminCommentsPage', 'TwatchAdminPage' );

	class AdminCommentsPage extends AdminCommentsPageParent {
		
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Admin: Comments'; }
		
		protected function getSelectedLeftButton() { return 'comments'; }
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="../js/AdminComments.js"></script>' );
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch;

			require_once $twatch->path( 'lib/Comments.php' );

			$minYear = 2004;
			$maxYear = $twatch->now->getYear();
			$year = $twatch->now->getYear();
			$month = $twatch->now->getMonth();
			$day = $twatch->now->getDay();

			$comments = new TwatchComments();

			$p->pl( '<h1>Comments</h1>' );
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
			$this->initJsPage( $p );
			$p->rel();
			$p->pl( '	comments = new Comments( '.$minYear.', '.$maxYear.', '.$year.', '.$month.', '.$day.' );' );
			$p->pl( '	comments.insert();' );
			$p->pl( '	comments.area.element.focus();' );
			$p->hold( 1 );
			foreach( $comments->getAll() as $comment ) {
				$p->pl( 'comments.addItem( '.$comment->adminJsObject().' );' );
			}
			$p->rel();
			$p->pl( '/*]]>*/</script>' );

		}
	}

	$twatch->applyOverrides( array( 'AdminCommentsPage' => true ) );

	$page = $twatch->makeObject( 'AdminCommentsPage' );
	$page->render( $p );
?>