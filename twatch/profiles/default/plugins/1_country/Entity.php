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
		
	class TwatchEntGeneIpCountry extends TwatchEntityGene {
		
		public $valueClassName = 'EntityVCountry';
		private $ipEntityId;
		
		public function __construct( $ipEntityId, $entityId ) {
			parent::__construct( $entityId );
			$this->ipEntityId = $ipEntityId;
		}
		
		protected static function _fromParams( $a, $prefix, $new, $className, $entityId ) {
			global $twatch;
			$ipEntityId = $twatch->config->get( TwatchConfig::ENTITIES, $entityId )->gene->ipEntityId;
			return new $className( $ipEntityId, $entityId );
		}
		
		public function getPrecedents() {
			return array( $this->ipEntityId );
		}
		
		public function attempt2( TwatchRequest $request ) {
			global $ardeBase, $ardeCountry, $twatch;
			
			if( !isset( $request->doneGenes[ $this->ipEntityId ] ) ) return false;
			$ipGen = $request->doneGenes[ $this->ipEntityId ];
			
			if( isset( $ipGen->origin ) && $ipGen->origin != null ) {
				$origEntityId = $ipGen->origin;
			} else {
				$origEntityId = $this->ipEntityId;
			}
			
			$updatingCache = false;
			
			if( isset( $request->dict->results[ $origEntityId ] ) ) {
				if( $request->dict->results[ $origEntityId ]->timestamp < $twatch->state->get( TwatchCountryPlugin::$object->startId + TwatchCountryPlugin::STATE_CACHE_VALID ) ) {
					$updatingCache = true;
				} elseif( $request->dict->results[ $origEntityId ]->cache1 != 0 ) {
					$this->valueId = $request->dict->results[ $origEntityId ]->cache1;
					return true;
				}
			}
			if( isset( TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ] ) ) {
				$GLOBALS[ 'ardeCountryProfile' ] = TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ];
			}
			require_once TwatchCountryPlugin::$object->ardeCountryPath( 'lib/Global.php' );
			require_once $ardeCountry->path( 'lib/Country.php' );
			
			$ardeCountry->db = new ArdeDb( $ardeCountry->settings );
			$ardeCountry->db->connect();
			
			$this->valueId = ArdeCountry::fetchCountry( $ipGen->valueId );
			
			if( $updatingCache ) {
				$request->dict->updateCache( TwatchDict::IP, $request->dict->results[ $origEntityId ]->id, 1, $this->valueId );
			} else {
				$request->dict->putCache( $origEntityId , 1, $this->valueId );
			}
			return true;
		}
		
		public function getSerialData() {
			$d = parent::getSerialData();
			$d->data[] = $this->ipEntityId;
			return $d;
		}
		
		
		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[1], $d->data[0] );
		}
	}
	
	class TwatchEntGeneCountry extends TwatchEntityGene {
		
		public $valueClassName = 'EntityVCountry';
		
		public function getPrecedents() {
			return array( TwatchCountryPlugin::$object->startId + TwatchCountryPlugin::IP_COUNTRY_ENTITY, 
				TwatchCountryPlugin::$object->startId + TwatchCountryPlugin::PIP_COUNTRY_ENTITY );
		}
		
		public function attempt2( TwatchRequest $request ) {
			if( isset( $request->doneGenes[ TwatchCountryPlugin::$object->startId + TwatchCountryPlugin::IP_COUNTRY_ENTITY ] ) ) {
				$this->valueId = $request->doneGenes[ TwatchCountryPlugin::$object->startId + TwatchCountryPlugin::IP_COUNTRY_ENTITY ]->valueId;
				return true; 
			} elseif( isset( $request->doneGenes[ TwatchCountryPlugin::$object->startId + TwatchCountryPlugin::PIP_COUNTRY_ENTITY ] ) ) {
				$this->valueId = $request->doneGenes[ TwatchCountryPlugin::$object->startId + TwatchCountryPlugin::PIP_COUNTRY_ENTITY ]->valueId;
				return true;
			}
			return false;
		}
		
		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0] );
		}
		
		public function allowImport() {
			return true;
		}
		
		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntCountryPasvGene( $dict, $mode, $context );
		}
	}
?>