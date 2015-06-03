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
	
	class EntityVCountry extends EntityV {
		
		protected $jsClassName = 'EntityVCountry';
		
		
		function finalizeAttempt1() {
			global $ardeBase, $ardeCountry;
			if( isset( TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ] ) ) {
				$GLOBALS[ 'ardeCountryProfile' ] = TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ];
			}
			require_once TwatchCountryPlugin::$object->ardeCountryPath( 'lib/Global.php' );
			require_once $ardeCountry->path( 'lib/Country.php' );

			
			
			$this->str = ArdeCountry::getIdName( $this->id );
			
			if( $this->str === null ) {
				$this->str = 'unknown country id '.$this->id;
			}
			return false;
		}
		
		

		public function getString( $id ) {
			global $twatch, $ardeCountry;
			if( isset( TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ] ) ) {
				$GLOBALS[ 'ardeCountryProfile' ] = TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ];
			}
			require_once TwatchCountryPlugin::$object->ardeCountryPath( 'lib/Global.php' );
			if( $ardeCountry->locale->id != $twatch->locale->id && $ardeCountry->localeExists( $twatch->locale->id ) ) {
				$ardeCountry->loadLocale( $twatch->locale->id );
			}
			return $ardeCountry->locale->text( $this->str );
			
		}

		
		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			global $ardeBase, $ardeCountry, $twatch;
			if( isset( TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ] ) ) {
				$GLOBALS[ 'ardeCountryProfile' ] = TwatchCountryPlugin::$object->settings[ 'arde_country_profile' ];
			}
			require_once TwatchCountryPlugin::$object->ardeCountryPath( 'lib/Global.php' );
			if( $ardeCountry->locale->id != $twatch->locale->id && $ardeCountry->localeExists( $twatch->locale->id ) ) {
				$ardeCountry->loadLocale( $twatch->locale->id );
			}
			require_once $ardeCountry->path( 'lib/Country.php' );
			require_once $ardeCountry->path( 'lib/IdToName.php' );
			
			$a = array();
			foreach( ArdeCountry::getIdNames() as $id => $name ) {
				$a[ $id ] = $ardeCountry->locale->text( $name );
			}
			
			ardeUtf8Sort( $a );
			return self::getArrayIds( $a, $offset, $count, $beginWith );
		}
		
	}
	
	class ArdeCountryEntityVIp extends ArdeCountryEntityVIpParent {
		protected function dictRes( $res ) {
			global $twatch, $ardeUser;
			if( !$twatch->config->propertyExists( TwatchConfig::ENTITIES, TwatchCountryPlugin::$object->startId + TwatchCountryPlugin::ENTITY ) ) return;
			if( $ardeUser->user->data->get( TwatchUserData::VIEW_ENTITY, TwatchCountryPlugin::$object->startId + TwatchCountryPlugin::ENTITY ) == TwatchEntity::VIS_HIDDEN ) return;
			if( $res->cache1 != 0 ) {
				$this->cou = new EntityVCountry( TwatchCountryPlugin::$object->startId + TwatchCountryPlugin::ENTITY, $res->cache1 );
				$this->cou->finalizeAttempt1();
			}
		}
	}
?>