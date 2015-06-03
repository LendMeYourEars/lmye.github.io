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
    	
	global $ardeBase, $ardeUser, $ardeUserProfile;
	if( !isset( $ardeUserProfile ) ) $ardeUserProfile = 'default';
	
	if( !isset( $ardeBase ) ) {
		$ardeUserSettings = ardeUserGetSettings( $ardeUserProfile );
		$ardeBaseProfile = $ardeUserSettings[ 'base_profile' ];
		require_once dirname(__FILE__).'/../'.$ardeUserSettings[ 'to_base' ].'/lib/Global.php';
	}

	function ardeUserGetSettings( $profile ) {
		$settings = array();
		include dirname(__FILE__).'/../profiles/'.$profile.'/settings.php';
		return $settings;
	}
		
	class ArdeUserApp extends ArdeAppWithPlugins {
		
		var $version = '0.150';
		
		var $name = 'ardeUser';
		
		var $db;
		
		var $config;
		
		var $user;

		var $rootUsernameUpdated = false;
		
		public function path( $target ) {
			return ardeSlashConcat( dirname(__FILE__).'/..', $target );
		}
		
		public function extPath( $extName, $target ) {
			if( preg_match( '/^(\w\:|\/)/', $this->settings[ 'to_'.$extName ] ) ) {
				return $this->settings[ 'to_'.$extName ].'/'.$target;
			} else {
				return ardeSlashConcat( dirname(__FILE__).'/..', $this->settings[ 'to_'.$extName ], $target );
			}
		}
		
		public static function initUser() {
			global $ardeUser;
			$ardeUser->user = ArdeUser::getUser();
			self::loadUserData();
		}
		
		public static function loadUserData() {
			global $ardeUser;
			require_once $ardeUser->path( 'data/DataUsers.php' );
			$ardeUser->user->data = new ArdeUserData( $ardeUser->user->id );
			$ardeUser->user->data->addDefaults( ArdeUserData::$defaultProperties );
			$ardeUser->user->data->loadChanges();
		}
		

	}
	
	class ArdeUserPlugin extends ArdePlugin {
		
		public function getLayer() {
			return $this->id;
		}
		
		public function afterUserDeleted( $userId ) {}
		
		public function applyConfigChanges( ArdeUserConfig $config, $ids ) {}

	}
	
	
	$ardeUser = new ArdeUserApp( $ardeBase );
	$ardeUser->loadSettings( $ardeUserProfile );
	$ardeUser->loadPlugins();
?>