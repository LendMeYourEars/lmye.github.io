<?php
	require_once dirname(__FILE__).'/../lib/Global.php';
	
	require_once $twatch->path( 'lib/Common.php' );
	require_once $twatch->path( 'lib/Reader.php' );
	require_once $twatch->path( 'lib/EntityPassiveGene.php' );
	require_once $twatch->path( 'data/DataGlobal.php' );
	require_once $ardeBase->path( 'lib/ArdePrinter.php' );
	
	if( !$GLOBALS['twatch']->settings[ 'down' ] ) {
		twatchInitGetStats();
	}
	
	function twatchInitGetStats() {
		global $twatch;
		
		$p = new ArdePrinter();
		
		twatchConnect();
		
		$twatch->config = new TwatchConfig( $twatch->db );
		$twatch->config->addDefaults( TwatchConfig::$defaultProperties );
		$twatch->config->applyAllChanges();
		
		twatchSetAppTime();
		
	}
	
	function twatchToday() {
		global $twatch;
		if( $twatch->settings[ 'down' ] ) return '';
		return $twatch->now->getDayCode(); 
	}
	
	function twatchYesterday() {
		global $twatch;
		if( $twatch->settings[ 'down' ] ) return '';
		return $twatch->now->dayOffset( -1 )->getDayCode();
	}
	
	function twatchThisMonth() {
		global $twatch;
		if( $twatch->settings[ 'down' ] ) return '';
		return $twatch->now->getMonthCode();
	}
	
	function twatchLastMonth() {
		global $twatch;
		if( $twatch->settings[ 'down' ] ) return '';
		return $twatch->now->monthOffset( -1 )->getMonthCode();
	}
	
	function twatchCounterResult( $counterId, $periodType, $periodCode , $value = null, $group = null, $websiteId = 1 ) {
		global $twatch;
		if( $twatch->settings[ 'down' ] ) return 0;
		$website = TwatchWebsite::getWebsiteFromId( $websiteId );
		if( $websiteId === null ) throw new TwatchException( 'website with id '.$websiteId.' not found.' );
		
		if( !$twatch->config->propertyExists( TwatchConfig::COUNTERS, $counterId ) )
			throw new TwatchException( 'counter with id '.$counterId.' not found.' );
		$counter = $twatch->config->get( TwatchConfig::COUNTERS, $counterId );
		
		$hr = new TwatchHistoryReader( $website->getSub() );
		
		if( $counter->getType() == TwatchCounter::TYPE_SINGLE ) {
			$counter->request( $hr, $periodType, $periodCode );
			$entityVId = null;
			$groupId = 0;
		} else {
			if( $value === null ) throw new TwatchUserError( 'Currently only single results can be retreived with twatchCounterResult()' );
			$entity = $twatch->config->get( TwatchConfig::ENTITIES, $counter->entityId );
			$gene = $entity->gene->getPassiveGene( new TwatchDbPassiveDict( $twatch->db ), TwatchEntityPassiveGene::MODE_READ_ONLY, TwatchEntityPassiveGene::CONTEXT_API );
			$entityVId = $gene->getStringEntityVId( $value, $website );
			if( $entityVId === false ) return 0;
			if( $counter->getType() == TwatchCounter::TYPE_GROUPED ) {
				if( $group === null ) throw new TwatchUserError( 'You must specify the group for grouped counters' );
				$groupEntity = $twatch->config->get( TwatchConfig::ENTITIES, $counter->groupEntityId );
				$gene = $groupEntity->gene->getPassiveGene( new TwatchDbPassiveDict( $twatch->db ), TwatchEntityPassiveGene::MODE_READ_ONLY, TwatchEntityPassiveGene::CONTEXT_API );
				$groupId = $gene->getStringEntityVId( $group, $website );
			} else {
				$groupId = 0;
			}
			$counter->request( $hr, $periodType, $periodCode, $groupId, 0, false, $entityVId );
		}
		$hr->rollGet();
		
		$res = $counter->getResult( $hr, $periodType, $periodCode, $groupId, $entityVId );
		return $res->count;
	}
	
	function twatchOnlineVisitorsCount( $websiteId = 1 ) {
		global $twatch;
		if( $twatch->settings[ 'down' ] ) return 0;
		$website = TwatchWebsite::getWebsiteFromId( $websiteId );
		if( $websiteId === null ) throw new TwatchException( 'website with id '.$websiteId.' not found.' );
		
		$sr = new TwatchSessionReader( $website->getSub() );
		return $sr->onlineVisitorsCount( 300, TwatchVisitorType::NORMAL ); 
	}
?>