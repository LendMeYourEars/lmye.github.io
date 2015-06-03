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
	require_once $twatch->path( 'lib/TimeZone.php' );
	
	loadConfig();
	
	
	
	class AdminGeneralRec {
		
		public $xhtml;
		
		public function __construct( $xhtml ) { $this->xhtml = $xhtml; }
		
		public function performAction( ArdePrinter $p, $action ) {
			global $twatch, $ardeUser, $xhtml;
			if( $action == 'set_time_zone' ) {
				requirePermission( TwatchUserData::ADMINISTRATE );
				$tz = TwatchTimeZone::fromParams( $_POST );
				
				$origTz = TwatchTimeZone::fromConfig();
				
				if( $tz->isEquivalent( $origTz ) ) throw new TwatchUserError( 'nothing to change' );
				
				$tz->applyToConfig();
				
				if( !$this->xhtml ) {
					$p->pl( '<successful />' );
				}
			
			} elseif( $action == 'restore_time_zone' ) {
				requirePermission( TwatchUserData::ADMINISTRATE );
				$twatch->config->restoreDefault( TwatchConfig::TIME_DIFFERENCE );
				$twatch->config->restoreDefault( TwatchConfig::TIME_ZONE_NAME );
				
				$tz = TwatchTimeZone::fromConfig();
				
				if( !$this->xhtml ) {
					$tz->printXml( $p, 'result' );
					$p->nl();
				}
			
			} elseif( $action == 'upgrade_cookie_keys' ) {
				requirePermission( TwatchUserData::ADMINISTRATE );
				TwatchCookieKeys::upgradeKeys();
				if( !$this->xhtml ) {
					$p->pl( '<successful />' );
				}
			} elseif( $action == 'upgrade_admin_cookie' ) {
				requirePermission( TwatchUserData::ADMINISTRATE );
				TwatchAdminCookie::upgradeSecret();
				if( !$this->xhtml ) {
					$p->pl( '<successful />' );
				}
			} elseif( $action == 'set_perms' ) {
				requirePermission( TwatchUserData::ADMINISTRATE );

				require_once $twatch->path( 'lib/AdminGeneral.php' );
				$selectedUser = getSelectedUser( false );
		
				$selectedUser->data->hold();
				
				$permIds = array(
					 TwatchUserData::VIEW_REPORTS
					,TwatchUserData::VIEW_ADMIN
					,TwatchUserData::CONFIG
				);
				
				$rootPermIds = array(
					 TwatchUserData::ADMINISTRATE
					,TwatchUserData::VIEW_ERRORS 
				);
				
				foreach( $permIds as $permId ) {
					BoolWithDefAction::fromParams( $permId, 0, $_POST, 'p'.$permId.'_' )->run( $selectedUser->data );
				}
				
				if( $ardeUser->user->isRoot() ) {
					foreach( $rootPermIds as $permId ) {
						BoolWithDefAction::fromParams( $permId, 0, $_POST, 'p'.$permId.'_' )->run( $selectedUser->data );
					}
					if( isset( $_POST[ 'hpfc' ] ) ) {
						requireRootUser();
						$hiddenProfilesCount = ArdeParam::int( $_POST, 'hpfc', 0 );
						$hiddenProfiles = array();
						for( $i = 0; $i < $hiddenProfilesCount; ++$i ) {
							$name = ArdeParam::str( $_POST, 'hpf_'.$i );
							$hiddenProfiles[ $name ] = true;
						}
						$selectedUser->data->set( $hiddenProfiles, TwatchUserData::HIDDEN_PROFILES );
					}
				}
				
				
				
				$selectedUser->data->flush();
				
				if( !$xhtml ) {
					$p->pl( '<successful />' );
				}
			} elseif( $_POST[ 'a' ] == 'restore_profiles' ) {
				requireRootUser();
				require_once $twatch->path( 'lib/AdminGeneral.php' );
				$selectedUser = getSelectedUser();
				$selectedUser->data->restoreDefault( TwatchUserData::HIDDEN_PROFILES );
				if( !$xhtml ) {
					$p->pl( '<result>' );
					foreach( $selectedUser->data->get( TwatchUserData::HIDDEN_PROFILES ) as $profileId => $notImportant ) {
						$p->pl( '	<profile>'.$profileId.'</profile>' );
					}
					$p->pl( '</result>' );
				}
			} elseif( $action == 'update_def_lang' ) {
				require_once $twatch->path( 'lib/AdminGeneral.php' );
				$selectedUser = getSelectedUser( true );
				requireConfigPermission( $selectedUser );
				if( !$ardeUser->user->hasPermission( TwatchUserData::ADMINISTRATE ) && !$selectedUser->is( $ardeUser->user ) ) {
					throw new ArdeUserError( 'No Permission' );
				}
				$i = ArdeParam::str( $_POST, 'i' );
				if( !$twatch->localeExists( $i ) ) throw new ArdeException( "Locale '".$i."' doesn't exist." );
				$selectedUser->data->set( $i, TwatchUserData::DEFAULT_LANG );
				if( !$this->xhtml ) {
					$p->pl( '<successful />' );
				}
			} elseif( $action == 'restore_def_lang' ) {
				require_once $twatch->path( 'lib/AdminGeneral.php' );
				$selectedUser = getSelectedUser( true );
				requireConfigPermission( $selectedUser );
				$selectedUser->data->restoreDefault( TwatchUserData::DEFAULT_LANG );
				if( !$this->xhtml ) {
					$p->pl( '<result>'.ardeXmlEntities( $selectedUser->data->get( TwatchUserData::DEFAULT_LANG ) ).'</result>' );
				}
			} elseif( $action == 'change_log_when' ) {
				requirePermission( TwatchUserData::ADMINISTRATE );
				$when = TwatchExpression::fromParam( ArdeParam::str( $_POST, 'w' ) );
				$res = $when->isValid();

				if( $res !== true ) {
					$e = new TwatchUserError( '"when" has Syntax Error' );
					$e->safeExtras[] = $res;
					throw $e;
				}
				if( ardeEquivOrderedArrays( $when->a, $twatch->config->get( TwatchConfig::LOGGER_WHEN ) ) ) {
					throw new TwatchUserError( 'Nothing to Change' );
				}
				$oldWhen = new TwatchExpression( $twatch->config->get( TwatchConfig::LOGGER_WHEN ), null );
				$oldWhen->uninstall();
				$when->install();
				$twatch->config->set( $when->a, TwatchConfig::LOGGER_WHEN );
				
				if( !$this->xhtml ) {
					$p->pl( '<successful />' );
				}
				
			} else {
				throw new TwatchException( 'unknown action '.$action );
			}
		}
	}
	
	$twatch->applyOverrides( array( 'AdminGeneralRec' => true ) );
	
	$rec = $twatch->makeObject( 'AdminGeneralRec', $xhtml );
	
	if( !isset( $_POST['a'] ) ) throw new TwatchException( 'Action was not sent' );
	
	$rec->performAction( $p, $_POST['a'] );
	
	$p->end();
?>