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
    
	$admin = true;
	require_once dirname(__FILE__).'/../../lib/RecGlobalHead.php';
	loadConfig();
	
	
	
	class UserRec {
		protected $usersAdmin;
		
		public function init() {
			$this->usersAdmin = new ArdeUsersAdmin();
		}
		
		public function handleAction( ArdePrinter $p, $action ) {
			global $ardeUser, $xhtml;
			
			if( $action == 'add' ) {
				
				$username = ArdeParam::str( $_POST, 'un' );
				if( ctype_space( $username ) ) throw new ArdeUserError( 'username cannot be empty' );
				
				$pass = ArdeParam::str( $_POST, 'p' );
				if( ctype_space( $pass ) ) throw new ArdeUserError( 'password cannot be empty' );
				
				$passRetype = ArdeParam::str( $_POST, 'pr' );
				if( $pass != $passRetype ) throw new ArdeUserError( 'password and password retype do not match' );
				
				if( $this->usersAdmin->getUsernameId( $username ) ) throw new ArdeUserError( 'username '.$username.' already exists' );
				
				$this->usersAdmin->add( $username, $pass );
				
				$this->printUsers( $p, 0, ArdeUser::$perPage );
				
			} elseif( $action == 'update' ) {
				
				$id = ArdeParam::int( $_POST, 'i', 0 );
				if( $id == ArdeUser::USER_ROOT ) {
					throw new ArdeUserError( 'You can\'t change root user from here.' );
				}
				
				$username = ArdeParam::str( $_POST, 'un' );
				if( ctype_space( $username ) ) throw new ArdeUserError( 'username cannot be empty' );
				
				$usernameId = $this->usersAdmin->getUsernameId( $username );
				if( $usernameId && $usernameId != $id ) throw new ArdeUserError( 'username '.$username.' already exists' );
				
				if( !empty( $_POST[ 'p' ] ) ) {
					$pass = $_POST[ 'p' ];
					if( ctype_space( $pass ) ) throw new ArdeUserError( 'password cannot be empty' );
					
					$passRetype = ArdeParam::str( $_POST, 'pr' );
					if( $pass != $passRetype ) throw new ArdeUserError( 'password and password retype do not match' );
				} else {
					$pass = null;
				}
				
				$user = $this->usersAdmin->getUserById( $id );
				if( $user->name == $username && $pass === null ) {
					throw new ArdeUserError( 'nothing to change' );
				} 
				
				$this->usersAdmin->update( $id, $username, $pass );
		
				
				if( !$xhtml ) {
					$p->pl( '<successful />' );
				}
				
			} elseif( $action == 'delete' ) {
				$id = ArdeParam::int( $_POST, 'i', 0 );
				if( $id == ArdeUser::USER_ROOT ) {
					throw new ArdeUserError( 'You can\'t delete the root user.' );
				}
				if( !$this->usersAdmin->delete( $id ) ) {
					throw new ArdeUserError( 'User doesn\'t exist.' );
				}
				if( isset( $_POST[ 'o' ] ) ) {
					$offset = ArdeParam::int( $_POST, 'o', 0 );
					if( isset( $_POST[ 'bw' ] ) ) {
						$beginWith = $_POST[ 'bw' ];
					} else {
						$beginWith = null;
					}
					$this->printUsers( $p, $offset, ArdeUser::$perPage, $beginWith );
				} else {
					$p->pl( '<successful />' );
				}
				
			} elseif( $action == 'get_users' ) {
				$offset = ArdeParam::int( $_POST, 'o', 0 );
				$count = ArdeParam::int( $_POST, 'c', 0 );
				if( isset( $_POST [ 'wm' ] ) ) {
					$withMore = ArdeParam::bool( $_POST, 'wm' );
				} else {
					$withMore = false;
				}
				if( isset( $_POST[ 'bw' ] ) ) {
					$beginWith = $_POST[ 'bw' ];
				} else {
					$beginWith = null;
				}
				$this->printUsers( $p, $offset, $count, $beginWith, $withMore );
			} else {
				throw new ArdeUserError( 'unknown action '.$action );
			}
		}
		
		protected function printUsers( ArdePrinter $p, $offset, $count, $beginWith = null, $withMore = false ) {
			$users = $this->usersAdmin->getUsers( $offset, $count, $beginWith );
			$totalUsers = $this->usersAdmin->getUsersCount( $beginWith );
			if( $withMore ) {
				$more = $totalUsers > $offset + count( $users );
				$p->pl( '<result more="'.($more?'true':'false').'">', 1 );
			} else {
				$p->pl( '<result total="'.$totalUsers.'">', 1 );
			}
			foreach( $users as $user ) {
				$user->printXml( $p, 'user' );
				$p->nl();
			}
			$p->rel();
			$p->pl( '</result>' );
		}
	}
	
	$action = ArdeParam::str( $_POST, 'a' );
	
	$userRec = new UserRec();
	$userRec->init();
	$userRec->handleAction( $p, $action );
	
	$p->end();
?>