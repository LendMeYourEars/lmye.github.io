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

	require_once $ardeBase->path( 'db/Db.php' );

	class ArdeDbUser {
		public $id;
		public $username;
		public $random;
		public $randomExpires;
		public $groupId;

		public function __construct( $id, $username, $random, $randomExpires, $groupId = null ) {
			$this->id = $id;
			$this->username = $username;
			$this->random = $random;
			$this->randomExpires = $randomExpires;
			$this->groupId = $groupId;
		}
	}

	
	class ArdeDbUsers {

		private $db;

		public function __construct( ArdeDb $db ) {
			$this->db = $db;
		}

		public function install( $firstRandom, $rootUserName, $overwrite = true ) {
			$this->db->createTable( '', 'u', '('.
						' id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
						' username VARCHAR(50) CHARACTER SET '.$this->db->getCharset().' COLLATE '.$this->db->getCollation().' UNIQUE,'.
						' pass CHAR(40) CHARACTER SET latin1 COLLATE latin1_bin,'.
						' rnd CHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,'.
						' exp INT UNSIGNED NOT NULL )', $overwrite );
			$this->db->query( 'INSERT INTO', 'u', 'VALUES ( '.ArdeUser::USER_ROOT.", 'root', '', '', 0 )" );
			$this->db->createTable( '', 'lr', '( id TINYINT UNSIGNED NOT NULL PRIMARY KEY, rnd CHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL )', $overwrite );
			$this->db->query( 'INSERT INTO', 'lr', "VALUES( 0, ".$this->db->string( $firstRandom, 'latin1' )." )" );
			$this->db->createTable( '', 'g', '( uid INT UNSIGNED NOT NULL, appid INT UNSIGNED NOT NULL, gid INT UNSIGNED NOT NULL, UNIQUE( uid, appid ) )', $overwrite );
		}

		public function uninstall() {
			$this->db->dropTableIfExists( '', 'g' );
			$this->db->dropTableIfExists( '', 'u' );
			$this->db->dropTableIfExists( '', 'lr' );
			$this->uninstallRootSession();
		}


		public function getUsernameId( $username ) {
			$res = $this->db->query( 'SELECT id FROM', 'u', 'WHERE username = '.$this->db->string( $username ) );
			if( $r = $this->db->fetchRow( $res ) ) {
				return (int)$r[0];
			}
			return null;
		}
		
		public function addUser( $username, $passwordHash ) {
			$this->db->query( 'INSERT INTO', 'u', 'VALUES ( NULL, '.$this->db->string( $username ).", '".$this->db->escape( $passwordHash )."', '', 0 )" );
			return new ArdeDbUser( $this->db->lastInsertId(), $username, '', 0 );
		}
		
		public function updateUser( $id, $username, $passwordHash = null ) {
			$q = 'SET username = '.$this->db->string( $username );
			if( $passwordHash !== null ) {
				$q .= ", pass = '".$this->db->escape( $passwordHash )."'";
			}
			$q .= ' WHERE id = '.$id;
			$this->db->query( 'UPDATE', 'u', $q );
			return $this->db->affectedRows() != 0;
		}
		
		public function deleteUser( $id ) {
			$this->db->query( 'DELETE FROM', 'u', 'WHERE id = '.$id );
			return $this->db->affectedRows() != 0;
		}
		
		public function getUserById( $id, $appId = 0 ) {
			return $this->getUser( $id, null, null, null, $appId );
		}
		
		public function getUserByRandom( $random, $appId = 0 ) {
			return $this->getUser( null, $random, null, null, $appId );
		}
		
		public function getUserByUsernamePass( $username, $passwordHash, $appId = 0 ) {
			return $this->getUser( null, null, $username, $passwordHash, $appId );
		}
		
		protected function getUser( $id, $random = null, $username = null, $passwordHash = null, $appId = 0 ) {
			
			$q = "SELECT u.id, u.username, if( UNIX_TIMESTAMP() < exp, rnd, '' ), CAST( u.exp AS SIGNED ) - UNIX_TIMESTAMP(), g.gid FROM ".$this->db->table( 'u' ).' AS u';
			$q .= ' LEFT JOIN '.$this->db->table( 'g' ).' AS g ON( u.id = g.uid AND g.appid = '.$appId.' )';
			$q .= ' WHERE ';
			if( $id !== null ) {
				$q .= 'u.id = '.$id;
			} elseif( $random !== null ) {
				$q .= "u.rnd = ".$this->db->string( $random, 'latin1' )." AND UNIX_TIMESTAMP() < u.exp";
			} else {
				$q .= "u.username = ".$this->db->string( $username )." AND u.pass = '".$this->db->escape( $passwordHash )."'";
			}
			$res = $this->db->query( $q );
			$r = $this->db->fetchRow( $res );
			if( !$r ) return null;
			
			if( $r[2] == '' ) {
				$r[2] = null;
				$r[3] = 0;
			}
			return new ArdeDbUser( (int)$r[0], $r[1], $r[2], (int)$r[3], $r[4] === null ? $r[4] : (int)$r[4] );
		}

		public function getUsers( $offset, $count, $beginWith = null, $alphaOrder = false ) {
			$q = '';
			if( $beginWith !== null ) {
				$q .= 'WHERE username LIKE '.$this->db->string( $beginWith.'%' ).' ';
			}
			if( $alphaOrder ) {
				$q .= 'ORDER BY username ASC';
			} else {
				$q .= 'ORDER BY id DESC';
			}
			$q .= ' LIMIT '.$offset.', '.$count;
			$res = $this->db->query( 'SELECT id, username FROM', 'u', $q );
			$o = array();
			while( $r = $this->db->fetchRow( $res ) ) {
				$o[] = new ArdeDbUser( (int)$r[0], $r[1], null, 0 ); 
			}
			return $o;
		}
		
		public function getUsersCount( $beginWith = null ) {
			$res = $this->db->query( 'SELECT COUNT(*) FROM', 'u', $beginWith === null ? '' : 'WHERE username LIKE '.$this->db->string( $beginWith.'%' ) );
			return $this->db->fetchInt( $res );
		}
		
		public function setUserGroup( $userId, $groupId, $appId ) {
			
			$this->db->query( 'REPLACE INTO', 'g', 'VALUES ( '.$userId.", ".$appId.", ".$groupId.' )' );
		}
		
		public function reassignUsers( $fromGroupId, $toGroupId, $appId ) {
			$this->db->query( 'UPDATE', 'g', 'SET gid = '.$toGroupId.' WHERE appid = '.$appId.' AND gid = '.$fromGroupId );
		}
		
		public function removeApp( $appId ) {
			$this->db->query( 'DELETE FROM', 'g', 'WHERE appid = '.$appId );
		}
		
		public function setRandom( $userId, $random, $expires ) {
			if( $expires ) {
				$expires = 'UNIX_TIMESTAMP()+'.$expires;
			} else {
				$expires = '0';
			}
			$this->db->query( 'UPDATE', 'u', "SET rnd = ".$this->db->string( $random, 'latin1' ).", exp = ".$expires." WHERE id = ".$userId );
		}

		public function setLastRandom( $rnd ) {
			$this->db->query( 'UPDATE', 'lr', "SET rnd = ".$this->db->string( $rnd, 'latin1' ) );
		}

		public function getLastRandom( $soft = false ) {
			try {
				$res = $this->db->query( 'SELECT rnd FROM', 'lr' );
				$r = $this->db->fetchRow( $res );
				return $r[0];
			} catch( ArdeDbQueryError $e ) {
				if( $soft && $e->getCode() == $this->db->getErrorCode( ArdeDb::ERROR_TABLE_NOT_EXIST ) ) {
					return '';
				} else {
					throw $e;
				}
			}
		}

		public function getRootSessionRandom() {
			try {
				$res = $this->db->query( 'SELECT rnd FROM', 'rs', "WHERE id = 0 AND UNIX_TIMESTAMP() < exp" );
			} catch( ArdeDbQueryError $e ) {
				if( $e->getCode() == $this->db->getErrorCode( ArdeDb::ERROR_TABLE_NOT_EXIST ) ) return null;
				throw $e;
			}
			if( !( $r = $this->db->fetchRow( $res ) ) ) return null;
			return $r[0];
		}

		public function setRootSessionRandom( $random, $expires ) {
			try {
				$this->_setRootSessionRandom( $random, $expires );
			} catch( ArdeDbQueryError $e ) {
				if( $e->getCode() == $this->db->getErrorCode( ArdeDb::ERROR_TABLE_NOT_EXIST ) ) {
					$this->installRootSession();
					return $this->_setRootSessionRandom( $random, $expires );
				}
				throw $e;
			}
		}
		private function _setRootSessionRandom( $random, $expires ) {
			$this->db->query( 'INSERT INTO', 'rs', "VALUES( 0, ".$this->db->string( $random, 'latin1' ).", UNIX_TIMESTAMP() + ".$expires." ) ON DUPLICATE KEY UPDATE rnd = VALUES( rnd ), exp = VALUES( exp )" );
		}

		private function installRootSession() {
			$this->db->createTable( '', 'rs', '( id TINYINT UNSIGNED NOT NULL PRIMARY KEY, rnd CHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL, exp INT UNSIGNED NOT NULL )' );
		}

		public function uninstallRootSession() {
			$this->db->dropTableIfExists( '', 'rs' );
		}

	}
?>