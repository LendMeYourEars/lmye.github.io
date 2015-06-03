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

	$updatePage = true;

	require_once dirname(__FILE__).'/../lib/PassivePageHead.php';

	class TwatchInstallPluginPage extends TwatchPassivePage {

		public $rootUser;

		protected function getTitle() { return 'Uninstall TraceWatch'; }
		
		protected function getToRoot() { return '..'; }
		
		protected function init() {
			global $ardeBase, $twatch, $ardeUser, $ardeUserProfile;

			$ardeUserProfile = $twatch->settings[ 'user_profile' ];
			require_once $twatch->extPath( 'user', 'lib/Global.php' );
			require_once $ardeUser->path( 'lib/User.php' );
			require_once $ardeBase->path( 'lib/ArdeJs.php' );

			$ardeUser->db = new ArdeDb( $ardeUser->settings );
			$ardeUser->db->connect();

			$this->rootUser = ArdeUser::getRootSessionUser();
			$url = new ArdeUrlWriter( 'root_session_login.php' );
			$url->setParam( 'back', ardeRequestUri() )->setParam( 'profile', $ardeUserProfile, 'default' );
			if( $this->rootUser === null ) {
				ardeRedirect( $twatch->extUrl( $this->getToRoot(), 'user', $url->getUrl() ) );
				return false;
			}
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch;
			
			$p->setMutedErrors( false );

			$profiles = $twatch->getProfiles();

			if( isset( $_GET['action'] ) ) {
				require_once $twatch->path( 'lib/General.php' );
				require_once $twatch->path( 'data/DataGlobal.php' );

				try { set_time_limit( 600 ); } catch( Exception $e ) {}

				if( $twatch->settings[ 'disable_output_buffering' ] ) {
					while ( ob_get_level() > 0 ) {
						try { ob_end_flush(); } catch( Exception $e ) {}
					}
					try{ ob_implicit_flush(); } catch( Exception $e ) {}
				}


				$twatch->db = new ArdeDb( $twatch->settings );
				$twatch->db->connect();

				$twatch->config = new TwatchConfig( $twatch->db );
				$twatch->state = new TwatchState( $twatch->db );

				$twatch->config->addDefaults( TwatchConfig::$defaultProperties );
				$defs = array( TwatchConfig::PLUGIN_VERSIONS => array() );
				$twatch->config->addDefaults( $defs );
				$twatch->config->applyAllChanges();


				$twatch->state->addDefaults( TwatchState::$defaultProperties );
				$twatch->state->addDefaults( TwatchState::$extraDefaults );
				$twatch->state->applyAllChanges();

				twatchSetAppTime();

				$pluginId = ArdeParam::int( $_GET, 'plugin' );
				if( !isset( $twatch->plugins[ $pluginId ] ) ) throw new TwatchException( 'plugin doesn\'t exist' );
				$plugin = $twatch->plugins[ $pluginId ];
				$plugin->startId = $twatch->config->getLayerStartId( $plugin->getLayer() );
				$action = ArdeParam::str( $_GET, 'action' );

				if( $action == 'Install' ) {
					if( !$plugin->needsInstall() ) throw new TwatchException( 'plugin doesn\'t need installation' );
					$p->pl( '<h3>Installing plugin "'.$plugin->getName().'" of profile "'.$twatch->profileName().'"</h3>' );
					if( $plugin->hasInstallForm() && !isset( $_GET['run'] ) ) {
						$p->pl( '<form method="GET">', 1 );
						$plugin->printInInstallForm( $p );
						$p->rel();
						$p->pl( '	<input name="profile" value="'.$twatch->profile.'" type="hidden" />' );
						$p->pl( '	<input name="plugin" value="'.$plugin->id.'" type="hidden" />' );
						$p->pl( '	<input name="action" value="'.$action.'" type="hidden" />' );
						if( isset( $_GET[ 'verbose' ] ) ) {
							$p->pl( '	<input name="verbose" value="'.$_GET[ 'verbose' ].'" type="hidden" />' );
						}
						$p->pl( '	<input name="run" value="true" type="hidden" />' );
						$p->pl( '	<p><input type="submit" value="Install" /></p>' );
						$p->pl( '</form>' );
					} else {
						$this->install( $p, $plugin );
						$this->rootUser->terminateSession();
					}
				} elseif( $action == 'Uninstall' ) {
					if( !$plugin->needsUninstall() ) throw new TwatchException( 'plugin doesn\'t need uninstallation' );
					$p->pl( '<h3>Uninstalling plugin "'.$plugin->getName().'" of profile "'.$twatch->profileName().'"</h3>' );
					if( !isset( $_GET['sure'] ) ) {
						$p->pl( '<form method="GET">', 1 );
						$p->pl( '	<input name="profile" value="'.$twatch->profile.'" type="hidden" />' );
						$p->pl( '	<input name="plugin" value="'.$plugin->id.'" type="hidden" />' );
						$p->pl( '	<input name="action" value="'.$action.'" type="hidden" />' );
						if( isset( $_GET[ 'verbose' ] ) ) {
							$p->pl( '	<input name="verbose" value="'.$_GET[ 'verbose' ].'" type="hidden" />' );
						}
						$p->pl( '	<input name="sure" value="true" type="hidden" />' );
						$p->pl( '	<p>Are you sure you want to completely remove this plugin and all data associated with it?</p>' );
						$p->pl( '	<p><input type="submit" value="I\'m Sure" /></p>' );
						$p->pl( '</form>' );
					} else {
						$this->uninstall( $p, $plugin );
						$this->rootUser->terminateSession();
					}
				} elseif( $action == 'Update' ) {
					if( !$plugin->needsInstall() ) throw new TwatchException( 'plugin doesn\'t need installation' );
					$p->pl( '<h3>Updating plugin "'.$plugin->getName().'" of profile "'.$twatch->profileName().'"</h3>' );
					$this->update( $p, $plugin );
					$this->rootUser->terminateSession();
				} else {
					throw new TwatchException( 'unknown action' );
				}

			} else if( count( $profiles ) > 1 && !isset( $_GET[ 'profile' ] ) ) {
				$p->pl( '<form method="GET"><p>', 1 );
				if( count( $profiles ) > 1 ) {
					$p->pl( 'Profile: <select name="profile">' );
					foreach( $profiles as $id => $name ) {
						$p->pl( '<option value="'.$id.'"'.( $id == $twatch->profile ? 'selected="selected"' : '' ).'>'.$name.'</option>' );
					}
					$p->pl( '</select>' );
				}
				$p->rel();
				if( isset( $_GET[ 'verbose' ] ) ) {
					$p->pl( '	<input name="verbose" value="'.$_GET[ 'verbose' ].'" type="hidden" />' );
				}
				$p->pl( '	<input type="submit" value="Select" />' );
				$p->pl( '</p></form>' );
			} else {
				$plugins = array();

				foreach( $twatch->plugins as $plugin ) {
					if( $plugin->needsInstall() || $plugin->needsUninstall() ) {
						$plugins[ $plugin->id ] = $plugin->getName();
					}
				}
				if( !count( $plugins ) ) {
					$p->pl( '<div class="block" style="text-align:center;font-weight:bold"><p><span class="fixed">There are no plugins that need installation.</span></p></div>' );
					return;
				}
				$p->pl( '<form method="GET"><p>', 1 );
				$p->pl( 'Plugin: <select name="plugin">' );
				foreach( $plugins as $id => $name ) {
					$p->pl( '<option value="'.$id.'">'.$name.'</option>' );
				}
				$p->rel();
				$p->pl( '	</select>' );

				$p->pl( '	<input name="profile" value="'.$twatch->profile.'" type="hidden" />' );
				if( isset( $_GET[ 'verbose' ] ) ) {
					$p->pl( '	<input name="verbose" value="'.$_GET[ 'verbose' ].'" type="hidden" />' );
				}
				$p->pl( '	<input name="action" type="submit" value="Install" />' );
				$p->pl( '	<input name="action" type="submit" value="Update" />' );
				$p->pl( '	<input name="action" type="submit" value="Uninstall" />' );
				$p->pl( '</p></form>' );
			}

		}

		

		public function install( ArdePrinter $p, TwatchPlugin $plugin ) {
			global $twatch;
			$plugin->install( $p );
			$twatch->config->set( $plugin->getVersion(), TwatchConfig::PLUGIN_VERSIONS, $twatch->config->getStartId() + $plugin->id );
			$p->pl( '<div class="block" style="text-align:center;font-weight:bold"><p><span class="fixed">Successfully Installed</span></p></div>' );
		}


		public function uninstall( ArdePrinter $p, TwatchPlugin $plugin ) {
			global $twatch;
			$plugin->uninstall( $p );
			$p->pn( '<p>Deleting plugin\'s property changes... ' );
			$twatch->config->removeLayerChanges( $plugin->getLayer() );
			$twatch->state->removeLayerChanges( $plugin->getLayer() );
			$twatch->config->remove( TwatchConfig::PLUGIN_VERSIONS, $twatch->config->getStartId() + $plugin->id );
			$p->pl( '<span class="good">successful</span></p>' );
			$p->pl( '<div class="block" style="text-align:center;font-weight:bold"><p><span class="fixed">Successfully Uninstalled</span></p></div>' );
		}

		public function update( ArdePrinter $p, TwatchPlugin $plugin ) {
			$plugin->update( $p );
			$p->pl( '<div class="block" style="text-align:center;font-weight:bold"><p><span class="fixed">Successfully Updated</span></p></div>' );
		}

		public function run( ArdePrinter $p ) {
			global $twatch, $ardeUser, $ardeBase;


		}

	}

	$twatch->applyOverrides( array( 'TwatchInstallPluginPage' => true ) );

	$page = $twatch->makeObject( 'TwatchInstallPluginPage' );

	$page->render( $p );

?>