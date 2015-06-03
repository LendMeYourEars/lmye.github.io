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

	global $ardeBase, $ardeBaseProfile, $twatch, $twatchProfile;

	if( !isset( $twatchProfile ) ) $twatchProfile = 'default';

	if( !file_exists( dirname(__FILE__).'/../profiles/'.$twatchProfile ) ) {
		echo "profile '".$twatchProfile."' doesn't exist, using default.";
		$twatchProfile = 'default';
	}

	function TwatchGetExtPath( $rootPath, $toExt, $target ) {
		if( preg_match( '/^(\w\:|\/)/', $toExt ) ) {
			return $toExt.'/'.$target;
		} else {
			return $rootPath.'/'.$toExt.'/'.$target;
		}
	}
	
	if( !isset( $ardeBase ) ) {
		$twatchSettings = twatchGetSettings( $twatchProfile );
		$ardeBaseProfile = $twatchSettings[ 'base_profile' ];
		require_once TwatchGetExtPath( dirname(__FILE__).'/..', $twatchSettings[ 'to_base' ], 'lib/Global.php' );
	}

	function twatchGetSettings( $profile ) {
		$settings = array();
		include dirname(__FILE__).'/../profiles/'.$profile.'/settings.php';
		return $settings;
	}

	class TwatchApp extends ArdeAppWithPlugins {

		var $version = "0.353";

		var $name = 'twatch';

		var $now;

		var $db;

		var $config;

		var $state;

		protected function getPluginClassName() {
			return 'TwatchPlugin';
		}
		
		public function getCopyrightYears() {
			return '2004-2011';
		}
		
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


	}

	function twatchConnect() {
		global $twatch;
		if( !isset( $twatch->db ) ) {
			$twatch->db = new ArdeDb( $twatch->settings );
			$twatch->db->connect();
		}
	}

	class TwatchPlugin extends ArdePlugin {

		public $startId;

		public function getLayer() {
			return $this->id;
		}

		public function getStartId() { return $this->startId; }

		public function applyConfigChanges( TwatchConfig $config, $ids ) {}

		public function applyStateChanges( TwatchState $state, $ids ) {}

		public function applyUserdataChanges( TwatchUserData $userData, $ids ) {}
		
		public function afterTwatchInstall( ArdePrinter $p ) {}
	}

	$twatch = new TwatchApp( $ardeBase );
	$twatch->loadSettings( $twatchProfile );
	$twatch->loadPlugins();


?>