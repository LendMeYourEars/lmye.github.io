<?php
	require_once $ardeBase->path( 'lib/ArdeProperties.php' );
	require_once $ardeBase->path( 'lib/ArdeDbErrorReporter.php' );
	
	class ArdeUserPropertyChangesDb extends ArdePropertyChangesDb {
		public function __construct( $db, $tableName ) {
			parent::__construct( $db, '', $tableName );
		}
		
		protected function getExtraKeyValues() {
			return array();
		}
	}

	abstract class ArdeUserProperties extends ArdeAdminProperties {
		const USER_LAYER = 1000;
		
		const IDS_PER_LAYER = 500000;
		
		protected $db;
		
		function __construct( ArdeDb $db = null ) {
			$this->db = $db;
			$this->changes = new ArdePropertyChanges();
			/*if( $db != null ) {
				$this->changes = new TwatchPropertyChanges( $db, 'c' );
			} else {
				$this->changes = new ArdePropertyChanges();
			}*/
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
				$this->changes =  new ArdeUserPropertyChangesDb( $this->db, $this->getTableName() );
			}
			parent::advanceLayerTo( $layer );
		}
		
		public function install( $overwrite ) {
			$changes = new ArdeUserPropertyChangesDb( $this->db, $this->getTableName() );
			$changes->install( $overwrite );
		}
		
		public function uninstall() {
			$changes = new ArdeUserPropertyChangesDb( $this->db, $this->getTableName() );
			$changes->uninstall();
		}
	}

	class ArdeUserConfig extends ArdeUserProperties {

		public static $defaultProperties;
		
		const DEFAULT_LANG = 1;
		const VERSION = 2;
		const PLUGIN_VERSIONS = 3;
		const INSTANCE_ID = 4;
		
		public static $idStrings = array(
			 self::DEFAULT_LANG => 'Default Language'
			,self::VERSION => 'Version'
			,self::PLUGIN_VERSIONS => 'Plugin Versions'
		);
		
		public function getIdString( $id ) {
			if( !isset( self::$idStrings[ $id ] ) ) return $id;
			return $id.' ('.self::$idStrings[ $id ].')';
		}
		
		protected function getTableName() { return 'c'; }

		protected function applyPluginChanges() {
			global $ardeUser;
			foreach( $ardeUser->plugins as $plugin ) {
				$this->advanceLayerTo( $plugin->getLayer() );
				$plugin->startId = $this->getStartId();
				$plugin->applyConfigChanges( $this, $this->data );
			}
			
		}

	}
	
	class ArdeUserDataChanges extends ArdePropertyChangesDb {
		public $userId;
		
		public function __construct( $db, $userId ) {
			$this->userId = $userId;
			parent::__construct( $db, '', 'ud', array( 'uid' ) );
		}
		
		protected function getExtraKeyValues() {
			return array( 'uid' => $this->userId );
		}
		
		
	}
	
	class ArdeUserErrorLogger extends ArdeDbErrorReporter {
		public function __construct( ArdeErrorReporter $secondReporter = null, $storeRestrictions = false ) {
			global $ardeUser;
			parent::__construct( $ardeUser->db, '', 'err', $secondReporter, $storeRestrictions );
		}
	}
	
	class ArdeUserData extends ArdeAdminProperties {
		
		const ADMINISTRATE = 1;
		const VIEW_ERRORS = 2;
		
		public static $defaultProperties;
		
		public static $idStrings = array(
			 self::ADMINISTRATE => 'Administrate'
			,self::VIEW_ERRORS => 'View Errors'
		);
		
		public function getLayerStartId( $layer ) {
			return 500000;
		}
		
		public function getIdString( $id ) {
			if( !isset( self::$idStrings[ $id ] ) ) return $id;
			return $id.' ('.self::$idStrings[ $id ].')';
		}
		
		public function __construct( $userId ) {
			global $ardeUser;
			parent::__construct();
			$this->changes = new ArdeUserDataChanges( $ardeUser->db, $userId );
		}
		
		public function install( $overwrite ) {
			$this->changes->install( $overwrite );
		}
		
		public function uninstall() {
			$this->changes->uninstall();
		}
		
		public function loadChanges() {
			$changes = $this->changes->getChanges( array_keys( $this->data ) );
			$this->applyChanges( $changes );
		}
		
		
	}
	

	
	
	
?>