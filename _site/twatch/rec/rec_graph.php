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
    
	require_once dirname(__FILE__).'/../lib/RecGlobalHead.php';

	require_once dirname(__FILE__).'/../lib/EntityV.php';
	require_once dirname(__FILE__).'/../lib/Reader.php';

	loadConfig();

	requirePermission( TwatchUserData::VIEW_REPORTS );
	
	
	$websiteId = ArdeParam::int( $_POST, 'w' );
	if( !$twatch->config->propertyExists( TwatchConfig::WEBSITES, $websiteId ) ) throw new TwatchException( 'website with id '.$websiteId.' not found.' );
	$website = $twatch->config->get( TwatchConfig::WEBSITES, $websiteId );
	if( $website->parent ) throw new TwatchException( 'sub-website is not allowed.' );
	
	$graphR = new TwatchGraphReader( $website->getSub() );
	
	$counterId = ArdeParam::int( $_POST, 'ci' );
	if( !$twatch->config->propertyExists( TwatchConfig::COUNTERS, $counterId ) ) throw new TwatchException( 'unknown counter '.$counterId );
	$counter = $twatch->config->get( TwatchConfig::COUNTERS, $counterId );
	
	if( $counter->getType() == TwatchCounter::TYPE_GROUPED ) {
		$groupId = ArdeParam::int( $_POST, 'g', 0 );
	} else {
		$groupId = 0;
	}
	
	if( $counter->getType() != TwatchCounter::TYPE_SINGLE ) {
		$entityVId = ArdeParam::int( $_POST, 'evi', 0 );
	} else {
		$entityVId = 0;
	}
	
	$res = $graphR->get( $counterId, $groupId, $entityVId );
	
	if( !$xhtml ) {
		$res->printXml( $p, 'result' );
		$p->nl();
	} else {
		$p->pl( 'successful' );
	}
	
	$p->end();
?>
