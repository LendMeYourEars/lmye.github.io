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
    
	$adminConfig = true;
	require_once dirname(__FILE__).'/../../lib/RecGlobalHead.php';
	require_once $twatch->path( 'lib/Comments.php' );
	loadConfig();
	
	requirePermission( TwatchUserData::ADMINISTRATE );
	
	if( !isset( $_POST['a'] ) ) throw new TwatchException( 'Action was not sent' );
	
	$comments = new TwatchComments();

	if( $_POST['a'] == 'add' ) {
		$visibility = isset( $_POST[ 'p' ] ) ? TwatchComment::VIS_PRIVATE : TwatchComment::VIS_PUBLIC; 
		$time = new TwatchTime();
		$time->initWithDate( ArdeParam::int( $_POST, 'y' ), ArdeParam::int( $_POST, 'm' ), ArdeParam::int( $_POST, 'd' ) );
		$txt = ArdeParam::str( $_POST, 't' );
		$id = $comments->add( $time, $txt, $visibility );
		if( !$xhtml ) {
			$comment = new TwatchComment( $id, $txt, $time, $visibility );
			$comment->printXml( $p, 'result' );
			$p->nl();
		}
		
	} elseif( $_POST['a'] == 'remove' ) {
		$id = ArdeParam::int( $_POST, 'i', 0 );
		$comments->remove( $id );
		if( !$xhtml ) {
			$p->pl( '<successful />' );
		}
		
	} elseif( $_POST['a'] == 'reset' ) {
		$comments->install( true );
		if( !$xhtml ) {
			$p->pl( '<successful />' );
		}
	} else {
		throw new TwatchException( 'unknown action '.$_POST['a'] );
	}

	$p->end();
?>