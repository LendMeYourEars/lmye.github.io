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
    
	require_once $ardeUser->path( 'db/ArdeDbUser.php' );
	require_once $ardeUser->path( 'lib/General.php' );
	require_once $ardeBase->path( 'lib/ArdeSerializer.php' );
	
	class ArdeUserOrGroup {
		const USER = 1;
		const GROUP = 2;
		
		public $id;
		public $name;
		
		public $data;
		
		public function __construct( $id = 0, $name = '' ) {
			$this->id = $id;
			$this->name = $name;
		}
		
		
		public function hasPermission( $id, $subId = 0 ) {
			return $this->data->get( $id, $subId );
		}
		
		public function getPermission( $id, $subId = 0 ) {
			return new ArdeUserPermission( $this->hasPermission( $id, $subId ), $this->data->isDefault( $id, $subId ), $this->data->getDefault( $id, $subId ) );	
		}
		
		public function jsParams() {
			return $this->id.', '.ArdeJS::string( $this->name );
		}
		
		public function jsObject() {
			return 'new User( '.$this->jsParams().' )';
		}
		
		public function baseJsParams() {
			return ( $this instanceof ArdeUser ? self::USER : self::GROUP ).', '.$this->id.', '.ArdeJS::string( $this->name );
		}
		
		public function baseJsObject() {
			return 'new User( '.$this->baseJsParams().' )';
		}
		
		public function adminJsObject() {
			return $this->jsObject();
		}

		public function baseAdminJsObject() {
			return $this->baseJsObject();
		}
		
		public function is( self $other ) {
			if( get_class( $other ) != get_class( $this ) ) return false;
			if( $this->id != $other->id ) return false;
			return true;
		}
		
		 public function isRoot() {
		 	return ($this instanceof ArdeUser) && ($this->id == ArdeUser::USER_ROOT); 
		 }
		
		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' id="'.$this->id.'"'.$extraAttrib.' type="'.( $this instanceof ArdeUser ? 'user' : 'group' ).'" >' );
			$p->pl( '	<name>'.ardeXmlEntities( $this->name ).'</name>' );
			$p->pn( '</'.$tagName.'>' );
		}
		
	}
	
	class ArdeUser extends ArdeUserOrGroup {
		const USER_PUBLIC = 1;
		const USER_ROOT = 100;

		public static $perPage = 7;
		
		public $random;
		public $groupId;
		public $randomExpires;
		public $data;
		
		public static function fromDbUser( ArdeDbUser $dbUser = null ) {
			global $ardeUser;
			
			if( $dbUser === null ) return null;
			
			if( $dbUser->id == ArdeUser::USER_ROOT ) {
				$o = new ArdeUserRoot();	
				$o->name = $ardeUser->settings[ 'root_username' ];
				$o->groupId = ArdeUserGroup::GROUP_ADMIN;
			} else {
				$o = new ArdeUser();
				$o->name = $dbUser->username;
				if( $dbUser->groupId === null ) {
					$o->groupId = ArdeUserGroup::GROUP_PUBLIC;
				} else {
					$o->groupId = $dbUser->groupId;
				}
			}
			
			$o->id = $dbUser->id;
			$o->random = $dbUser->random;
			$o->randomExpires = $dbUser->randomExpires;

			
			return $o;
		}

		
		
		public static function GetUser( $appId = 0 ) {
			$ardeUsers = new ArdeUsers();
			return $ardeUsers->getUser( $appId );
		}
		
		public static function getRootSessionUser() {
			$ardeUsers = new ArdeUsers();
			return $ardeUsers->getRootSessionUser();
		}
		
		public function baseAdminJsObject() {
			return'new User( '.$this->baseJsParams().', '.$this->groupId.' )'; 
		}
	}
	
	class ArdeUserPublic extends ArdeUser {
		
		public function __construct() {
			global $ardeUser;
			$this->id = self::USER_PUBLIC;
			$this->name = 'public';
			$this->groupId = ArdeUserGroup::GROUP_PUBLIC;
		}

	}
	
	class ArdeUserRoot extends ArdeUser {
		
		const SESSION_LENGTH = 900;
		
		public function __construct() {
			global $ardeUser;
			$this->id = self::USER_ROOT;
			$this->name = $ardeUser->settings[ 'root_username' ];
			$this->groupId = ArdeUserGroup::GROUP_ADMIN;
		}
		
		public function initWithDbUser( ArdeDbUser $dbUser ) {
			$this->random = $dbUser->random;
			$this->randomExpires = $dbUser->randomExpires;
		}
		
		
		
		public function terminateSession() {
			global $ardeUser;
			$dbUsers = new ArdeDbUsers( $ardeUser->db );
			$dbUsers->uninstallRootSession();
			
		}
		
		public function hasPermission( $id, $subId = 0 ) {
			return true;
		}
		
	}

	class ArdeUsers {
		protected $dbUsers;
		
		public function __construct() {
			global $ardeUser;
			$this->dbUsers = new ArdeDbUsers( $ardeUser->db );
		}
		
		public function getUser( $appId = 0 ) {
			global $ardeUser;
			
			if( isset( $_COOKIE[ $ardeUser->settings[ 'cookie_prefix' ] ] ) ) {
				$user = $this->getUserByRandom( $_COOKIE[ $ardeUser->settings[ 'cookie_prefix' ] ], $appId );
				if( $user !== null ) return $user;
			}
			
			return new ArdeUserPublic();

		}
		
		public function getRootSessionUser() {
			global $ardeUser;
			
			if( isset( $_COOKIE[ $ardeUser->settings[ 'cookie_prefix' ].'_root_session' ] ) ) {

				$rnd = $this->dbUsers->getRootSessionRandom();
				if ( $rnd === $_COOKIE[ $ardeUser->settings[ 'cookie_prefix' ].'_root_session' ] ) {
					return new ArdeUserRoot();
				}
			}
			
			return null;
		}
		
		public function setUserGroup( $userId, $groupId, $appId ) {
			return $this->dbUsers->setUserGroup( $userId, $groupId, $appId );
		}
		
		public function reassignUsers( $fromGroupId, $toGroupId, $appId ) {
			return $this->dbUsers->reassignUsers( $fromGroupId, $toGroupId, $appId );
		}
		
		public function getUserByRandom( $random, $appId ) {
			if( empty( $random ) ) return null;
			
			return ArdeUser::fromDbUser( $this->dbUsers->getUserByRandom( $random, $appId ) );

		}
		
		public function getUserById( $id, $appId = 0 ) {
			return ArdeUser::fromDbUser( $this->dbUsers->getUserById( $id, $appId ) );
		}
		
		public function getUserByUnPass( $username, $password, $appId = 0 ) {
			global $ardeUser;
			if( $username == $ardeUser->settings[ 'root_username' ] ) {
				if( $password == $ardeUser->settings[ 'root_password' ] ) {
					$dbUser = $this->dbUsers->getUserById( ArdeUser::USER_ROOT, $appId );
				} else {
					$dbUser = null;
				}
			} else {
				$dbUser = $this->dbUsers->getUserByUsernamePass( $username, $this->getPasswordHash( $password ), $appId );
			}
			return ArdeUser::fromDbUser( $dbUser );
		}
		
		public function getRootByUnPass( $username, $password ) {
			global $ardeUser;
			if( $username == $ardeUser->settings[ 'root_username' ] && $password == $ardeUser->settings[ 'root_password' ] ) {
				return new ArdeUserRoot();
			}
			return null;
		}
		
		public function updateRootUsername() {
			global $ardeUser;
			if( !$ardeUser->rootUsernameUpdated ) {
				$this->dbUsers->updateUser( ArdeUser::USER_ROOT, $ardeUser->settings[ 'root_username' ] );
				$ardeUser->rootUsernameUpdated = true;
			}
		}
		
		public function getUsers( $offset, $count, $beginWith = null, $alphaOrder = false ) {
			if( $beginWith !== null ) $this->updateRootUsername();
			$dbUsers = $this->dbUsers->getUsers( $offset, $count, $beginWith, $alphaOrder );
			$o = array();
			foreach( $dbUsers as $dbUser ) {
				$o[] = ArdeUser::fromDbUser( $dbUser );
			}
			return $o;
		}
		
		public function getUsersCount( $beginWith = null ) {
			if( $beginWith !== null ) $this->updateRootUsername();
			return $this->dbUsers->getUsersCount( $beginWith );
		}

		public function getPasswordHash( $password ) {
			if( $password === null ) return null;
			return sha1( $password );
		}
		
		public function makeRootSessionRandom() {
			global $ardeUser;
			$newRandom = $this->makeActiveRandom( true );
			$this->dbUsers->setRootSessionRandom( $newRandom, ArdeUserRoot::SESSION_LENGTH );
			return $newRandom;
		}
		
		public static function makePassiveRandomString() {
			global $ardeUser;
			return microtime().mt_rand( 0, 0x7fffffff ).$ardeUser->settings[ 'salt' ];
		}
		
		public static function makePassiveRandom() {
			return md5( self::makePassiveRandomString() );
		}
		
		public function makeActiveRandom( $soft = false ) {

			$lastRandom = $this->dbUsers->getLastRandom( $soft );
			$randomStr = $lastRandom.self::makePassiveRandomString();
			$rnd = md5( $randomStr );
			if( $lastRandom != '' ) $this->dbUsers->setLastRandom( $rnd );
			return $rnd;
		}
		
		public function renewUserRandom( $user, $expires ) {
			
			$newRandom = $this->makeActiveRandom();

			$this->dbUsers->setRandom( $user->id, $newRandom, $expires );
			
			$user->random = $newRandom;
			$user->randomExpires = $expires;
			
		}
		
		public function removeUserRandom( $user ) {
			$this->dbUsers->setRandom( $user->id, '', 0 );
			
			$user->random = '';
			$user->randomExpires = 0;
		}
		
		public function removeApp( $appId ) {
			$this->dbUsers->removeApp( $appId );
		}
		
		public function install( $overwrite ) {

			$this->dbUsers->install( self::makePassiveRandom(), $overwrite );
		}
		
		public function uninstall() {
			
			$this->dbUsers->uninstall();
		}
		
	}
	
	class ArdeUserGroup extends ArdeUserOrGroup implements ArdeSerializable {
		const GROUP_PUBLIC = 1;
		const GROUP_ADMIN = 2;
		
		public $defaultPropertiesId = 0;
		
		public function __construct( $id, $name, $defaultPropertiesId = 0 ) {
			parent::__construct( $id, $name );
			$this->defaultPropertiesId = $defaultPropertiesId;
		}
		
		public function adminJsObject() {
			return'new UserGroup( '.$this->jsParams().' )'; 
		}
		
		public function printAdminXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$this->printXml( $p, $tagName, $extraAttrib );
		}
		
		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->id, $this->name, $this->defaultPropertiesId ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2] );
		}
	}
	
	class ArdePublicGroup extends ArdeUserGroup {
		public function __construct() {
			parent::__construct( self::GROUP_PUBLIC, 'public' );
		}
	}
	
	class ArdeAllUsers extends ArdeUserGroup {
		public function __construct() {
			parent::__construct( 0, 'All Users' );
			$this->defaultPropertiesId = 1;
		}
	}
	
?>