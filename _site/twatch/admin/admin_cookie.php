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

	$twatch->makeParentClass( 'AdminAdminCookiePage', 'TwatchAdminPage' );

	class AdminAdminCookiePage extends AdminAdminCookiePageParent {

		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Admin Cookie'; }
		
		protected function getSelectedLeftButton() { return 'admin_cookie'; }

		protected function init() {
			global $twatch, $ardeUser;
			
			parent::init();
			
			if( isset( $_GET[ 'action' ] ) ) {
				if( !$ardeUser->user->hasPermission( TwatchUserData::ADMINISTRATE ) ) throw new TwatchUserError( 'No permission' );
				if( $_GET[ 'action' ] == 'set' ) {
					setcookie( $twatch->settings[ 'cookie_prefix' ].'_admin', $twatch->config->get( TwatchConfig::ADMIN_COOKIE )->secret, time() + 86400 * 365 * 10, $twatch->settings[ 'cookie_folder' ], $twatch->settings[ 'cookie_domain' ] );
				} elseif( $_GET[ 'action' ] == 'remove' ) {
					setcookie( $twatch->settings[ 'cookie_prefix' ].'_admin', '', time() - 86400, $twatch->settings[ 'cookie_folder' ], $twatch->settings[ 'cookie_domain' ] );
				}
				if( isset( $_GET[ 'back' ] ) ) {
					ardeRedirect( $_GET[ 'back' ] );
				}
			}
		}

		protected function printBody( ArdePrinter $p ) {
			global $twatch, $ardeUser;

			$p->pl( '<h1>Admin Cookie</h1>' );

			if( isset( $_COOKIE[ $twatch->settings[ 'cookie_prefix' ].'_admin' ] ) &&
				$twatch->config->get( TwatchConfig::ADMIN_COOKIE )->isAdminCookie( $_COOKIE[ $twatch->settings[ 'cookie_prefix' ].'_admin' ] ) ) {
				$p->pn( '<p><span class="good">Admin Cookie is set in your browser</span></p>' );
			} else {
				$p->pn( '<p><span class="critical">Admin Cookie is not set in your browser</span></p>' );
			}

			$p->pl( '<p>if you set the admin cookie in your browser TraceWatch will trace you as Administrator, otherwise you will be counted as a normal visitor.</p>' );

			$url = ArdeUrlWriter::getCurrentRelative();
			$url->removeParam( 'action' );
			$url->setParam( 'back', $url->getUrl() );
			$url->setParam( 'action', 'set' );
			$p->pl( '<p><a href="'.$url->getUrl().'">set admin cookie in my browser</a></p>' );
			$url->setParam( 'action', 'remove' );
			$p->pl( '<p><a href="'.$url->getUrl().'">remove admin cookie from my browser</a></p>' );
			$p->pl( '<p>' );
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
			$this->initJsPage( $p );
			$p->relnl();
			$p->pl( "	upgradeSecretButton = new ArdeRequestButton( 'Upgrade Admin Cookie Secret', 'This will invalidate any admin cookie currently in browsers' ).setCritical( true );" );
			$p->pl( '	upgradeSecretButton.insert();' );
			$p->pl( '	upgradeSecretButton.onclick = function() {' );
			$p->pl( "		upgradeSecretButton.request( twatchFullUrl( 'rec/rec_general.php' ), 'a=upgrade_admin_cookie' );" );
			$p->pl( '	};' );
			$p->pl( '/*]]>*/</script>' );
			$p->pl( '</p>' );
		}



	}

	$twatch->applyOverrides( array( 'AdminAdminCookiePage' => true ) );

	$page = $twatch->makeObject( 'AdminAdminCookiePage' );
	$page->render( $p );

?>