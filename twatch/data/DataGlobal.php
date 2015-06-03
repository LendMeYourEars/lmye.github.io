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
    
	require_once $twatch->path( 'lib/General.php' );
	require_once $twatch->path( 'lib/Common.php' );
	
	twatchMakeDefDataGlobal();
	
	function twatchMakeDefDataGlobal() {
		$conf = array();
		$state = array();
		
		
		$state[ TwatchState::OFF_ENTITY ] = array();
		$state[ TwatchState::OFF_COUNTER ] = array();
		$state[ TwatchState::OFF_ENTITY ][ TwatchEntity::REF_TYPE ] = true;
		$state[ TwatchState::PATH_ANALYZER_INSTALLED ][ 0 ] = false;
		$state[ TwatchState::PATH_NEXT_CLEANUP_ROUND ][ 0 ] = 1;
		$state[ TwatchState::USER_AGENT_CACHE_VALID ][ 0 ] = 1;
		$state[ TwatchState::SEARCHE_CACHE_VALID ][ 0 ] = 1;
		
		
		$conf[ TwatchConfig::COOKIE_KEYS ][ 0 ] = new TwatchCookieKeys( array( 349546728, 789347167, 229781588, 123878900 ) );
		
		$conf[ TwatchConfig::ADMIN_COOKIE ][ 0 ] = new TwatchAdminCookie( 'some unbelievably secure secret 34052305saaksfaksdfjlkqejrtiouwfhjsvo' );
		
		$conf[ TwatchConfig::PATH_ANALYZER ][ 0 ] = new TwatchPathAnalyzer( 1000, 25, 5, array( TwatchEntity::REF_GROUP ), 86400 * 5, 3 );
	
		$conf[ TwatchConfig::TIME_DIFFERENCE ][ 0 ] = 0;
		$conf[ TwatchConfig::TIME_ZONE_NAME ][ 0 ] = 'GMT';
		
		
		$conf[ TwatchConfig::LOGGER_WHEN ][ 0 ] = array();
		$conf[ TwatchConfig::COUNTERS_WHEN ][ 0 ] = array();
		
		$conf[ TwatchConfig::VERSION ][ 0 ] = '0.3';
		
		$conf[ TwatchConfig::INSTANCE_ID ][ 0 ] = 0;
		
		
		
		$conf[ TwatchConfig::DICTS ] = array(
			 TwatchDict::PAGE		=> new TwatchDict( 	TwatchDict::PAGE,		'page',					'pages',					30,				1 )
			,TwatchDict::AGENT		=> new TwatchDict( 	TwatchDict::AGENT,		'agent',				'agents',					30,				1 )
			,TwatchDict::IP			=> new TwatchDict( 	TwatchDict::IP,			'ip',					'ips',						7,				1 )
			,TwatchDict::SE_KEYWORD	=> new TwatchDict( 	TwatchDict::SE_KEYWORD,	'search engine-keyword','search engine-keywords',	15, 			1 )
			,TwatchDict::REF_DOMAIN	=> new TwatchDict( 	TwatchDict::REF_DOMAIN,	'domain',				'domains',					15,				TwatchEntGeneRefGroup::MAX_SEARCHE )
			,TwatchDict::REF		=> new TwatchDict( 	TwatchDict::REF,		'referrer',				'referrers',				15,				TwatchEntGeneProcRef::MAX_KEYWORDS )
		);
		
		$conf[ TwatchConfig::DICTS ][ TwatchDict::REF ]->keepAnyway = 15;
		$conf[ TwatchConfig::DICTS ][ TwatchDict::IP ]->keepAnyway = 4;
		
		
	
		$conf[ TwatchConfig::ENTITIES ] = array(
			 TwatchEntity::REF			=> new TwatchEntity( 'Referrer'					,'referred from {value}'			,new TwatchEntGeneRef( TwatchEntity::REF, 'referrer' )									)
			,TwatchEntity::REF_GROUP	=> new TwatchEntity( 'Referrer Group'			,'referred from {value}'			,new TwatchEntGeneRefGroup( TwatchEntity::REF_GROUP )						)
			,TwatchEntity::SE_KEYWORD	=> new TwatchEntity( 'Search Engine - Keyword'	,'reffered from {value}'			,new TwatchEntGeneSeKeyword( TwatchEntity::SE_KEYWORD )						)
			,TwatchEntity::PROC_REF		=> new TwatchEntity( 'Processed Referrer'		,'referred from {value}'			,new TwatchEntGeneProcRef( TwatchEntity::PROC_REF )							)
			,TwatchEntity::REF_TYPE		=> new TwatchEntity( 'Referrer Type'			,'referred from {value}'			,new TwatchEntGeneRefType( TwatchEntity::REF_TYPE )							)
			,TwatchEntity::AGENT_STR	=> new TwatchEntity( 'User Agent String'		,'with {value} user agent string'	,new TwatchEntGeneAgentString( TwatchEntity::AGENT_STR, 'agent' )								)
			,TwatchEntity::USER_AGENT	=> new TwatchEntity( 'User Agent'				,'with {value} user agent'			,new TwatchEntGeneUserAgent( TwatchEntity::USER_AGENT )							)
			,TwatchEntity::IP			=> new TwatchEntity( 'IP'						,'from {value}'						,new TwatchEntGeneIp( TwatchEntity::IP )										)
			,TwatchEntity::RIP			=> new TwatchEntity( 'Request IP'				,'from {value}'						,new TwatchEntGeneInputIp( TwatchEntity::RIP, 'ip' ) 					)
			,TwatchEntity::FIP			=> new TwatchEntity( 'Forwarded IP'				,'forwarding request for {value}'	,new TwatchEntGeneInputIp( TwatchEntity::FIP, 'fip' )		)
			,TwatchEntity::PIP			=> new TwatchEntity( 'Proxy IP'					,'using {value} proxy'				,new TwatchEntGenePip( TwatchEntity::PIP )									)
			,TwatchEntity::PAGE			=> new TwatchEntity( 'Page'						,'visiting {value}'					,new TwatchEntGenePage( TwatchEntity::PAGE, 'page' )									)
			,TwatchEntity::HOUR			=> new TwatchEntity( 'Hour'						,'visiting at {value}'				,new TwatchEntGeneHour( TwatchEntity::HOUR )									)
			,TwatchEntity::WEEKDAY		=> new TwatchEntity( 'Weekday'					,'visiting on {value}'				,new TwatchEntGeneWeekday( TwatchEntity::WEEKDAY )							)
			,TwatchEntity::SCOOKIE		=> new TwatchEntity( 'Session ID'				,'with {value} session id'			,new TwatchEntGeneCookie( TwatchEntity::SCOOKIE, 'scookie' )					)
			,TwatchEntity::PCOOKIE		=> new TwatchEntity( 'Visitor ID'				,'with {value} visitor id'			,new TwatchEntGeneCookie( TwatchEntity::PCOOKIE, 'pcookie' )					)
			,TwatchEntity::ADMIN_COOKIE	=> new TwatchEntity( 'Admin Cookie'				,'with admin cookie'				,new TwatchEntGeneAdminCookie( TwatchEntity::ADMIN_COOKIE, 'admin', 'has admin cookie' ) 				)
		);
		
		$conf[ TwatchConfig::ENTITIES ][ TwatchEntity::PAGE ]->unstoppable = true;
		$conf[ TwatchConfig::ENTITIES ][ TwatchEntity::IP ]->unstoppable = true;
		$conf[ TwatchConfig::ENTITIES ][ TwatchEntity::RIP ]->unstoppable = true;
	
		
		$conf[ TwatchConfig::ENTITIES ][ TwatchEntity::REF_GROUP ]->hasImage = true;
		$conf[ TwatchConfig::ENTITIES ][ TwatchEntity::PROC_REF ]->hasImage = true;
		$conf[ TwatchConfig::ENTITIES ][ TwatchEntity::USER_AGENT ]->hasImage = true;
		
		$conf[ TwatchConfig::RDATA_WRITERS ]=array(
			 TwatchRDataWriter::AGT_STR		=> new TwatchRDataWriter( 	TwatchRDataWriter::AGT_STR		,TwatchEntity::AGENT_STR,array( TwatchExpression::VALUE_CHANGED, TwatchEntity::AGENT_STR )	)
			,TwatchRDataWriter::AGT			=> new TwatchRDataWriter( 	TwatchRDataWriter::AGT			,TwatchEntity::USER_AGENT	,array( TwatchExpression::VALUE_CHANGED, TwatchEntity::USER_AGENT ))
			,TwatchRDataWriter::IP			=> new TwatchRDataWriter( 	TwatchRDataWriter::IP			,TwatchEntity::IP		,array( TwatchExpression::VALUE_CHANGED, TwatchEntity::IP )		)
			,TwatchRDataWriter::PIP			=> new TwatchRDataWriter( 	TwatchRDataWriter::PIP			,TwatchEntity::PIP		,array( TwatchExpression::VALUE_CHANGED, TwatchEntity::PIP )	)
			,TwatchRDataWriter::REF			=> new TwatchRDataWriter( 	TwatchRDataWriter::REF			,TwatchEntity::PROC_REF	,array( TwatchExpression::REQUEST_INFO, TwatchRequest::NEW_SESSION ))
			
			,TwatchRDataWriter::REF_GROUP 	=> new TwatchRDataWriter( 	TwatchRDataWriter::REF_GROUP	,TwatchEntity::REF_GROUP ,array( TwatchExpression::REQUEST_INFO, TwatchRequest::NEW_SESSION ))
			,TwatchRDataWriter::ADMIN_COOKIE=> new TwatchRDataWriter( 	TwatchRDataWriter::ADMIN_COOKIE	,TwatchEntity::ADMIN_COOKIE ,array( TwatchExpression::VALUE_CHANGED, TwatchEntity::ADMIN_COOKIE ))
		);
	
		$default_delete = array( TwatchPeriod::DAY => 90 );
		$default_trim = array( TwatchPeriod::DAY => array(3,20), TwatchPeriod::MONTH => array(1,20) );
		$defaultActiveTrim = array( TwatchPeriod::ALL => array(30,20) );
		$all_periods = array( TwatchPeriod::DAY, TwatchPeriod::MONTH, TwatchPeriod::ALL );
		$week_periods = array( TwatchPeriod::MONTH, TwatchPeriod::ALL );
		$default_counters = array();
		
		$cous = array(
			 TwatchCounter::PAGE_VIEWS	=> new TwatchSingleCounter(		TwatchCounter::PAGE_VIEWS	,'Page Views'			,$all_periods	,array(	TwatchExpression::VISITOR_TYPE_IS, TwatchVisitorType::NORMAL )																	,$default_delete																			)
			,TwatchCounter::SESSIONS	=> new TwatchSingleCounter(		TwatchCounter::SESSIONS		,'Sessions'				,$all_periods	,array( TwatchExpression::VISITOR_TYPE_IS, TwatchVisitorType::NORMAL, '&', TwatchExpression::REQUEST_INFO, TwatchRequest::NEW_SESSION )		,$default_delete																			)
			,TwatchCounter::VISITORS	=> new TwatchSingleCounter(		TwatchCounter::VISITORS		,'Unique Visitors'		,$all_periods	,array( TwatchExpression::VISITOR_TYPE_IS, TwatchVisitorType::NORMAL, '&', TwatchExpression::REQUEST_INFO, TwatchRequest::VISITOR_DAYFIRST )	,$default_delete																			)
			,TwatchCounter::NEW_VISITORS=> new TwatchSingleCounter(		TwatchCounter::NEW_VISITORS	,'New Visitors'			,$all_periods	,array( TwatchExpression::VISITOR_TYPE_IS, TwatchVisitorType::NORMAL, '&', TwatchExpression::REQUEST_INFO, TwatchRequest::NEW_VISITOR )		,$default_delete																			)
			,TwatchCounter::ROBOT_PVIEWS=> new TwatchSingleCounter(		TwatchCounter::ROBOT_PVIEWS	,'Robot Page Views'		,$all_periods	,array(	TwatchExpression::VISITOR_TYPE_IS, TwatchVisitorType::ROBOT )																	,$default_delete																			)
			,TwatchCounter::PAGES		=> new TwatchListCounter(		TwatchCounter::PAGES		,'Pages'				,$all_periods	,array( TwatchExpression::VISITOR_TYPE_IS, TwatchVisitorType::NORMAL )																	,$default_delete	,$default_trim	,TwatchEntity::PAGE, $defaultActiveTrim										)
			,TwatchCounter::REFGROUPS	=> new TwatchListCounter(		TwatchCounter::REFGROUPS	,'Referrer Groups'		,$all_periods	,array( TwatchExpression::VISITOR_TYPE_IS, TwatchVisitorType::NORMAL, '&', TwatchExpression::REQUEST_INFO, TwatchRequest::NEW_SESSION )		,$default_delete	,$default_trim	,TwatchEntity::REF_GROUP, $defaultActiveTrim								)
			,TwatchCounter::REFERRERS	=> new TwatchGroupedCounter(	TwatchCounter::REFERRERS	,'Referrers'			,$all_periods	,array( TwatchExpression::VISITOR_TYPE_IS, TwatchVisitorType::NORMAL, '&', TwatchExpression::REQUEST_INFO, TwatchRequest::NEW_SESSION )		,$default_delete	,$default_trim	,TwatchEntity::PROC_REF		,TwatchEntity::REF_GROUP, $defaultActiveTrim 	)
			,TwatchCounter::ROBOTS		=> new TwatchListCounter(		TwatchCounter::ROBOTS		,'Robots'				,$all_periods	,array( TwatchExpression::VISITOR_TYPE_IS, TwatchVisitorType::ROBOT )																		,$default_delete	,$default_trim	,TwatchEntity::USER_AGENT, array()									)
			,TwatchCounter::BROWSERS	=> new TwatchListCounter(		TwatchCounter::BROWSERS		,'Browsers'				,$all_periods	,array( TwatchExpression::VISITOR_TYPE_IS, TwatchVisitorType::NORMAL, '&', TwatchExpression::VALUE_CHANGED, TwatchEntity::USER_AGENT )			,$default_delete	,$default_trim	,TwatchEntity::USER_AGENT, array()									)
			,TwatchCounter::UA_STRINGS	=> new TwatchGroupedCounter(	TwatchCounter::UA_STRINGS	,'User Agents Strings'	,$all_periods	,array( '(', TwatchExpression::VISITOR_TYPE_IS, TwatchVisitorType::NORMAL, '|', TwatchExpression::VISITOR_TYPE_IS, TwatchVisitorType::ROBOT, ')', '&', TwatchExpression::VALUE_CHANGED, TwatchEntity::AGENT_STR )			,$default_delete	,$default_trim	,TwatchEntity::AGENT_STR		,TwatchEntity::USER_AGENT, $defaultActiveTrim 		)
			,TwatchCounter::DIST_HOURLY	=> new TwatchListCounter(		TwatchCounter::DIST_HOURLY	,'Hourly Distribution'	,$all_periods	,array( TwatchExpression::VISITOR_TYPE_IS, TwatchVisitorType::NORMAL, '&', TwatchExpression::REQUEST_INFO, TwatchRequest::NEW_SESSION )		,$default_delete	,array()		,TwatchEntity::HOUR, array()										)
			,TwatchCounter::DIST_WEEKLY	=> new TwatchListCounter(		TwatchCounter::DIST_WEEKLY	,'Weekday Distribution'	,$week_periods	,array( TwatchExpression::VISITOR_TYPE_IS, TwatchVisitorType::NORMAL, '&', TwatchExpression::REQUEST_INFO, TwatchRequest::NEW_SESSION )		,$default_delete	,array()		,TwatchEntity::WEEKDAY	, array()								)
		);
	
	
		$conf[TwatchConfig::COUNTERS] = $cous;
		
		$conf[ TwatchConfig::SEARCH_ENGINES ] = array(
			 TwatchSearchEngine::GOOGLE => new TwatchSearchEngine( TwatchSearchEngine::GOOGLE, 'Google', '/^ http\:\/\/ (?:www\.|) google\. .+? \/ (?: (?!url\?).*[\?|#] | url\?(?=.*url=) )  (?:.*&|)q=(?P<keyword>.+?)(?:$|&) /xi' )
			,TwatchSearchEngine::YAHOO => new TwatchSearchEngine( TwatchSearchEngine::YAHOO, 'Yahoo', '/^http\:\/\/.*search\.yahoo\.com\/.*search.*\?(?:.+&|)p=(?P<keyword>[^&]+)(?:$|&)/i' )
			,TwatchSearchEngine::GOOGLE_IMAGES_AREA => new TwatchSearchEngine( TwatchSearchEngine::GOOGLE_IMAGES_AREA, 'Google Images', '/^http\:\/\/(?:\w+\.|)google(?:\.\w+|)\.\w+\/imgres\?/i', false, false )
			,TwatchSearchEngine::GOOGLE_IMAGES => new TwatchSearchEngine( TwatchSearchEngine::GOOGLE_IMAGES, 'Google Images', '/^ http\:\/\/ images\.google\. .+? \/ imgres\? (?:.*&|)q=(?P<keyword>.+?)(?:$|&) /xi' )
			,TwatchSearchEngine::ALTAVISTA => new TwatchSearchEngine( TwatchSearchEngine::ALTAVISTA, 'Altavista', '/^http\:\/\/.*altavista\.com\/web\/results\?(?:.+&|)q=(?P<keyword>[^&]+)(?:$|&)/i' )
			,TwatchSearchEngine::MSN => new TwatchSearchEngine( TwatchSearchEngine::MSN, 'MSN', '/^http\:\/\/search\.msn\.com\/(?:.+&|.+\?|)q=(?P<keyword>[^&]+)(?:$|&)/i' )
			,TwatchSearchEngine::MY_WAY => new TwatchSearchEngine( TwatchSearchEngine::MY_WAY, 'MyWay mywebsearch.com', '/^http\:\/\/www\.mywebsearch\.com\/(?:.+&|.+\?|)searchfor=(?P<keyword>[^&]+)(?:$|&)/i' )
			,TwatchSearchEngine::AOL => new TwatchSearchEngine( TwatchSearchEngine::AOL, 'AOL', '/^http\:\/\/.+\.aol\.com\/(?:.+&|.+\?|)(?:enc|)query=(?P<keyword>[^&]+)(?:$|&)/i' )
			,TwatchSearchEngine::BING => new TwatchSearchEngine( TwatchSearchEngine::BING, 'Bing', '/^http\:\/\/.+\.bing\.com\/.+\?.*(?<=&|\?)q=(?P<keyword>[^&]+)/i' )
			,TwatchSearchEngine::YANDEX => new TwatchSearchEngine( TwatchSearchEngine::YANDEX, 'Yandex', '/^http\:\/\/.*yandex\..+\/yandsearch?.*(?<=&|\?)text=(?P<keyword>[^&]+)/i' )
		);
		
		$b = array();
		$b[ TwatchUserAgent::UNKNOWN ] = new TwatchUserAgent( TwatchUserAgent::UNKNOWN, 'Unknown', null, true );
		$b[ TwatchUserAgent::NETSCAPE ] = new TwatchUserAgent( TwatchUserAgent::NETSCAPE, 'Netscape Navigator', '/[ ](?:Netscape\d|Navigator)\//', true );
		$b[ TwatchUserAgent::MOZILLA ] = new TwatchUserAgent( TwatchUserAgent::MOZILLA, 'Mozilla', '/^Mozilla\/.+\(.+rv\:.+\)/i', true );
		$b[ TwatchUserAgent::FIREFOX ] = new TwatchUserAgent( TwatchUserAgent::FIREFOX, 'FireFox', '/firefox\//i', true );
		$b[ TwatchUserAgent::IE ] = new TwatchUserAgent( TwatchUserAgent::IE, 'Internet Explorer', '/^Mozilla\/.+\(.+MSIE.+\)/i', true );
		$b[ TwatchUserAgent::OPERA ] = new TwatchUserAgent( TwatchUserAgent::OPERA, 'Opera', '/(?:^Opera\/.+\(|^Mozilla\/.+\(.+\).+Opera)/i', true );
		$b[ TwatchUserAgent::SAFARI ] = new TwatchUserAgent( TwatchUserAgent::SAFARI, 'Safari', '/Safari\//i', true );
		$b[ TwatchUserAgent::LYNX ] = new TwatchUserAgent( TwatchUserAgent::LYNX, 'Lynx', '/^Lynx\/.+/i', true );
		$b[ TwatchUserAgent::KONQUEROR ] = new TwatchUserAgent( TwatchUserAgent::KONQUEROR, 'Konqueror', '/^(?:Konqueror\/|Mozilla\/.+\(.+Konqueror.+\))/i', true );
		$b[ TwatchUserAgent::CHROME ] = new TwatchUserAgent( TwatchUserAgent::CHROME, 'Google Chrome', '/Chrome\//i', true );
		
		$b[ TwatchUserAgent::ROBOT_GOOGLE ] = new TwatchUserAgent( TwatchUserAgent::ROBOT_GOOGLE, 'Google Robot', '/Googlebot\//', true );
		$b[ TwatchUserAgent::ROBOT_GOOGLE_IMAGES ] = new TwatchUserAgent( TwatchUserAgent::ROBOT_GOOGLE_IMAGES, "Google Images Robot", "/^googlebot-image\//i", true );
		$b[ TwatchUserAgent::ROBOT_YAHOO ] = new TwatchUserAgent( TwatchUserAgent::ROBOT_YAHOO, 'Yahoo Robot', '/(Yahoo\-MMCrawler|Slurp)/i', true );
		$b[ TwatchUserAgent::ROBOT_BING ] = new TwatchUserAgent( TwatchUserAgent::ROBOT_BING, 'Bing', '/bingbot\//i', true );
		
		$b[ 1103 ] = new TwatchUserAgent( 1103, "NG", "/^ng\//i" );
		$b[ 1104 ] = new TwatchUserAgent( 1104, "Pompos", "/^Pompos/" );
		$b[ 1105 ] = new TwatchUserAgent( 1105, "TAMU_CS_IRL_CRAWLER", "/^TAMU_CS_IRL_CRAWLER/" );
		$b[ 1106 ] = new TwatchUserAgent( 1106, "Gaisbot", "/^Gaisbot/" );
		$b[ 1107 ] = new TwatchUserAgent( 1107, "MSNbot", "/msnbot/", true );
		$b[ 1108 ] = new TwatchUserAgent( 1108, "Gigabot", "/^Gigabot/" );
		$b[ 1109 ] = new TwatchUserAgent( 1109, "Alexa", "/^ia_archiver/" );
		$b[ 1110 ] = new TwatchUserAgent( 1110, "Baidu spider", "/baiduspider/i" );
		$b[ 1111 ] = new TwatchUserAgent( 1111, "Asterias", "/^asterias/" );
		$b[ 1112 ] = new TwatchUserAgent( 1112, "sohu-search", "/^sohu-search/" );
		$b[ 1113 ] = new TwatchUserAgent( 1113, "Altavista", "/^(?:altavista|scooter)/i" );
		$b[ 1114 ] = new TwatchUserAgent( 1114, "NaverBot", "/^NaverBot/" );
		$b[ 1115 ] = new TwatchUserAgent( 1115, "Mozi!", "/^Mozi\!$/" );
		$b[ 1116 ] = new TwatchUserAgent( 1116, "Google Adsense", "/^Mediapartners-Google/i", true );
		$b[ 1117 ] = new TwatchUserAgent( 1117, "FindLinks", "/^findlinks/i" );
		$b[ 1118 ] = new TwatchUserAgent( 1118, "psbot", "/^psbot/i" );
		$b[ 1119 ] = new TwatchUserAgent( 1119, "YottaCars_Bot", "/^YottaCars_Bot/i" );
		$b[ 1120 ] = new TwatchUserAgent( 1120, "NutchCVS", "/^NutchCVS/i" );
		$b[ 1121 ] = new TwatchUserAgent( 1121, "almaden.ibm.com crawler", "/^http:\/\/www.almaden.ibm.com\/cs\/crawler/i" );
		$b[ 1122 ] = new TwatchUserAgent( 1122, "SurveyBot", "/^SurveyBot/i" );
		$b[ 1123 ] = new TwatchUserAgent( 1123, "KnowItAll", "/^KnowItAll/i" );
		$b[ 1124 ] = new TwatchUserAgent( 1124, "MusicWalker", "/^MusicWalker/i" );
		$b[ 1125 ] = new TwatchUserAgent( 1125, "Faxobot", "/^Faxobot/i" );
		$b[ 1126 ] = new TwatchUserAgent( 1126, "USyd-NLP-Spider", "/^USyd\-NLP\-Spider/i" );
		$b[ 1127 ] = new TwatchUserAgent( 1127, "WSB", "/^WSB/" );
		$b[ 1128 ] = new TwatchUserAgent( 1128, "Amfibibot", "/^Amfibibot/i" );
		$b[ 1129 ] = new TwatchUserAgent( 1129, "webcrawl.net", "/^webcrawl\.net/i" );
		$b[ 1130 ] = new TwatchUserAgent( 1130, "libwww-perl", "/^libwww\-perl/i" );
		$b[ 1131 ] = new TwatchUserAgent( 1131, "webcollage", "/^webcollage/i" );
		$b[ 1132 ] = new TwatchUserAgent( 1132, "www.scrubtheweb.com", "/^Scrubby/i" );
		$b[ 1133 ] = new TwatchUserAgent( 1133, "www.fast-search-engine.com", "/http\:\/\/www\.fast\-search\-engine\.com\//i" );
		$b[ 1134 ] = new TwatchUserAgent( 1134, "GeonaBot", "/^GeonaBot/i" );
		$b[ 1135 ] = new TwatchUserAgent( 1135, "wwwster", "/^wwwster/i" );
		$b[ 1136 ] = new TwatchUserAgent( 1136, "FyberSpider", "/^FyberSpider/i" );
		$b[ 1137 ] = new TwatchUserAgent( 1137, "EmeraldShield.com", "/^EmeraldShield\.com/i" );
		$b[ 1138 ] = new TwatchUserAgent( 1138, "TurnitinBot", "/^TurnitinBot/i" );
		$b[ 1139 ] = new TwatchUserAgent( 1139, "boitho.com-dc", "/^boitho\.com\-dc/i" );
		$b[ 1140 ] = new TwatchUserAgent( 1140, "grub.org grub-client", "/grub\-client/i" );
		$b[ 1141 ] = new TwatchUserAgent( 1141, "Ask.com", "/Ask\sJeeves\/Teoma/i", true );
		$b[ 1142 ] = new TwatchUserAgent( 1142, "Become.com BecomeBot", "/BecomeBot/i" );
		$b[ 1143 ] = new TwatchUserAgent( 1143, "wisenutbot.com", "/ZyBorg/i" );
		$b[ 1144 ] = new TwatchUserAgent( 1144, "W3C Validator", "/W3C_Validator/i" );
		$b[ 1145 ] = new TwatchUserAgent( 1145, "lwp-trivial", "/lwp\-trivial/i" );
		$b[ 1146 ] = new TwatchUserAgent( 1146, "SiteUptime.com", "/SiteUptime/i" );
		$b[ 1147 ] = new TwatchUserAgent( 1147, "WinHttp", "/^WinHttp$/" );
		$b[ 1148 ] = new TwatchUserAgent( 1148, "Yandex", "/Yandex\w*\//i", true );
		$b[ 1149 ] = new TwatchUserAgent( 1149, "EntireWeb", "/Speedy[ ]Spider/i", true );
		$b[ 1150 ] = new TwatchUserAgent( 1150, "Sosospider", "/^Sosospider/i" );
		$b[ 1151 ] = new TwatchUserAgent( 1151, "metadatalabs.com", "/^MLBot/i" );
		$b[ 1152 ] = new TwatchUserAgent( 1152, "Sogou web spider", "/^Sogou/i" );
		$b[ 1153 ] = new TwatchUserAgent( 1153, "Naver", "/^Yeti\//", true );
		$b[ 1154 ] = new TwatchUserAgent( 1154, "ScoutJet", '/ScoutJet/' );
		$b[ 1155 ] = new TwatchUserAgent( 1155, "VoilaBot", '/VoilaBot/' );
		$b[ 1156 ] = new TwatchUserAgent( 1156, "Exalead", '/Exabot/' );
		$b[ 1157 ] = new TwatchUserAgent( 1157, "Facebook", '/facebookexternalhit\//i' );
		$b[ 1158 ] = new TwatchUserAgent( 1158, "ChangeDetection.com", '/changedetection/i' );
		$b[ 1159 ] = new TwatchUserAgent( 1159, "100pulse.com", '/100pulse\//i' );
		$b[ 1160 ] = new TwatchUserAgent( 1160, "ICC-Crawler", '/ICC\-Crawler\//i' );
		$b[ 1161 ] = new TwatchUserAgent( 1161, "Twiceler", '/Twiceler\-/i' );
		$b[ 1162 ] = new TwatchUserAgent( 1162, "Balaena.com", '/WinWebBot\//i' );
		$b[ 1163 ] = new TwatchUserAgent( 1163, "Jabse.com", '/jabse\.com/i' );
		$b[ 1164 ] = new TwatchUserAgent( 1164, "tasap.com", '/tasapspider\//i' );
		$b[ 1165 ] = new TwatchUserAgent( 1165, "Filecrop.com", '/filecrop\//i' );
		$b[ 1166 ] = new TwatchUserAgent( 1166, "Butterfly", '/Butterfly\//i' );
		$b[ 1167 ] = new TwatchUserAgent( 1167, "setooz.com", '/(?:setooz|oozbot)/i' );
		$b[ 1168 ] = new TwatchUserAgent( 1168, 'DotNetDotCom.org', '/DotBot\//i' );
		$b[ 1169 ] = new TwatchUserAgent( 1169, 'Google AdWords', '/AdsBot\-Google/i' );
		$b[ 1170 ] = new TwatchUserAgent( 1170, 'Twenga.com', '/TwengaBot\//i' );
		$b[ 1171 ] = new TwatchUserAgent( 1171, '192.com', '/192.comAgent/i' );
		$b[ 1172 ] = new TwatchUserAgent( 1172, 'LucidMedia.com', '/LucidMedia ClickSense\//i' );
		$b[ 1173 ] = new TwatchUserAgent( 1173, 'Comodo Certificates Spider', '/Comodo\-Certificates\-Spider/i' );
		$b[ 1174 ] = new TwatchUserAgent( 1174, 'Google Feedfetcher', '/Feedfetcher\-Google/i' );
		$b[ 1175 ] = new TwatchUserAgent( 1175, 'TencentTraveler', '/TencentTraveler/i' );
		$b[ 1176 ] = new TwatchUserAgent( 1176, 'wise-guys.nl', '/Vagabondo/i' );
		$b[ 1177 ] = new TwatchUserAgent( 1177, 'atraxsolutions.com', '/atraxbot\//i' );
		$b[ 1178 ] = new TwatchUserAgent( 1178, 'Daumoa', '/Daumoa\//i' );
		$b[ 1179 ] = new TwatchUserAgent( 1179, 'accelobot.com', '/accelobot\.com/i' );
		$b[ 1180 ] = new TwatchUserAgent( 1180, 'sitebot.org', '/sitebot\//i' );
		$b[ 1181 ] = new TwatchUserAgent( 1181, 'twenga.fr', '/TwengaBot/i' );
		$b[ 1182 ] = new TwatchUserAgent( 1182, '7esoo.com', '/7esoorobot/i' );
		$b[ 1183 ] = new TwatchUserAgent( 1183, 'dotnetdotcom.org', '/DotBot\//i' );
		$b[ 1184 ] = new TwatchUserAgent( 1184, 'Ezooms', '/Ezooms\//i' );
		$b[ 1185 ] = new TwatchUserAgent( 1185, 'majestic12.co.uk', '/MJ12bot\//i' );
		$b[ 1186 ] = new TwatchUserAgent( 1186, 'Thriceler', '/Thriceler/i' );
		$b[ 1187 ] = new TwatchUserAgent( 1187, 'puritysearch.net', '/Purebot\//i' );
		$b[ 1188 ] = new TwatchUserAgent( 1188, 'nigma.ru', '/Nigma.ru\//i' );
		$conf[ TwatchConfig::USER_AGENTS ] = $b;
		
		$conf[ TwatchConfig::VISITOR_TYPES ] = array(
			 TwatchVisitorType::NORMAL	=> new TwatchVisitorType( TwatchVisitorType::NORMAL	,'Normal Visitor', array() )
			,TwatchVisitorType::ROBOT	=> new TwatchVisitorType( TwatchVisitorType::ROBOT	,'Robot', array( '(', TwatchExpression::ENTITY, TwatchEntity::USER_AGENT, '>=',  ArdeExpression::INT, 1000, '&', TwatchExpression::ENTITY, TwatchEntity::USER_AGENT, '<',  ArdeExpression::INT, 10000, ')' ) )
			,TwatchVisitorType::ADMIN	=> new TwatchVisitorType( TwatchVisitorType::ADMIN	,'Administrator', array() )
			,TwatchVisitorType::SPAMMER=> new TwatchVisitorType( TwatchVisitorType::SPAMMER	,'Spammer', array() )
		);
		
		$conf[ TwatchConfig::VISITOR_TYPES ][ TwatchVisitorType::ADMIN ]->predefinedIds[] = new TwatchDbVisitorTypeId( TwatchEntity::ADMIN_COOKIE, TwatchEntGeneNull::EXISTS );
		
		
		$conf[TwatchConfig::WEBSITES]=array(
			 1	=> new TwatchWebsite(	1	,'Main Website'	,'default'			)
		);
		
		$conf[TwatchConfig::LATEST][0] = new TwatchLatest( 3 );
			
		TwatchConfig::$defaultProperties = $conf;
		TwatchState::$defaultProperties = $state;
		
		TwatchState::$extraDefaults = array(
			 TwatchState::COUNTERS_AVAIL => array()
			,TwatchState::DICT_STATES => array()
		);
		
		foreach( $conf[ TwatchConfig::COUNTERS ] as $id => $counter ) {
			$cavail = new TwatchCounterAvailability();
			$cavail->cid = $id;
			TwatchState::$extraDefaults[ TwatchState::COUNTERS_AVAIL ][ $id ] = $cavail;
		}
		
	}
?>