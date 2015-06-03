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

	$twatch->makeParentClass( 'AdminErrorsPage', 'TwatchAdminPage' );

	class AdminErrorsPage extends AdminErrorsPageParent {

		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Admin: Errors'; }
		
		protected function getSelectedLeftButton() { return 'errors'; }
		
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="../js/AdminErrors.js"></script>' );
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch, $ardeUser;

			if( !$ardeUser->user->hasPermission( TwatchUserData::VIEW_ERRORS ) ) throw new TwatchUserError( 'No permission' );

			$errorLogger = new TwatchErrorLogger();
			$errors = $errorLogger->getErrors();

			$p->pl( '<h1>Errors</h1>' );

			$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
			$p->hold( 1 );
			$this->initJsPage( $p );
			$p->rel();
			$p->pl( '	adminErrors = new AdminErrors();' );
			$p->pl( '	adminErrors.insert();' );
			$p->pl( '/*]]>*/</script>' );

			$p->pl( '<div id="errors_pane">' );

			if( !count( $errors ) ) {
				$p->pl( '<p>No Errors</p>' );
			} else {
				foreach( $errors as $error ) {
					$error->printXhtml( $p );
					$p->nl();
				}
			}

			$p->pl( '</div>' );

		}
	}

	$twatch->applyOverrides( array( 'AdminErrorsPage' => true ) );

	$page = $twatch->makeObject( 'AdminErrorsPage' );
	$page->render( $p );

?>