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
    
	$adminConfig = true;
	require_once dirname(__FILE__).'/../../lib/RecGlobalHead.php';
	loadConfig();
	
	$ardeUserProfile = $twatch->settings[ 'user_profile' ];
	require_once $twatch->extPath( 'user', 'lib/Global.php' );
	require_once $ardeUser->path( 'lib/User.php' );
	require_once $twatch->path( 'data/DataUsers.php' );
	
	requirePermission( TwatchUserData::ADMINISTRATE );
	
	if( !isset( $_POST['a'] ) ) throw new TwatchException( 'Action was not sent' );
	
	require_once $twatch->path( 'lib/AdminGeneral.php' );
	
if( $_POST[ 'a' ] == 'set_def_website' ) {
		$selectedUser = getSelectedUser();
		if( !$ardeUser->user->hasPermission( TwatchUserData::ADMINISTRATE ) && !$selectedUser->is( $ardeUser->user ) ) {
			throw new ArdeUserError( 'No Permission' );
		}
		$id = ArdeParam::int( $_POST, 'v', 0 );
		if( !$twatch->config->propertyExists( TwatchConfig::WEBSITES, $id ) ) throw new ArdeUserError( 'website with id '.$id.' not found.');
		if( !$selectedUser->hasPermission( TwatchUserData::VIEW_WEBSITE, $id ) ) throw new ArdeUserError( 'user doesn\'t have permission to view website with id '.$id ); 
		$website = $twatch->config->get( TwatchConfig::WEBSITES, $id );
		if( $website->parent ) throw new ArdeUserError( 'website '.$id.' is a sub-website.' );
		$selectedUser->data->set( $id, TwatchUserData::DEFAULT_WEBSITE );
		if( !$xhtml ) {
			$p->pl( '<successful />' );
		}
	} elseif( $_POST[ 'a' ] == 'restore_def_website' ) {
		$selectedUser = getSelectedUser();
		$selectedUser->data->restoreDefault( TwatchUserData::DEFAULT_WEBSITE );
		if( !$xhtml ) {
			$p->pl( '<result>'.$selectedUser->data->get( TwatchUserData::DEFAULT_WEBSITE ).'</result>' );
		}
	}  elseif( $_POST[ 'a' ] == 'add_group' ) {
		$name = ArdeParam::str( $_POST, 'n' );
		if( $name == '' ) throw new ArdeUserError( 'Please enter a name.' );
		
		$copyId = ArdeParam::int( $_POST, 'c', 0 );
		if( !$twatch->config->propertyExists( TwatchConfig::USER_GROUPS, $copyId ) ) {
			throw new ArdeUserError( 'Invalid group '.$copyId.' to copy from.' );
		}
		$copyGroup = $twatch->config->get( TwatchConfig::USER_GROUPS, $copyId );
		
		$id = $twatch->config->getNewSubId( TwatchConfig::USER_GROUPS );
		$group = new ArdeUserGroup( $id, $name, $copyGroup->defaultPropertiesId );
		$twatch->config->addToBottom( $group, TwatchConfig::USER_GROUPS, $id );
		
		$userData = new TwatchUserData( null, null );
		$userData->copyGroup( $copyId, $id );
		
		if( !$xhtml ) {
			$group->printAdminXml( $p, 'result' );
			$p->nl();
		}
	} elseif( $_POST[ 'a' ] == 'delete_group' ) {
		$id = ArdeParam::int( $_POST, 'i' );
		if( $id == ArdeUserGroup::GROUP_ADMIN ) {
			throw new ArdeUserError( 'You can not delete the admin group' );
		}
		if( $id == ArdeUserGroup::GROUP_PUBLIC ) {
			throw new ArdeUserError( 'You can not delete the public group' );
		}
		if( !$twatch->config->propertyExists( TwatchConfig::USER_GROUPS, $id ) ) {
			throw new ArdeUserError( 'Group '.$copyId.' not found.' );
		}
		
		$reassignId = ArdeParam::int( $_POST, 'ri' );
		if( !$twatch->config->propertyExists( TwatchConfig::USER_GROUPS, $reassignId ) ) {
			throw new ArdeUserError( 'Group '.$copyId.' not found.' );
		}
		$ardeUsers = new ArdeUsers();
		$ardeUsers->reassignUsers( $id, $reassignId, $twatch->config->get( TwatchConfig::INSTANCE_ID ) );
		$twatch->config->remove( TwatchConfig::USER_GROUPS, $id );
		$userData = new TwatchUserData( null, null );
		$userData->clearGroup( $id );
		if( !$xhtml ) {
			$p->pl( '<successful />' );
		}
	} elseif( $_POST[ 'a' ] == 'set_user_group' ) {
		$userId = ArdeParam::int( $_POST, 'i' );
		$users = new ArdeUsers();
		if( $userId == ArdeUser::USER_PUBLIC || $userId == ArdeUser::USER_ROOT ) throw new ArdeUserError( 'You can\'t change public or root user groups.' );
		
		if( $users->getUserById( $userId ) === null ) throw new ArdeUserError( 'User '.$userId.' not found.' );

		
		$groupId = ArdeParam::int( $_POST, 'g' );
		if( !$twatch->config->propertyExists( TwatchConfig::USER_GROUPS, $groupId ) ) {
			throw new ArdeUserError( 'Group with id '.$groupId.' not found.' );
		}
		$users->setUserGroup( $userId, $groupId, $twatch->config->get( TwatchConfig::INSTANCE_ID ) );
		if( !$xhtml ) {
			$p->pl( '<successful />' );
		}
	} elseif( $_POST[ 'a' ] == 'change' ) {
		$id = ArdeParam::int( $_POST, 'i' );
		if( !$twatch->config->propertyExists( TwatchConfig::USER_GROUPS, $id ) ) {
			throw new ArdeUserError( 'Group '.$copyId.' not found.' );
		}
		$group = &$twatch->config->get( TwatchConfig::USER_GROUPS, $id );
		
		$name = ArdeParam::str( $_POST, 'n' );
		if( $name == '' ) throw new ArdeUserError( 'Please enter a name.' );
		
		if( $name == $group->name ) throw new ArdeUserError( 'Nothing to change.' );
		
		$group->name = $name;
		
		$twatch->config->setInternal( TwatchConfig::USER_GROUPS, $id );
		
		if( !$xhtml ) {
			$p->pl( '<successful />' );
		}
		
	} elseif( $_POST[ 'a' ] == 'get_users' ) {
		
		
		$offset = ArdeParam::int( $_POST, 'o', 0 );
		$count = ArdeParam::int( $_POST, 'c', 0 );
		if( isset( $_POST[ 'bw' ] ) ) {
			$beginWith = $_POST[ 'bw' ];
		} else {
			$beginWith = null;
		}
		
		$count++;
		
		$passiveUsers = array();
		
		$passiveUsers[] = new ArdeUserGroup( 0, 'All Users' );
		
		foreach( $twatch->config->getList( TwatchConfig::USER_GROUPS ) as $group ) {
			$passiveUsers[] = $group;
		}
		
		$passiveUsers[] = new ArdeUserPublic();
		
		if( $beginWith !== null ) {
			foreach( $passiveUsers as $k => $passiveUser ) {
				if( stripos( $passiveUser->name, $beginWith ) !== 0 ) {
					unset( $passiveUsers[ $k ] );
				}
			}
		}
		
		$users = array_slice( $passiveUsers, $offset, $count );
		
		if( count( $users ) < $count ) {
			$dbCount = $count - count( $users );
			$dbOffset = $offset - count( $users );
			if( $dbOffset < 0 ) $dbOffset = 0;
			$usersManager = new ArdeUsers();
			$dbUsers = $usersManager->getUsers( $dbOffset, $dbCount, $beginWith, true );
			foreach( $dbUsers as $user ) $users[] = $user;
		}
		
		if( count( $users ) >= $count ) {
			unset( $users[ count( $users ) -  1 ] );
			$more = true;
		} else {
			$more = false;
		}
		if( !$xhtml ) {
			$p->pl( '<result more="'.($more?'true':'false').'">', 1 );
			foreach( $users as $user ) {
				$user->printXml( $p, 'user' );
				$p->nl();
			}
			$p->rel();
			$p->pl( '</result>' );
		}
		
	} else {
		throw new TwatchException( 'unknown action '.$_POST['a'] );
	}
	
	$p->end();
?>