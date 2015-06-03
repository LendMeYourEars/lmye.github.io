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
	
	global $ardeBase;
	
	require_once $ardeBase->path( 'lib/General.php' );
	require_once $ardeBase->path( 'lib/ArdeProperties.php' );
	require_once $ardeBase->path( 'lib/ArdeDisTaskManager.php' );
	require_once $ardeBase->path( 'lib/ArdeDbErrorReporter.php' );
	
	class TwatchTime extends ArdeTime {
		public static function getTime( $ts = 0 ) {
			return new self( $ts );
		}
		public function __clone() {
			return new self( $this->ts );
		}
		
		public function duplicate() {
			return new self( $this->ts );
		}

	}
	
	function twatchSetAppTime() {
		global $twatch;
		$twatch->now = new TwatchTime( time() + $twatch->config->get( TwatchConfig::TIME_DIFFERENCE ) );
	} 
	
	class TwatchException extends ArdeException {
		protected $class = "TraceWatch Error";
	}
	
	class TwatchWarning extends ArdeException {
		protected $class = "TraceWatch Warning";
		protected $type = ArdeException::WARNING;
	}
	
	
	class TwatchUserError extends TwatchException {
		protected $class = "TraceWatch User Error"; 
		protected $type = ArdeException::USER_ERROR;
	}
	
	class TwatchNoPermission extends TwatchUserError {
		public function __construct( $message = null ) {
			parent::__construct( $message === null ? 'No Permission' : $message );
		}
	}

	class TwatchTaskManager extends ArdeDisTaskManager {
		public function __construct() {
			global $twatch;
			parent::__construct( $twatch->db, '', $twatch->now );
		}
	}
	
	
	class TwatchPropertyChangesDb extends ArdePropertyChangesDb {
		public $websiteId = 0;
		
		public function __construct( $db, $tableName ) {
			parent::__construct( $db, '', $tableName, array( 'wid' ) );
		}
		
		protected function getExtraKeyValues() {
			return array( 'wid' => $this->websiteId );
		}
	}
	
	abstract class TwatchProperties extends ArdeAdminProperties {
		const USER_LAYER = 1000;
		
		const IDS_PER_LAYER = 500000;
		
		protected $db;
		
		function __construct( ArdeDb $db = null ) {
			$this->db = $db;
			$this->changes = new ArdePropertyChanges();
		}
		
		public function getLayerStartId( $layer ) {
			return $layer * self::IDS_PER_LAYER;
		}
		
		protected function applyUserChanges() {
			$this->advanceLayerTo( self::USER_LAYER );
			$changes = $this->changes->getChanges( array_keys( $this->data ) );
			$this->applyChanges( $changes );
		}
		
		public function applyAllChanges() {
			$this->applyPluginChanges();
			$this->applyUserChanges();
			
		}
		
		abstract protected function getTableName();
		
		public function advanceLayerTo( $layer ) {
			if( $layer == $this->layer ) return;
			if( $layer == self::USER_LAYER ) {
				$this->changes =  new TwatchPropertyChangesDb( $this->db, $this->getTableName() );
			}
			parent::advanceLayerTo( $layer );
		}
		
		public function install( $overwrite ) {
			$changes = new TwatchPropertyChangesDb( $this->db, $this->getTableName() );
			$changes->install( $overwrite );
		}
		
		public function uninstall() {
			$changes = new TwatchPropertyChangesDb( $this->db, $this->getTableName() );
			$changes->uninstall();
		}
	}
	
	class TwatchConfig extends TwatchProperties {

		public static $defaultProperties;
		public static $userRelatedProperties;
		
		const COUNTERS = 1;
		const ENTITIES = 2;
		const WEBSITES = 3;
		const RDATA_WRITERS = 4;
		const PATH_ANALYZER = 6;
		const DICTS = 7;
		const VISITOR_TYPES = 9;
		const LATEST = 10;
		const USER_AGENTS = 19;
		const SEARCH_ENGINES = 20;
		const TIME_DIFFERENCE = 21;
		const TIME_ZONE_NAME = 22;
		const COOKIE_KEYS = 23;
		const ADMIN_COOKIE = 24;
		const LOGGER_WHEN = 26;
		const COUNTERS_WHEN = 27;
		const VERSION = 28;
		const PLUGIN_VERSIONS = 29;
		const USER_GROUPS = 30;
		const INSTANCE_ID = 31;
		
		
		public static $idStrings = array(
			 self::COUNTERS => 'Counters'
			,self::ENTITIES => 'Entities'
			,self::WEBSITES => 'Websites'
			,self::RDATA_WRITERS => 'RData Writers'
			,self::PATH_ANALYZER => 'Path Analyzer'
			,self::DICTS => 'Dicts'
			,self::VISITOR_TYPES => 'Visitor Types'
			,self::LATEST => 'Latest'
			,self::USER_AGENTS => 'User Agents'
			,self::SEARCH_ENGINES => 'Search Engines'
			,self::COOKIE_KEYS => 'Cookie Keys'
			,self::LOGGER_WHEN => 'Logger When'
			,self::COUNTERS_WHEN => 'Counters When'
		);
		
		public function getIdString( $id ) {
			if( !isset( TwatchConfig::$idStrings[ $id ] ) ) return $id;
			return $id.' ('.TwatchConfig::$idStrings[ $id ].')';
		}
		
		protected function getTableName() { return 'c'; }

		protected function applyPluginChanges() {
			global $twatch;
			foreach( $twatch->plugins as $plugin ) {
				$this->advanceLayerTo( $plugin->getLayer() );
				$plugin->startId = $this->getStartId();
				$plugin->applyConfigChanges( $this, $this->data );
			}
			
		}
		
		

	}
	
	class TwatchState extends TwatchProperties {
		
		public static $defaultProperties;
		
		const COUNTERS_AVAIL = 2;
		const PATH_NEXT_CLEANUP_ROUND = 3;
		const PATH_ANALYZER_INSTALLED = 4;
		const OFF_ENTITY =  5;
		const DICT_STATES =  6;
		const OFF_COUNTER = 7;
		const USER_AGENT_CACHE_VALID = 8;
		const SEARCHE_CACHE_VALID = 9;
		
		public static $extraDefaults;
		
		public static $idStrings = array(
			 self::COUNTERS_AVAIL => 'Counters Avail'
			,self::PATH_NEXT_CLEANUP_ROUND => 'Path Next Cleanup Round'
			,self::PATH_ANALYZER_INSTALLED => 'Path Analyzer Installed'
			,self::OFF_ENTITY =>  'Off Entity'
			,self::DICT_STATES =>  'Dict States'
		);
		
		public function getIdString( $id ) {
			if( !isset( TwatchState::$idStrings[ $id ] ) ) return $id;
			return $id.' ('.TwatchState::$idStrings[ $id ].')';
		}
		
		protected function getTableName() { return 's'; }
		
		protected function applyPluginChanges() {
			global $twatch;
			foreach( $twatch->plugins as $plugin ) {
				$this->advanceLayerTo( $plugin->id );
				$plugin->startId = $this->getStartId();
				$plugin->applyStateChanges( $this, $this->data );
			}
			
		}
		
	}
	
	class TwatchUserDataChanges extends ArdePropertyChangesDb {
		public $userType;
		public $userId;
		public $websiteId;
		public function __construct( $db ) {
			parent::__construct( $db, '', 'ud', array( 'utype', 'uid', 'wid' ) );
		}
		
		protected function getExtraKeyValues() {
			return array( 'utype' => $this->userType, 'uid' => $this->userId, 'wid' => $this->websiteId );
		}
		
		public function copyGroup( $fromId, $toId ) {
			$this->copyData( array( 'utype' => TwatchUserData::TYPE_GROUP, 'uid' => $fromId ), array( 'utype' => TwatchUserData::TYPE_GROUP, 'uid' => $toId ) );
		}
		
		public function clearGroup( $id ) {
			$this->clearData( array( 'utype' => TwatchUserData::TYPE_GROUP, 'uid' => $id ) );
		}
		
		public function clearUser( $id ) {
			$this->clearData( array( 'utype' => TwatchUserData::TYPE_USER, 'uid' => $id ) );
		}  
		
		public function clearWebsite( $id ) {
			$this->clearData( array( 'wid' => $id ) );
		}  
	}
	
	class TwatchUserData extends ArdeAdminProperties {
		
		const VIEW_REPORTS = 1;
		const VIEW_ADMIN = 2;
		const ADMINISTRATE = 3;
		const VIEW_ERRORS = 4;
		const KEYS = 5;
		const VIEW_IPS = 6;
		const VIEW_COOKIE_IDS = 7;
		const VIEW_ADMIN_IN_LATEST = 8;
		const VIEW_PRIVATE_COMMENTS = 9;
		const HIDDEN_PROFILES = 11;
		const VIEW_WEBSITE = 12;
		const DEFAULT_WEBSITE = 13;
		const STATS_PAGES = 14;
		const LATEST_PAGE = 15;
		const VIEW_STATS = 16;
		const VIEW_LATEST = 17;
		const VIEW_PATH_ANALYSIS = 18;
		const CONFIG = 19;
		const DEFAULT_LANG = 20;
		const VIEW_ENTITY = 21;
		const VIEW_COUNTER = 22;
		
		const DEF_DATA_PUBLIC = 1;
		const DEF_DATA_ADMIN = 2;
		
		const LAYER_ALL = 1000;
		const LAYER_GROUP = 1010;
		const LAYER_USER = 1020;
		const LAYER_WEBSITE_ALL = 1030;
		const LAYER_WEBSITE_GROUP = 1040;
		const LAYER_WEBSITE_USER = 1050;
		
		const TYPE_USER = 1;
		const TYPE_GROUP = 2;
		
		protected $groupId;
		protected $userId;
		protected $websiteId;
		
		public static $defaultProperties;
		
		public static $perWebsiteDef;
		public static $perEntityDef;
		public static $perCounterDef;
		
		public static $idStrings = array(
			 self::VIEW_REPORTS => 'View Reports'
			,self::VIEW_ADMIN => 'View Admin'
			,self::ADMINISTRATE => 'Administrate'
		);
		
		public function getLayerStartId( $layer ) {
			return $layer * 500000;
		}
		
		public function getIdString( $id ) {
			if( !isset( self::$idStrings[ $id ] ) ) return $id;
			return $id.' ('.self::$idStrings[ $id ].')';
		}
		
		public function __construct( $groupId, $userId ) {
			global $twatch;
			parent::__construct();
			$this->groupId = $groupId;
			$this->userId = $userId;
			$this->websiteId = 0;
			$this->changes = new TwatchUserDataChanges( $twatch->db );
		}
		
		public function install( $overwrite ) {
			$this->changes->install( $overwrite );
		}
		
		public function uninstall() {
			$this->changes->uninstall();
		}
		
		public function loadChanges( $websiteId = 0 ) {
			$this->changes->userType = 0;
			$this->changes->userId = 0;
			$this->changes->websiteId = $websiteId;
			
			if( !$websiteId ) $this->applyPluginChanges();
			
			$this->changes->queueGetChanges( 1, array_keys( $this->data ) );

			
			if( $this->groupId !== null ) {
				
				$this->changes->userType = self::TYPE_GROUP;
				$this->changes->userId = $this->groupId;
				$this->changes->queueGetChanges( 2, array_keys( $this->data ) );

				if( $this->userId !== null ) {
					$this->changes->userType = self::TYPE_USER;
					$this->changes->userId = $this->userId;
					$this->changes->queueGetChanges( 3, array_keys( $this->data ) );
				}
			}
			
			$changes = $this->changes->rollGetChanges();
			
			
			$this->advanceLayerTo( $websiteId ? self::LAYER_WEBSITE_ALL : self::LAYER_ALL );
			$this->applyChanges( $changes[1] );
			if( $this->groupId !== null ) {
				$this->advanceLayerTo( $websiteId ? self::LAYER_WEBSITE_GROUP : self::LAYER_GROUP );
				$this->applyChanges( $changes[2] );
				if( $this->userId !== null ) {
					$this->advanceLayerTo( $websiteId ? self::LAYER_WEBSITE_USER : self::LAYER_USER );
					$this->applyChanges( $changes[3] );
				}
			}
		}
		
		public function copyGroup( $fromId, $toId ) {
			$this->changes->copyGroup( $fromId, $toId );
		}
		
		public function clearGroup( $id ) {
			$this->changes->clearGroup( $id );
		}
		
		public function clearUser( $id ) {
			$this->changes->clearUser( $id );
		}
		
		public function clearWebsite( $id ) {
			$this->changes->clearWebsite( $id );
		}
		
		public function clearId( $id, $subId = null ) {
			$this->changes->clearId( $id, $subId );
		}
		
		protected function applyPluginChanges() {
			global $twatch;
			foreach( $twatch->plugins as $plugin ) {
				$this->advanceLayerTo( $plugin->id );
				$plugin->startId = $this->getStartId();
				$plugin->applyUserDataChanges( $this, $this->data );
			}
			
		}
	}
	
	class TwatchErrorLogger extends ArdeDbErrorReporter {
		public function __construct( ArdeErrorReporter $secondReporter = null, $storeRestrictions = false ) {
			global $twatch;
			parent::__construct( $twatch->db, '', 'err', $secondReporter, $storeRestrictions );
		}
	}
	
	function twatchGetDefaultWebiteId() {
		global $twatch, $ardeUser;
		$defaultWebsiteId = $ardeUser->user->data->get( TwatchUserData::DEFAULT_WEBSITE );
		
		if( !$twatch->config->propertyExists( TwatchConfig::WEBSITES, $defaultWebsiteId ) || !$ardeUser->user->hasPermission( TwatchUserData::VIEW_WEBSITE, $defaultWebsiteId ) ) {
			$defaultWebsiteId = null;
			foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $id => $website ) {
				if( $website->parent ) continue;
				if( !$ardeUser->user->hasPermission( TwatchUserData::VIEW_WEBSITE, $id ) ) continue;
				$defaultWebsiteId = $id;
				break;
			}
		}
		
		if( $defaultWebsiteId == null ) throw new ArdeUserError( 'No Permission' );
		
		return $defaultWebsiteId;
	}
	
	function twatchGetSelectedWebsiteId( $defaultWebsiteId, $withAll ) {
		global $twatch, $ardeUser;

		if( isset( $_GET[ 'website' ] ) ) {
			$websiteId = (int)$_GET[ 'website' ];
			if( !$twatch->config->propertyExists( TwatchConfig::WEBSITES, $websiteId ) ) $websiteId = $defaultWebsiteId;
			else if( $twatch->config->get( TwatchConfig::WEBSITES, $websiteId )->parent ) $websiteId = $defaultWebsiteId;
			else if( !$ardeUser->user->hasPermission( TwatchUserData::VIEW_WEBSITE, $websiteId ) ) $websiteId = $defaultWebsiteId;
		} else {
			if( $withAll ) {
				$websiteId = 0;	
			} else {
				$websiteId = $defaultWebsiteId;
			}
		}

		return $websiteId;
		
	}
	
	$twatch->applyOverrides( array( 'twatchSetAppTime' => true ) );
	
?>