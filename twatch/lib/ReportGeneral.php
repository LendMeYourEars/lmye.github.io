<?php
	function getUserData( $groupId, $userId, $defaultPropertiesId, $extraDefProps = array() ) {
		global $twatch;
		
		$data = new TwatchUserData( $groupId, $userId );
		
		$perWebsiteDef = array();
		foreach( TwatchUserData::$perWebsiteDef as $key => $value ) {
			$perWebsiteDef[ $key ] = array();
			foreach(  $twatch->config->getList( TwatchConfig::WEBSITES ) as $id => $website ) {
				if( $website->parent ) continue;
				
				$perWebsiteDef[ $key ][ $id ] = $value;
			}
		}
		$data->addDefaults( $perWebsiteDef );
		
		$perEntityDef = array();
		foreach( TwatchUserData::$perEntityDef as $key => $value ) {
			
			$perEntityDef[ $key ] = array();
			foreach( $twatch->config->getList( TwatchConfig::ENTITIES ) as $id => $entity ) {
				$perEntityDef[ $key ][ $id ] = $value;
			}
		}
		$data->addDefaults( $perEntityDef );
		
		$perCounterDef = array();
		foreach( TwatchUserData::$perCounterDef as $key => $value ) {
			$perCounterDef[ $key ] = array();
			foreach( $twatch->config->getList( TwatchConfig::COUNTERS ) as $id => $counter ) {
				$perCounterDef[ $key ][ $id ] = $value;
			}
		}
		$data->addDefaults( $perCounterDef );
		
		$data->addDefaults( TwatchUserData::$defaultProperties[ $defaultPropertiesId ] );
		
		
		foreach( $extraDefProps as $defProps ) {
			$data->addDefaults( $defProps );
		}
		
		
		
		$data->loadChanges();
		return $data;
	}
	
	function initUser( $extraDefProps = array() ) {
		global $ardeBase, $ardeUser, $twatch, $ardeUserProfile;
		
		require_once $ardeUser->path( 'lib/User.php' );
		require_once $twatch->path( 'data/DataUsers.php' );
		$ardeUser->db = new ArdeDb( $ardeUser->settings );
		$ardeUser->db->connect();
		
		$ardeUser->user = ArdeUser::getUser( $twatch->config->get( TwatchConfig::INSTANCE_ID ) );
		$group = $twatch->config->get( TwatchConfig::USER_GROUPS, $ardeUser->user->groupId );
		
		$ardeUser->user->data = getUserData( $group->id, $ardeUser->user->id, $group->defaultPropertiesId, $extraDefProps );		
	}
?>