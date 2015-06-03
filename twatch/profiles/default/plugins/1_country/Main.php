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
		
	class TwatchCountryPlugin extends TwatchPlugin {
		const ENTITY = 1;
		const IP_COUNTRY_ENTITY = 2;
		const PIP_COUNTRY_ENTITY = 3;
		const COUNTER = 1;
		const DATA_WRITER = 1;
		const STATE_CACHE_VALID = 1;
		
		public $settings;
		
		public $countryEntityId;

		
		public function init() {
			$setting = array();
			include $this->path( 'settings.php' );
			$this->settings = $settings;
		}
		
		public function ardeCountryPath( $target ) {
			global $twatch;
			return $twatch->path( $this->settings[ 'arde_country_path' ].'/'.$target );
		}
		
		public function ardeCountryUrl( $toRoot, $target ) {
			global $twatch;
			return $twatch->url( $toRoot, $this->settings[ 'arde_country_path' ].'/'.$target );
		}
		
		public function getName() {
			return 'Country';
		}
		
		public function getVersion() {
			return '0.106';
		}
		
		public function getOverrides() {
			return array( 
				 'EntityVIp' => new ArdeClassOverride( 'ArdeCountryEntityVIp', $this->path( 'EntityV.php' ) )
				,'TwatchPage' => new ArdeClassOverride( 'TwatchCountryPage', $this->path( 'TwatchPage.php' ) )
				,'TwatchInstallPage' => new ArdeClassOverride( 'TwatchCountryInstallPage', $this->path( 'TwatchInstallPage.php' ) )
				,'TwatchUninstallPage' => new ArdeClassOverride( 'TwatchCountryUninstallPage', $this->path( 'TwatchUninstallPage.php' ) )
				,'AboutPage' => new ArdeClassOverride( 'CountryAboutPage', $this->path( 'AboutPage.php' ) )
				,'AdminIndexPage' => new ArdeClassOverride( 'CountryAdminIndexPage', $this->path( 'AdminIndex.php' ) )
				,'AdminGeneralRec' => new ArdeClassOverride( 'CountryAdminGeneralRec', $this->path( 'AdminGeneralRec.php' ) )
			);
		}
		
		public function getInclude( $position ) {
			if( $position == 'entity' ) {
				return $this->path( 'Entity.php' );
			} elseif( $position == 'passive_gene' ) {
				return $this->path( 'PassiveGene.php' );
			}
			return null;
		}
		
		public function needsInstall() { return true; }
		
		public function needsUninstall() { return true; }
		
		public function hasInstallForm() { return true; }
		
		public function printInInstallForm( ArdePrinter $p ) {
			$p->pl( '<p><label><input type="checkbox" name="start_counter" checked="checked" /> start the countries counter</label></p>' );
		}
		
		public function install( ArdePrinter $p ) {
			global $twatch;
			$p->pn( '<p>Installing the counter... ' );
			$counter = $twatch->config->get( TwatchConfig::COUNTERS, $this->getStartId() + self::COUNTER );
			$counter->install();
			$p->pl( '<span class="good">successful</span></p>' );
			$p->pn( '<p>Starting the counter... ' );
			if( isset( $_GET[ 'start_counter' ] ) ) {
				try {
					$counter->start();
				} catch( ArdeException $e ) {
					ArdeException::reportError( $e );
				}
			}
			$p->pl( '<span class="good">successful</span></p>' );
		}
		
		
		
		public function uninstall( ArdePrinter $p ) {
			global $twatch;
			$p->pn( '<p>Deleting latest visitors data... ' );
			$dataWriter = $twatch->config->get( TwatchConfig::RDATA_WRITERS, $this->getStartId() + self::DATA_WRITER );
			$dataWriter->removeData();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>Uninstalling counter... ' );
			$counter = $twatch->config->get( TwatchConfig::COUNTERS, $this->getStartId() + self::COUNTER );
			$counter->uninstall();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>Deleting IPs\' cache... ' );
			require_once( $twatch->path( 'db/DbPassiveDict.php' ) );
			$dict = $twatch->config->get( TwatchConfig::DICTS, TwatchDict::IP );
			$dict->clearCache( 1 );
			$p->pl( '<span class="good">successful</span></p>' );
		}
		

		public function applyConfigChanges( TwatchConfig $config, $ids ) {

			
			
			$entityId = $config->getStartId() + self::IP_COUNTRY_ENTITY;
			$entity = new TwatchEntity( 'IP Country', 'from {value}', new TwatchEntGeneIpCountry( TwatchEntity::IP, $entityId ) );
			$config->addToBottom( $entity, TwatchConfig::ENTITIES, $entityId );
			
			$entityId = $config->getStartId() + self::PIP_COUNTRY_ENTITY;
			$entity = new TwatchEntity( 'Proxy IP Country', 'from {value}', new TwatchEntGeneIpCountry( TwatchEntity::PIP, $entityId ) );
			$config->addToBottom( $entity, TwatchConfig::ENTITIES, $entityId );
			
			$this->countryEntityId = $config->getStartId() + self::ENTITY;
			$entity = new TwatchEntity( 'Country', 'from {value}', new TwatchEntGeneCountry( $this->countryEntityId ) );
			$entity->hasImage = true;
			$config->addToBottom( $entity, TwatchConfig::ENTITIES, $this->countryEntityId );
			
			$default_delete = array( TwatchPeriod::DAY => 90 );
			$default_trim = array( TwatchPeriod::DAY => array(3,20), TwatchPeriod::MONTH => array(1,20) );
			$defaultActiveTrim = array( TwatchPeriod::ALL => array(30,20) );
			$all_periods = array( TwatchPeriod::DAY, TwatchPeriod::MONTH, TwatchPeriod::ALL );
			$counterId = $config->getStartId() + self::COUNTER;
			$counter = new TwatchListCounter( $counterId, 'Countries', $all_periods, array( TwatchExpression::VISITOR_TYPE_IS, TwatchVisitorType::NORMAL, '&', TwatchExpression::REQUEST_INFO, TwatchRequest::NEW_SESSION ), $default_delete, $default_trim, $this->countryEntityId, $defaultActiveTrim );
			$config->addToBottom( $counter, TwatchConfig::COUNTERS, $counterId );
			
			$dataWriter = new TwatchRDataWriter( $config->getStartId() + self::DATA_WRITER, $config->getStartId() + self::ENTITY, array( TwatchExpression::VALUE_CHANGED, $this->countryEntityId ) );
			$config->addToBottom( $dataWriter, TwatchConfig::RDATA_WRITERS, $config->getStartId() + self::DATA_WRITER );
			
			
		}

		public function applyStateChanges( TwatchState $state, $ids ) {
			$defState = array( $state->getStartId() + self::STATE_CACHE_VALID => array( 0 => 1 ) );
			$state->addDefaults( $defState );
			
			if( isset( $ids[ TwatchState::COUNTERS_AVAIL ] ) ) {
				$cavail = new TwatchCounterAvailability();
				$cavail->cid = $state->getStartId() + self::COUNTER;
				$state->set( $cavail, TwatchState::COUNTERS_AVAIL, $cavail->cid );
			}
		}
		
		public function applyUserDataChanges( TwatchUserData $userData, $ids ) {
			global $twatch;
			if( isset( $ids[ TwatchUserData::STATS_PAGES ] ) ) {
				$statsPage = &$userData->get( TwatchUserData::STATS_PAGES, 1 );
				$allPTypes = array( TwatchPeriod::DAY, TwatchPeriod::MONTH, TwatchPeriod::ALL );
				$id = 0;
				foreach( $statsPage->lCounterVs as $cvid => $lcv ) {
					if( $cvid > $id ) $id = $cvid + 1;
				}
				$statsPage->lCounterVs[ $id ] = new ListCounterView( $id, 'Countries', '{number} visitors from {value}', $userData->getStartId() + self::COUNTER, $allPTypes, new TwatchEntityView(1,1,0) );
			}
			
			if( isset( $ids[ TwatchUserData::LATEST_PAGE ] ) ) {
				$latestPage = &$userData->get( TwatchUserData::LATEST_PAGE );
				$newSecItems = array();
				$newSecItems[ 0 ] = new TwatchLatestItem( $this->countryEntityId, new TwatchEntityView( 1, 1, 0 ), TwatchLatestItem::LOOKUP_LAST, null, 'Unknown Country' );
				foreach( $latestPage->secItems as $i => $secItem ) {
					$newSecItems[ $i ] = $secItem;
				}
				$latestPage->secItems = $newSecItems;
			}
		}
		
		public static $object;
	}
	
	$pluginObject = TwatchCountryPlugin::$object = new TwatchCountryPlugin();
?>