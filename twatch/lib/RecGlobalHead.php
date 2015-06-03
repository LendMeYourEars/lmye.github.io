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
    
	
	ini_set( 'display_errors', '1' );

	if( isset( $_GET[ 'profile' ] ) ) {
		$twatchProfile = $_GET[ 'profile' ];
	}

	
	require_once dirname(__FILE__).'/../lib/Global.php';
	
	if( $twatch->settings[ 'down' ] ) die( $twatch->settings[ 'down_message' ] );
	
	require_once $ardeBase->path( 'lib/ArdeXmlPrinter.php' );
	require_once $ardeBase->path( 'lib/ArdeJs.php' );
	require_once $ardeBase->path( 'lib/ArdeXml.php' );
	require_once $twatch->path( 'lib/ReportGeneral.php' );
	
	require_once $twatch->path( 'data/DataGlobal.php' );
	
	$xhtml = isset( $_GET['xhtml'] );
	
	$p = new ArdeXmlPrinter( $xhtml, !$xhtml, 'response' );
	ArdeException::startErrorSystem( $p, $p );
	
	$p->setHideErrors( !$twatch->settings[ 'unauthorized_show_errors' ] );
	$p->setMutedErrors( $twatch->settings[ 'unauthorized_muted_errors' ] );

	
	$p->start( 'application' );
	
	if( $xhtml ) {
		$p->pl( '<body>', 1 );
	}
	
	foreach( $_GET as $k => $v ) { $_POST[$k] = $v; }
	
	$twatch->db = new ArdeDb( $twatch->settings );
	$twatch->db->connect();
	
	require_once $twatch->extPath( 'user', 'lib/Global.php' );
	require_once $twatch->path( 'data/DataUsers.php' );
	
	$twatch->config = new TwatchConfig( $twatch->db );
	$twatch->state = new TwatchState( $twatch->db );
	
	$twatch->config->addDefaults( TwatchConfig::$defaultProperties );
	$twatch->config->addDefaults( TwatchConfig::$userRelatedProperties );

	$twatch->state->addDefaults( TwatchState::$defaultProperties );
	$twatch->state->addDefaults( TwatchState::$extraDefaults );
	
	function loadConfig( $extraDefConfig = array(), $extraUserDefConfig = array() ) {
		global $twatch, $ardeUser, $p, $adminConfig;
		foreach( $extraDefConfig as $extraDef ) {
			$twatch->config->addDefaults( $extraDef );
		}

		$twatch->config->applyAllChanges();
		
		$twatch->state->applyAllChanges();
		
		initUser( $extraUserDefConfig );
		
		if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_ERRORS ) ) {
			$p->setMutedErrors(  $twatch->settings[ 'authorized_muted_errors' ] );
			$p->setHideErrors( !$twatch->settings[ 'authorized_show_errors' ] );
		}
		
		if( ( $twatch->settings[ 'authorized_log_errors' ] && $ardeUser->user->hasPermission( TwatchUserData::VIEW_ERRORS ) ) ||
			( $twatch->settings[ 'unauthorized_log_errors' ] && !$ardeUser->user->hasPermission( TwatchUserData::VIEW_ERRORS ) ) ) {
			ArdeException::setGlobalReporter( new TwatchErrorLogger( ArdeException::getGlobalReporter() ) );
		}
		
		
		$twatch->callFunction( 'twatchSetAppTime' );
		
		if( isset( $_GET[ 'lang' ] ) && $twatch->localeExists( $_GET[ 'lang' ] ) ) {
			$twatch->loadLocale( $_GET[ 'lang' ] );
		} else {
			$twatch->loadLocale( $ardeUser->user->data->get( TwatchUserData::DEFAULT_LANG ) );
		}
		
		

	}

	function requirePermission( $id, $subId = 0 ) {
		global $ardeUser;
		if( !$ardeUser->user->hasPermission( $id, $subId ) ) throw new TwatchNoPermission();
	}
	
	function requireRootUser() {
		global $ardeUser;
		if( $ardeUser->user->id != ArdeUser::USER_ROOT ) throw new TwatchNoPermission();
	}
	
	function requireConfigPermission( ArdeUserOrGroup $selectedUser ) {
		global $ardeUser;
		if( $ardeUser->user->hasPermission( TwatchUserData::ADMINISTRATE ) ) return;
		if( $ardeUser->user->hasPermission( TwatchUserData::CONFIG ) && $selectedUser->is( $ardeUser->user ) ) return;
		throw new TwatchNoPermission();
	}
	
	class ValueWithDefAction {
		public $id;
		public $subId;
		public $value;
		public $restoreDef;
		
		public function run( ArdeAdminProperties $props ) {
			if( !$props->propertyExists( $this->id, $this->subId ) ) throw new TwatchUserError( 'no permission found with id '.$this->id.' and sub-id '.$this->subId );
			if( $this->restoreDef ) {
				if( !$props->isDefault( $this->id, $this->subId ) ) {
					$props->restoreDefault( $this->id, $this->subId );
				}
			} else {
				$props->set( $this->value, $this->id, $this->subId );
			}
		}

		
		protected static function _fromParams( $className, $id, $subId, $a, $prefix ) {
			$o = new $className();
			$o->id = $id;
			$o->subId = $subId;
			if( isset( $a[ $prefix.'d' ] ) ) {
				$o->restoreDef = true;
			} else {
				$o->value = $o->interpretValue( ArdeParam::str( $a, $prefix.'v' ) );
			}
			return $o;
		}
		
		public static function fromParams( $id, $subId, $a, $prefix = '' ) {
			return self::_fromParams( 'ValueWithDefAction', $id, $subId, $a, $prefix );
		}
		
		protected function interpretValue( $value ) {
			return $value;
		}
		
		
	}
	
	class IntWithDefAction extends ValueWithDefAction {
		protected function interpretValue( $value ) {
			return (int)$value;
		}
		
		public static function fromParams( $id, $subId, $a, $prefix = '' ) {
			return self::_fromParams( 'IntWithDefAction', $id, $subId, $a, $prefix );
		}
		
	}
	
	class BoolWithDefAction extends ValueWithDefAction {
		protected function interpretValue( $value ) {
			return $value=='t'?true:false;
		}
		
		public static function fromParams( $id, $subId, $a, $prefix = '' ) {
			return self::_fromParams( 'BoolWithDefAction', $id, $subId, $a, $prefix );
		}
	}
	
	function successful( ArdePrinter $p ) {
		global $xhtml;
		if( !$xhtml ) $p->pl( '<successful />' );
	}
	
	
?>