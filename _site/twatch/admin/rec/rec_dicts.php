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
	
	
	loadConfig();
	
	requirePermission( TwatchUserData::ADMINISTRATE );
	
	$dicts = &$twatch->config->getList( TwatchConfig::DICTS );
	
	if( !isset( $_POST['a'] ) ) throw new TwatchException( 'Action was not sent' );
	

	if( $_POST['a'] == 'get_entries' ) {
		
		$dictId = ArdeParam::int( $_POST, 'i' );
		if( !isset( $dicts[ $dictId ] ) ) throw new TwatchException( 'dict '.$dictId.' does not exist' );
		$offset = ArdeParam::int( $_POST, 'o', 0 );
		$count = ArdeParam::int( $_POST, 'c', 1 );
		$entries = $dicts[ $dictId ]->getEntries( $offset, $count+1, null, true );
		if( !$xhtml ) {
			$p->pl( '<result more="'.(count($entries)>$count?'true':'false').'">', 1 );
			$i = 0;
			foreach( $entries as $id => $str ) {
				if( $i >= $count ) break;
				$p->pl( '<entry id="'.$id.'">'.htmlentities( $str ).'</entry>' );		
				++$i;
			}
			$p->rel();
			$p->pl( '</result>' );
		}
	
	} elseif( $_POST['a'] == 'cleanup' ) {
		
		$dictId = ArdeParam::int( $_POST, 'i' );
		if( !isset( $dicts[ $dictId ] ) ) throw new TwatchException( 'dict '.$dictId.' does not exist' );
		$dicts[ $dictId ]->fullCleanup();
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'reset_cleanup' ) {
		
		$dictId = ArdeParam::int( $_POST, 'i' );
		if( !isset( $dicts[ $dictId ] ) ) throw new TwatchException( 'dict '.$dictId.' does not exist' );
		$dicts[ $dictId ]->resetCleanup();
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} else {
		throw new TwatchException( 'unknown action '.$_POST['a'] );
	}

	$p->end();
?>