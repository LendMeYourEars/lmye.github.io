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
    
	
	require_once dirname(__FILE__).'/../lib/PageGlobal.php';
	require_once $twatch->path( 'lib/AdminPage.php' );
	
	$twatch->makeParentClass( 'AdminIndexPage', 'TwatchAdminPage' );
	
	class AdminIndexPage extends AdminIndexPageParent {
		
		protected function printBaseJsIncludes( ArdePrinter $p ) {
			global $twatch;
			parent::printBaseJsIncludes( $p );
			$p->pl( '<script type="text/javascript" src="'.$twatch->baseUrl( $this->getToRoot(), 'js/ArdeExpression.js' ).'"></script>' );
		}
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			global $twatch;
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="'.$twatch->url( $this->getToRoot(), 'js/TwatchDiag.js' ).'"></script>' );
			$p->pl( '<script type="text/javascript" src="'.$twatch->url( $this->getToRoot(), 'js/AdminIndex.js' ).'"></script>' );
			$p->pl( '<script type="text/javascript" src="'.$twatch->url( $this->getToRoot(), 'js/Logger.js' ).'"></script>' );
		}
		
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Administrate'; }
		
		protected function getSelectedLeftButton() { return 'main'; }
		

		protected function printBody( ArdePrinter $p ) {
			global $twatch, $ardeBase, $ardeUser;
			require_once $twatch->path( 'lib/TimeZone.php' );
			
			require $twatch->path( 'lib/TwatchDiag.php' );
			require_once $twatch->path( 'lib/EntityV.php' );
			
			require_once $twatch->extPath( 'user', 'lib/Global.php' );
			require_once $ardeUser->path( 'lib/User.php' );
			require_once $twatch->path( 'data/DataUsers.php' );

			$this->printContents( $p );
		}
		
		protected function printContents( ArdePrinter $p ) {
			global $twatch, $ardeBase, $ardeUser;

			if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_ADMIN ) ) {
				$p->pl ('<h2>Permissions</h2>' );
				$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
				
				if( $ardeUser->user->isRoot() ) {
					$profilesA = new ArdeAppender( ', ' );
					
					$hiddenProfiles = $this->selectedUser->data->get( TwatchUserData::HIDDEN_PROFILES );
					foreach( $twatch->getProfiles() as $id => $name ) {
						$profilesA->append( 'new ProfileVis( '.ArdeJs::string( $id ).', '.ArdeJs::string( $name ).', '.ArdeJs::bool( isset( $hiddenProfiles[ $id ] ) ).' )' );
					}
					$profilesS = '[ '.$profilesA->s.' ]';
				} else {
					$profilesS = 'null';
				}
				
				$p->pn( '	userManager = new UserManager( ' );
				$p->pn(      TwatchUserData::VIEW_REPORTS.', '.$this->selectedUser->getPermission( TwatchUserData::VIEW_REPORTS )->jsObject() );
				$p->pn( ', '.TwatchUserData::VIEW_ADMIN.', '.$this->selectedUser->getPermission( TwatchUserData::VIEW_ADMIN )->jsObject() );
				$p->pn( ', '.TwatchUserData::CONFIG.', '.$this->selectedUser->getPermission( TwatchUserData::CONFIG )->jsObject() );
				if( $ardeUser->user->isRoot() ) {
					$p->pn( ', '.TwatchUserData::ADMINISTRATE.', '.$this->selectedUser->getPermission( TwatchUserData::ADMINISTRATE )->jsObject() );
					$p->pn( ', '.TwatchUserData::VIEW_ERRORS.', '.$this->selectedUser->getPermission( TwatchUserData::VIEW_ERRORS )->jsObject() );
				} else {
					$p->pn( ', null, null, null, null' );
				}
				$p->pl( ', '.$profilesS.' );' );
				$p->pl( '	userManager.insert();' );
				$p->pl( '/*]]>*/</script>' );
			}
			
			$defaultId = $this->selectedUser->data->get( TwatchUserData::DEFAULT_LANG );
			$langIds = new ArdeAppender( ', ' );
			foreach( $twatch->getLocaleIds() as $id ) $langIds->append( ArdeJs::string( $id ) );
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
			$p->pl( '	new LangSelector( '.ArdeJs::string( $defaultId ).', [ '.$langIds->s.' ] ).insert();' );
			$p->pl( '/*]]>*/</script>' );
			
			
			if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_ADMIN ) ) {
			
				$p->pl( '<h2>Time Zone</h2>' );
				$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
	
				$tz =  TwatchTimeZone::fromConfig();
				
				$p->pl( '	timeZone = '.$tz->jsObject().';' );
				$p->pl( '	timeZone.insert();' );
				$p->pl( '/*]]>*/</script>' );
				
				
				
				$diag = new TwatchDiag();
				$diag->load(); 
				
				$p->pl( '<h2>Logger</h2>' );
				$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
				$p->hold(1);
				TwatchExpression::printJsInputElements( $p );
				$p->rel();
				$expression = new TwatchExpression( $twatch->config->get( TwatchConfig::LOGGER_WHEN ), null );
				$p->pl( '	logger = new Logger( '.$expression->jsObject().' );' );
				$p->pl( '	logger.insert();' );
				$p->pl( '/*]]>*/</script>' );
	
				$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
				$p->pl( '	diag = '.$diag->jsObject().';' );
				$p->pl( '	diag.insert();' );
				$p->pl( '/*]]>*/</script>' );
				$p->pl( '<hr />' );
				$p->pl( '<p>' );
				$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
				$p->pl( "	upgradeKeysButton = new ArdeRequestButton( 'Upgrade Tracking Cookie Keys', 'This will invalidate any tracking cookie you already have in visitors\' browser' ).setCritical( true );" );
				$p->pl( '	upgradeKeysButton.insert();' );
				$p->pl( '	upgradeKeysButton.onclick = function() {' );
				$p->pl( "		upgradeKeysButton.request( twatchFullUrl( 'rec/rec_general.php' ), 'a=upgrade_cookie_keys' );" );
				$p->pl( '	};' );
				$p->pl( '/*]]>*/</script>' );
				$p->pl( '</p>' );
			}
			
			if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_ERRORS ) ) {
			
				$p->pl( '<hr />' );
				if( $ardeUser->user->hasPermission( TwatchUserData::ADMINISTRATE ) ) {
					if( isset( $_SERVER[ 'SERVER_SOFTWARE' ] ) ) {
						$serverName = $_SERVER[ 'SERVER_SOFTWARE' ].' - '.php_sapi_name();
					} else {
						$serverName = "UNKNOWN";
					}
					try {
						$version = $twatch->config->get( TwatchConfig::VERSION );
					} catch( Exception $e ) {
						$version = '%ERROR%';
					}
					try {
						$instanceId = $twatch->config->get( TwatchConfig::INSTANCE_ID );
					} catch( Exception $e ) {
						$instanceId = '%ERROR%';
					}
					
					$p->pl( '<p><b>PHP</b> version: <span class="fixed">'.phpversion().'</span></p>' );
					$p->pl( '<p><b>MySql</b> server version: <span class="fixed">'.mysql_get_server_info().'</span></p>' );
					$p->pl( '<p><b>HTTP</b> server: <span class="fixed">'.$serverName.'</span></p>' );
					$p->pl( '<p><b>Database data</b> version: <span class="fixed">'.$version.'</span>' );
					$p->pl( '<p><b>Instance ID</b>: <span class="fixed">'.$instanceId.'</span>' );
				}
			}
		}
	} 
	
	$twatch->applyOverrides( array( 'AdminIndexPage' => true ) );
	
	$page = $twatch->makeObject( 'AdminIndexPage' );
	
	$page->render( $p );

	
?>
