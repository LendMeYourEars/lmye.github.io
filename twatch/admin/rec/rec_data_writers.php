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
	require_once $twatch->path( 'lib/EntityV.php' );
	
	loadConfig();
	
	requirePermission( TwatchUserData::ADMINISTRATE );
	
	$dataWriters = &$twatch->config->getList( TwatchConfig::RDATA_WRITERS );
	
	if( !isset( $_POST['a'] )) throw new TwatchException( 'Action was not sent' );
	
	
	if( $_POST['a'] == 'add' ) {
		
		$dataWriter = TwatchRDataWriter::fromParams( $_POST, true );
		$dataWriter->id = $twatch->config->getNewSubId( TwatchConfig::RDATA_WRITERS );
		$dataWriter->adding();
		$twatch->config->addToTop( $dataWriter, TwatchConfig::RDATA_WRITERS, $dataWriter->id );
		if( !$xhtml ) {
			$dataWriter->printXml( $p, 'result' );
			$p->nl();
		}
		
	} elseif( $_POST['a'] == 'change' ) {
		
		$dataWriter = TwatchRDataWriter::fromParams( $_POST, false );
		if( $dataWriter->isEquivalent( $dataWriters[ $dataWriter->id ] ) ) throw new TwatchUserError( 'nothing to change' );
		$dataWriters[ $dataWriter->id ]->removing();
		$dataWriter->adding();
		$twatch->config->set( $dataWriter, TwatchConfig::RDATA_WRITERS, $dataWriter->id );
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'delete' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $dataWriters[ $id ] ) ) throw new TwatchException( 'data writer '.$id.' does not exist' );
		$dataWriters[ $id ]->removing();
		$dataWriters[ $id ]->removeData();
		$twatch->config->remove( TwatchConfig::RDATA_WRITERS, $id );
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'restore_deleted' ) {
	
		$delDefs = $twatch->config->getDeletedDefaults( TwatchConfig::RDATA_WRITERS );
		
		$twatch->config->restoreDeletedDefaults( TwatchConfig::RDATA_WRITERS, TwatchConfig::RESTORE_POS_INSERT );
		foreach( $delDefs as $id => $pos ) {
			$dataWriters[ $id ]->adding();
		}
		if( !$xhtml ) {
			$p->pl( '<result>', 1 );
			foreach( $delDefs as $id => $pos ) {
				$dataWriters[ $id ]->printXml( $p, 'data_writer', ' pos="'.$pos.'" ' );
				$p->nl();
			}
			$p->rel();
			$p->pl( '</result>' );
		}
	
	} else {
		throw new TwatchException( 'unknown action '.$_POST['a'] );
	}

	$p->end();
?>
