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
    global $ardeBase, $twatch;
    
	require_once $ardeBase->path( 'lib/ArdeSerializer.php' );
	require_once $twatch->path( 'lib/StatsPage.php' );
	require_once $twatch->path( 'lib/EntityV.php' );
		
	makeStatsPageData();
	
	function makeStatsPageData() {
		$allPTypes = array( TwatchPeriod::DAY, TwatchPeriod::MONTH, TwatchPeriod::ALL );
		
		$sp1 = new StatsPage( 1, 'General Stats', 3, $allPTypes );
		
		$sp1->sCounterVs = array(
			 1	=>	new SingleCounterView( 1		,'Unique Visitors'		,'{number} unique visitors'	,TwatchCounter::VISITORS	,$allPTypes															)
			,2	=>	new SingleCounterView( 2		,'New Visitors'			,'{number} new visitors'	,TwatchCounter::NEW_VISITORS,$allPTypes															)
			,3	=>	new SingleCounterView( 3		,'Sessions'				,'{number} sessions'		,TwatchCounter::SESSIONS	,$allPTypes															)
			,4	=>	new SingleCounterView( 4		,'Page Views'			,'{number} page views'		,TwatchCounter::PAGE_VIEWS	,$allPTypes															)
			,5	=>	new SingleCounterView( 5		,'Avg. PViews/Visitor'	,'{number}'					,TwatchCounter::PAGE_VIEWS	,$allPTypes		,CounterView::DIV_VALUE	,1			,.9			,1	)
			,6	=>	new SingleCounterView( 6		,'Est./Avg. UVisitors/Day'	,'{number}'				,TwatchCounter::VISITORS	,$allPTypes		,CounterView::DIV_DAYS	,null		,.2			,1	)
			,7	=>	new SingleCounterView( 7		,'Est./Avg. PViews/Day'	,'{number}'					,TwatchCounter::PAGE_VIEWS	,$allPTypes		,CounterView::DIV_DAYS	,null		,.2			,1	)
			,8	=>	new SingleCounterView( 8		,'Avg. UVisitors/Hour'	,'{number}'					,TwatchCounter::VISITORS	,$allPTypes		,CounterView::DIV_HOURS	,null		,1			,1	)
			,9	=>	new SingleCounterView( 9		,'Avg. PViews/Hour'		,'{number}'					,TwatchCounter::PAGE_VIEWS	,$allPTypes		,CounterView::DIV_HOURS	,null		,1			,1	)
			,10	=>	new SingleCounterView( 10		,'Robot Page Views'		,'{number} robot page views',TwatchCounter::ROBOT_PVIEWS,$allPTypes															)
		);
		
		$weekdayPTypes =  array( TwatchPeriod::MONTH, TwatchPeriod::ALL );
		
		$sp1->lCounterVs = array(
			 1	=>	new ListCounterView(	1	,'Hourly Distribution'	,'{number} sessions'					,TwatchCounter::DIST_HOURLY	,$allPTypes		,new TwatchEntityView(1,0,0)	,0		,0		,CounterView::DIV_HOUR_COUNT	,null		,1			,2		,0 	)
			,2	=>	new ListCounterView(	2	,'Pages'				,'{number} {value} views'				,TwatchCounter::PAGES		,$allPTypes		,new TwatchEntityView(1,0,1)																				)
			,3	=>	new ListCounterView(	3	,'Referrers'			,'{number} visitors referred from {value}'	,TwatchCounter::REFGROUPS	,$allPTypes		,new TwatchEntityView(1,1,1)																				)
			,4	=>	new ListCounterView(	4	,'Robots'				,'{number} page views by {value}'			,TwatchCounter::ROBOTS		,$allPTypes		,new TwatchEntityView(1,1,0)																				)
			,5	=>	new ListCounterView(	5	,'Browsers'				,'{number} sessions using {value}'			,TwatchCounter::BROWSERS		,$allPTypes		,new TwatchEntityView(1,1,0)																				)
			,7	=>	new ListCounterView(	7	,'Weekday Distribution'	,'{number} sessions'					,TwatchCounter::DIST_WEEKLY	,$weekdayPTypes ,new TwatchEntityView(1,0,0)	,0		,0		,CounterView::DIV_DAY_COUNT		,null		,1			,2		,0		)
			
		);
		
		$sp1->lCounterVs[3]->subs = array(
			1 => new SubCounterView(	1	,'{group} urls/keywords'		,'{number} visitors referred from {value}'		,TwatchCounter::REFERRERS			,$allPTypes		,new TwatchEntityView(1,0,1,EntityVProcRef::STRING_GROUP_SUB)	,7		,0							)
		);
		$sp1->lCounterVs[4]->subs = array(
			1 => new SubCounterView(	1	,'{group} user agent strings'	,'{number} with {value}'						,TwatchCounter::UA_STRINGS			,$allPTypes		,new TwatchEntityView(1,0,0)	,7		,0															)	
		);
		$sp1->lCounterVs[5]->subs = array(
			1 => new SubCounterView(	1	,'{group} user agent strings'	,'{number} with {value}'						,TwatchCounter::UA_STRINGS			,$allPTypes		,new TwatchEntityView(1,0,0)	,7		,0															)	
		);
		
		
		$sp1->lCounterVs[1]->graphView = new GraphView( 225, 140, 9, EntityVHour::STRING_NUMBER_24, EntityVHour::STRING_INTERVAL, array( 1, 5, 9, 13, 17, 21, 24 ) );
		$sp1->lCounterVs[7]->graphView = new GraphView( 225, 140, 27, EntityVWeekday::STRING_SHORT, EntityVWeekday::STRING_LONG, array(1, 2, 3, 4, 5, 6, 7 ) );
		
		StatsPage::$defaults = array( 
			TwatchUserData::STATS_PAGES => array( 1 => $sp1 )
		);
		
	}
	
?>
