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
    
	global $ardeUser;
	
	require_once $ardeUser->path( 'lib/User.php' );
	
	twatchMakeUserData();
	twatchMakeUserRelatedData();
	
	function twatchMakeUserData() {
		
		$d = array();
		$d[ TwatchUserData::VIEW_WEBSITE ] = true;
		
		TwatchUserData::$perWebsiteDef = $d;

		$d = array();
		$d[ TwatchUserData::VIEW_ENTITY ] = TwatchEntity::VIS_VISIBLE;
		
		TwatchUserData::$perEntityDef = $d;
		
		$d = array();
		$d[ TwatchUserData::VIEW_COUNTER ] = true;
		
		TwatchUserData::$perCounterDef = $d;
		
		$d = array();
		
		$d[ TwatchUserData::VIEW_REPORTS ][0] = true;
		$d[ TwatchUserData::VIEW_ADMIN ][0] = false;
		$d[ TwatchUserData::ADMINISTRATE ][0] = false;
		$d[ TwatchUserData::VIEW_ERRORS ][0] = false;
		$d[ TwatchUserData::VIEW_IPS ][0] = false;
		$d[ TwatchUserData::VIEW_COOKIE_IDS ][0] = false;
		$d[ TwatchUserData::VIEW_ADMIN_IN_LATEST ][0] = false;
		$d[ TwatchUserData::VIEW_PRIVATE_COMMENTS ][0] = false;
		$d[ TwatchUserData::HIDDEN_PROFILES ][0] = array();
		$d[ TwatchUserData::DEFAULT_WEBSITE ][0] = 1;
		$d[ TwatchUserData::VIEW_STATS ][0] = true;
		$d[ TwatchUserData::VIEW_LATEST ][0] = true;
		$d[ TwatchUserData::VIEW_PATH_ANALYSIS ][0] = true;
		$d[ TwatchUserData::CONFIG ][0] = false;
		$d[ TwatchUserData::DEFAULT_LANG ][ 0 ] = 'English';
		$d[ TwatchUserData::VIEW_ENTITY ] = array();
		$d[ TwatchUserData::VIEW_ENTITY ][ TwatchEntity::IP ] = TwatchEntity::VIS_SHOW_AS_HIDDEN;
		$d[ TwatchUserData::VIEW_ENTITY ][ TwatchEntity::PIP ] = TwatchEntity::VIS_SHOW_AS_HIDDEN;
		$d[ TwatchUserData::VIEW_ENTITY ][ TwatchEntity::FIP ] = TwatchEntity::VIS_SHOW_AS_HIDDEN;
		$d[ TwatchUserData::VIEW_ENTITY ][ TwatchEntity::RIP ] = TwatchEntity::VIS_SHOW_AS_HIDDEN;
		$d[ TwatchUserData::VIEW_ENTITY ][ TwatchEntity::PCOOKIE ] = TwatchEntity::VIS_SHOW_AS_HIDDEN;
		$d[ TwatchUserData::VIEW_ENTITY ][ TwatchEntity::SCOOKIE ] = TwatchEntity::VIS_SHOW_AS_HIDDEN;
		$d[ TwatchUserData::VIEW_ENTITY ][ TwatchEntity::ADMIN_COOKIE ] = TwatchEntity::VIS_HIDDEN;
		
		TwatchUserData::$defaultProperties[ TwatchUserData::DEF_DATA_PUBLIC ] = $d;
		
		$d[ TwatchUserData::VIEW_REPORTS ][0] = true;
		$d[ TwatchUserData::VIEW_ADMIN ][0] = true;
		$d[ TwatchUserData::ADMINISTRATE ][0] = true;
		$d[ TwatchUserData::VIEW_IPS ][0] = true;
		$d[ TwatchUserData::VIEW_COOKIE_IDS ][0] = true;
		$d[ TwatchUserData::VIEW_ADMIN_IN_LATEST ][0] = true;
		$d[ TwatchUserData::VIEW_PRIVATE_COMMENTS ][0] = true;
		$d[ TwatchUserData::CONFIG ][0] = true;
		$d[ TwatchUserData::VIEW_ENTITY ][ TwatchEntity::IP ] = TwatchEntity::VIS_VISIBLE;
		$d[ TwatchUserData::VIEW_ENTITY ][ TwatchEntity::PIP ] = TwatchEntity::VIS_VISIBLE;
		$d[ TwatchUserData::VIEW_ENTITY ][ TwatchEntity::FIP ] = TwatchEntity::VIS_VISIBLE;
		$d[ TwatchUserData::VIEW_ENTITY ][ TwatchEntity::RIP ] = TwatchEntity::VIS_VISIBLE;
		$d[ TwatchUserData::VIEW_ENTITY ][ TwatchEntity::PCOOKIE ] = TwatchEntity::VIS_VISIBLE;
		$d[ TwatchUserData::VIEW_ENTITY ][ TwatchEntity::SCOOKIE ] = TwatchEntity::VIS_VISIBLE;
		$d[ TwatchUserData::VIEW_ENTITY ][ TwatchEntity::ADMIN_COOKIE ] = TwatchEntity::VIS_VISIBLE;
		
		TwatchUserData::$defaultProperties[ TwatchUserData::DEF_DATA_ADMIN ] = $d;
		
		
		

	}
	
	function twatchMakeUserRelatedData() {
		$d = array();
		
		$d[ TwatchConfig::USER_GROUPS ] = array(
			 ArdeUserGroup::GROUP_PUBLIC => new ArdeUserGroup( ArdeUserGroup::GROUP_PUBLIC, 'public', TwatchUserData::DEF_DATA_PUBLIC )
			,ArdeUserGroup::GROUP_ADMIN => new ArdeUserGroup( ArdeUserGroup::GROUP_ADMIN, 'admin', TwatchUserData::DEF_DATA_ADMIN )
		);
		
		TwatchConfig::$userRelatedProperties = $d;
	}
?>