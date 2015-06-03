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

	require_once dirname(__FILE__).'/../lib/Reader.php';
	require_once dirname(__FILE__).'/../lib/EntityV.php';

	require_once $twatch->path( 'data/DataStatsPage.php' );
	
	loadConfig( array(), array( StatsPage::$defaults ) );
	
	requirePermission( TwatchUserData::VIEW_REPORTS );
	
	$websiteId = ArdeParam::int( $_POST, 'w' );
	if( !$twatch->config->propertyExists( TwatchConfig::WEBSITES, $websiteId ) ) throw new TwatchException( 'website with id '.$websiteId.' not found.' );
	
	$statsPageId = ArdeParam::int( $_POST, 'si' );
	if( !$ardeUser->user->data->propertyExists( TwatchUserData::STATS_PAGES, $statsPageId ) ) throw new TwatchException( 'stats page '.$statsPageId.' not found.' );
	
	$StatsPage = $ardeUser->user->data->get( TwatchUserData::STATS_PAGES, $statsPageId );
	
	$counterViewId = ArdeParam::int( $_POST, 'vi' );
	if( !isset( $StatsPage->lCounterVs[ $counterViewId ] ) || !$StatsPage->lCounterVs[ $counterViewId ]->isViewable( $ardeUser->user ) ) throw new TwatchException( 'counter view '.$counterViewId.' not found.' );
	
	if( isset( $_POST[ 'svi' ] ) ) {
		$subViewId = ArdeParam::int( $_POST, 'svi' );
		$groupId = ArdeParam::int( $_POST, 'gi', 0 );
		if( !isset( $StatsPage->lCounterVs[ $counterViewId ]->subs[ $subViewId ] ) ) throw new TwatchException( 'sub counter view '.$subViewId.' not found.' );
	} else {
		$subViewId = null;
	}
	
	if( $twatch->config->get( TwatchConfig::COUNTERS, $StatsPage->lCounterVs[ $counterViewId ]->counterId )->getType() == TwatchCounter::TYPE_GROUPED ) {
		$groupId = ArdeParam::int( $_POST, 'gi', 0 );
	}
	
	$periodType = ArdeParam::int( $_POST, 'pt' );
	if( !isset( TwatchPeriod::$typeStrings[ $periodType ] ) ) throw new TwatchException( 'invalid period type '.$periodType );
	
	$periodCode = ArdeParam::str( $_POST, 'pc' );
	if( !TwatchPeriod::isValidTypeCode( $periodType, $periodCode ) ) throw new TwatchException( 'invalid period code '.$periodCode );
	
	$limit = ArdeParam::int( $_POST, 'l', 0, 1000 );
	
	$historyR = new TwatchHistoryReader( $twatch->config->get( TwatchConfig::WEBSITES, $websiteId )->getSub() );
	
	$period = TwatchPeriod::fromCode( $periodType, $periodCode );
	
	$StatsPage->initSingle( $period, $counterViewId );
	
	if( $subViewId === null ) {
		$StatsPage->lCounterVs[ $counterViewId ]->request( $historyR, $limit );
		$res = $historyR->rollGet();
		$StatsPage->lCounterVs[ $counterViewId ]->getResult( $historyR );
		$r = $StatsPage->lCounterVs[ $counterViewId ]->results[ $periodType.'-'.$periodCode ];
		if( !$xhtml ) {
			$r->printXml( $p, 'result', $StatsPage->lCounterVs[ $counterViewId ]->entityView );
		} else {
		}
	} else {
		$StatsPage->lCounterVs[ $counterViewId ]->subs[ $subViewId ]->request( $historyR, $limit, $groupId );
		$res = $historyR->rollGet();
		$StatsPage->lCounterVs[ $counterViewId ]->subs[ $subViewId ]->getResult( $historyR );
		$r = $StatsPage->lCounterVs[ $counterViewId ]->subs[ $subViewId ]->results[ $periodType.'-'.$periodCode ];
		if( !$xhtml ) {
			$r->printXml( $p, 'result', $StatsPage->lCounterVs[ $counterViewId ]->subs[ $subViewId ]->entityView );
		} else {
		}
	}
	
	
	
	if( !$xhtml ) {
		$p->pl( '<successful />' );
	} else {
		$p->pl( 'successful' );
		$p->relnl();
		$p->pl( '</body>' );
	}
	
	
	$p->end();
?>