<?php
	require_once $twatch->path( 'lib/ReportGeneral.php' );
	
	function getSelectedUser( $withWebsite = false, $extraDefData = null ) {
		global $twatch, $ardeUser;

		if( $ardeUser->user->hasPermission( TwatchUserData::ADMINISTRATE ) ) {
			
			if( isset( $_GET[ 'user' ] ) ) {
				$id = $_GET[ 'user' ];
				if( $id == ArdeUser::USER_PUBLIC ) {
					$selectedUser = new ArdeUserPublic();
				} else {
					$users = new ArdeUsers();
					$selectedUser = $users->getUserById( $id, $twatch->config->get( TwatchConfig::INSTANCE_ID ) );
					if( !$selectedUser ) $selectedUser = new ArdeAllUsers();
				}
			} elseif( isset( $_GET[ 'group' ] ) ) {
				$id = $_GET[ 'group' ];
				if( $twatch->config->propertyExists( TwatchConfig::USER_GROUPS, $id ) ) {
					$selectedUser = $twatch->config->get( TwatchConfig::USER_GROUPS, $id );
				} else {
					$selectedUser = new ArdeAllUsers();
				}
			} else {
				$selectedUser = new ArdeAllUsers();
			}
		} elseif( $ardeUser->user->hasPermission( TwatchUserData::CONFIG ) ) {
			$selectedUser = $ardeUser->user;
		} else {
			$selectedUser = new ArdeAllUsers();
		}
		
		if( $selectedUser instanceof ArdeUserGroup ) {
			if( !$selectedUser->id ) {
				$groupId = null;
			} else {
				$groupId = $selectedUser->id;
			}
			$userId = null;
		} else {
			$groupId = $selectedUser->groupId;
			$userId = $selectedUser->id;
		}
		
		if( $groupId ) {
			$group = $twatch->config->get( TwatchConfig::USER_GROUPS, $groupId );
		} else {
			$group = $selectedUser;
		}
		
		$selectedUser->data = getUserData( $groupId, $userId, $group->defaultPropertiesId, $extraDefData === null ? array() : array( $extraDefData ) ); /*new TwatchUserData( $groupId, $userId );
		
		$perWebsiteDef = array();
		foreach( TwatchUserData::$perWebsiteDef as $key => $value ) {
			$perWebsiteDef[ $key ] = array();
			foreach(  $twatch->config->getList( TwatchConfig::WEBSITES ) as $id => $website ) {
				if( $website->parent ) continue;
				
				$perWebsiteDef[ $key ][ $id ] = $value;
			}
		}
		$selectedUser->data->addDefaults( $perWebsiteDef );
		
		$perEntityDef = array();
		foreach( TwatchUserData::$perEntityDef as $key => $value ) {
			$perEntityDef[ $key ] = array();
			foreach( $twatch->config->getList( TwatchConfig::ENTITIES ) as $id => $entity ) {
				$perEntityDef[ $key ][ $id ] = $value;
			}
		}
		$selectedUser->data->addDefaults( $perEntityDef );
		
		
		$selectedUser->data->addDefaults( TwatchUserData::$defaultProperties[ $group->defaultPropertiesId ] );

		
		
		if( $extraDefData !== null ) {
			$selectedUser->data->addDefaults( $extraDefData );
		}
		
		
		
		$selectedUser->data->loadChanges();*/
		
		
		
		if( $withWebsite ) {
			$websiteId = twatchGetSelectedWebsiteId( twatchGetDefaultWebiteId(), true );
			if( $websiteId ) {
				$selectedUser->data->loadChanges( $websiteId );
			}
		}
		return $selectedUser;
	}
?>