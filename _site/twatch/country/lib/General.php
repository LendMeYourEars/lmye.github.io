<?php
	require_once $ardeBase->path( 'lib/ArdeProperties.php' );
	
	class ArdeCountryPropertyChangesDb extends ArdePropertyChangesDb {
		public function __construct( $db, $tableName ) {
			parent::__construct( $db, '', $tableName );
		}
		
		protected function getExtraKeyValues() {
			return array();
		}
	}

	abstract class ArdeCountryProperties extends ArdeAdminProperties {
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
			//$this->applyPluginChanges();
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

	class ArdeCountryConfig extends ArdeCountryProperties {

		public static $defaultProperties;
		
		const INSTANCE_ID = 1;
		const VERSION = 2;
		const PLUGIN_VERSIONS = 3;
		const DB_SOURCE = 4;
		
		
		
		
		
		protected function getTableName() { return 'c'; }


	}
?>