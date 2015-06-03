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
    
	require_once $twatch->path( 'lib/EntityV.php' );
	require_once $twatch->path( 'lib/LatestPage.php' );
	
	twatchMakeLatestPageData();
	
	function twatchMakeLatestPageData() { 
		$l = new TwatchLatestPage( 10, TwatchLatestPage::DEFAULT_REQ_PER_SES, TwatchLatestPage::DEFAULT_REQ_PER_SINGLE_SES, array( TwatchVisitorType::NORMAL, TwatchVisitorType::ROBOT, TwatchVisitorType::ADMIN, TwatchVisitorType::SPAMMER ) );
		$l->priItems[ TwatchLatestPage::ITEM_REF ] = new TwatchLatestItem( TwatchEntity::PROC_REF, new TwatchEntityView( 1, TwatchEntityView::EV_REFS_IMG_TYPE, 1, EntityV::STRING_DEFAULT, 90 ), TwatchLatestItem::LOOKUP_FIRST, 'Referrer', 'Direct Type or Unknown' );
		$l->priItems[ TwatchLatestPage::ITEM_BRO ] = new TwatchLatestItem( TwatchEntity::USER_AGENT, new TwatchEntityView( 1, 1, 0 ), TwatchLatestItem::LOOKUP_LAST, null, 'Unknown Browser' );
		$l->priItems[ TwatchLatestPage::ITEM_AGT ] = new TwatchLatestItem( TwatchEntity::AGENT_STR, new TwatchEntityView( 1, 0, 0, EntityV::STRING_DEFAULT, 90 ), TwatchLatestItem::LOOKUP_LAST, 'UA String', 'Unknown UA String' );
		
		$l->secItems[ TwatchLatestPage::ITEM_IP ] = new TwatchLatestItem( TwatchEntity::IP, new TwatchEntityView( 1, 0, 0, EntityVIp::STRING_DOMAIN_IP ), TwatchLatestItem::LOOKUP_LAST, 'IP', 'Unknown IP' );
		$l->secItems[ TwatchLatestPage::ITEM_PIP ] = new TwatchLatestItem( TwatchEntity::PIP, new TwatchEntityView( 1, 0, 0, EntityVIp::STRING_DOMAIN_IP ), TwatchLatestItem::LOOKUP_LAST, 'Proxy', null );
		
		TwatchLatestPage::$defaults = array();
		TwatchLatestPage::$defaults[ TwatchUserData::LATEST_PAGE ][ 0 ] = $l;
	}
?>
