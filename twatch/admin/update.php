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

	class TwatchUpdatePage extends TwatchPassivePage {

		public $rootUser;

		protected function getTitle() { return 'Update TraceWatch'; }
		
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
			if( $this->rootUser === null ) ardeRedirect( $twatch->extUrl( $this->getToRoot(), 'user', $url->getUrl() ) );
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch, $ardeUser;

			$p->setMutedErrors( false );
			
			if( isset( $_GET['run'] ) ) {
				try { set_time_limit( 600 ); } catch( Exception $e ) {}
	
				if( $twatch->settings[ 'disable_output_buffering' ] ) {
					while ( ob_get_level() > 0 ) {
						try { ob_end_flush(); } catch( Exception $e ) {}
					}
					try{ ob_implicit_flush(); } catch( Exception $e ) {}
				}
				
				$this->run( $p );
				
				$p->pl( '<h3>Updating User Manager</h3>' );
				require_once $ardeUser->path( 'lib/Installer.php' );
				$userInstaller = new ArdeUserInstaller();
				$userInstaller->update( $p );
				
				$this->rootUser->terminateSession();
			} else {
				$p->pl( '<form method="GET">' );
				$profiles = $twatch->getProfiles();
				if( count( $profiles ) > 1 ) {
					$p->pl( '<p>Profile: <select name="profile">' );
					foreach( $profiles as $id => $name ) {
						$p->pl( '<option value="'.$id.'"'.( $id == $twatch->profile ? 'selected="selected"' : '' ).'>'.$name.'</option>' );
					}
					$p->pl( '</select></p>' );
				}
				$p->pl( '	<p><label><input type="checkbox" name="update_um" checked="checked" /> Update user manager too</label></p>' );
				$p->pl( '	<p><input type="submit" value="Update" /></p>' );
				$p->pl( '	<input type="hidden" name="run" value="true" /></p>' );
				$p->pl( '</form>' );
			}

		}

		

		public function run( ArdePrinter $p ) {
			global $twatch, $ardeUser, $ardeBase;

			

			require_once $twatch->path( 'lib/General.php' );
			require_once $twatch->path( 'data/DataGlobal.php' );
			
			$p->pl( '<h3>updating TraceWatch profile "'.$twatch->profileName().'"</h3>' );

			$twatch->db = new ArdeDb( $twatch->settings );
			$twatch->db->connect();

			if( !$twatch->db->columnExists( 'wid', 'c' ) ) {
				$p->pn( '<p>Updating config table\'s structure... ' );

				$twatch->db->query( 'ALTER TABLE', 'c', 'DROP INDEX id' );
				$twatch->db->query( 'ALTER TABLE', 'c', 'ADD COLUMN wid INT UNSIGNED NOT NULL FIRST' );
				$twatch->db->query( 'ALTER TABLE', 'c', 'ADD UNIQUE( wid, id, subid, type )' );
				$p->pl( '<span class="good">successful</span></p>' );
			}
			if( !$twatch->db->columnExists( 'wid', 's' ) ) {
				$p->pn( '<p>Updating state table\'s structure... ' );
				$twatch->db->query( 'ALTER TABLE', 's', 'DROP INDEX id' );
				$twatch->db->query( 'ALTER TABLE', 's', 'ADD COLUMN wid INT UNSIGNED NOT NULL FIRST' );
				$twatch->db->query( 'ALTER TABLE', 's', 'ADD UNIQUE( wid, id, subid, type )' );
				$p->pl( '<span class="good">successful</span></p>' );
			}
			if( !$twatch->db->columnExists( 'utype', 'ud' ) ) {
				$p->pn( '<p>Updating user data table\'s structure... ' );
				$twatch->db->query( 'ALTER TABLE', 'ud', 'DROP INDEX uid' );
				$twatch->db->query( 'ALTER TABLE', 'ud', 'ADD COLUMN utype INT UNSIGNED NOT NULL FIRST' );
				$twatch->db->query( 'ALTER TABLE', 'ud', 'ADD COLUMN wid INT UNSIGNED NOT NULL AFTER uid' );
				$twatch->db->query( 'ALTER TABLE', 'ud', 'ADD UNIQUE( utype, uid, wid, id, subid, type )' );
				$p->pl( '<span class="good">successful</span></p>' );
			}
				

			
			$twatch->config = new TwatchConfig( $twatch->db );
			$twatch->state = new TwatchState( $twatch->db );

			$twatch->config->addDefaults( TwatchConfig::$defaultProperties );
			$def = array( TwatchConfig::PLUGIN_VERSIONS => array() );
			$twatch->config->addDefaults( $def );
			$twatch->config->applyAllChanges();

			$twatch->state->addDefaults( TwatchState::$defaultProperties );
			$twatch->state->addDefaults( TwatchState::$extraDefaults );
			$twatch->state->applyAllChanges();

			twatchSetAppTime();

			$version = (double)$twatch->config->get( TwatchConfig::VERSION );


			if( $version >= 0.352 ) {
				$p->pl( '<div class="block" style="text-align:center;font-weight:bold"><p><span class="fixed">Nothing to do</span></p></div>' );
				return;
			}

			if( $version < 0.32 ) {
				require_once $twatch->path( 'lib/Common.php' );
				$p->pn( '<p>invalidating user agents cache... ' );
				TwatchUserAgent::invalidateCache();
				$p->pl( '<span class="good">successful</span></p>' );
				$this->setVersion( $p, '0.32' );
			}

			if( $version < 0.33 ) {

				$p->pn( '<p>Extending the dummy table' );
				$twatch->db->installDummyTable( '', 'du', 50, true );
				$p->pl( '<span class="good">successful</span></p>' );

				$p->pl( '<p>Making all period data</p>' );
				$p->pl( '<div style="margin-left:20px">' );
				foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $website ) {
					if( $website->parent ) continue;
					$p->pn( '<p>Website "'.$website->name.'"... ' );
					$q = "INSERT IGNORE INTO ".$twatch->db->tableName( 'h', $website->getSub() )
						." SELECT ".TwatchPeriod::ALL.", '', cid, p1, p2, SUM(c) FROM ".$twatch->db->tableName( 'h', $website->getSub() )
						." WHERE dtt = ".TwatchPeriod::MONTH." GROUP BY cid, p1, p2";
					$twatch->db->query( $q );

					$p->pl( '<span class="good">successful</span></p>' );
				}
				$p->pl( '</div>' );

				$p->pn( '<p>Setting all period availability... ' );
				$taskM = new TwatchTaskManager();
				$taskM->deleteAllTasks( 'TwatchActiveTrimData' );
				foreach( $twatch->state->getList( TwatchState::COUNTERS_AVAIL ) as $id => $ca ) {
					$counterAvail = &$twatch->state->get( TwatchState::COUNTERS_AVAIL, $id );
					if( !isset( $counterAvail->timestamps[ TwatchPeriod::ALL ] ) && isset( $counterAvail->timestamps[ TwatchPeriod::MONTH ] ) ) {
						$counterAvail->timestamps[ TwatchPeriod::ALL ] = $counterAvail->timestamps[ TwatchPeriod::MONTH ];
					}
					$twatch->state->setInternal( TwatchState::COUNTERS_AVAIL, $id );
				}
				$p->pl( '<span class="good">successful</span></p>' );

				$p->pn( '<p>Making active trim tasks' );
				foreach( $twatch->config->getList( TwatchConfig::COUNTERS ) as $counter ) {
					if( $counter->getType() == TwatchCounter::TYPE_SINGLE ) continue;
					if( isset( $counter->activeTrim[ TwatchPeriod::ALL ] ) ) {
						$counter->makeActiveTrimTasks( TwatchPeriod::ALL );
					}
				}
				$p->pl( '<span class="good">successful</span></p>' );

				$p->pl( '<p>Computing counter summations</p>' );
				$p->pl( '<div style="margin-left:20px">' );
				foreach( $twatch->config->getList( TwatchConfig::COUNTERS ) as $counter ) {
					if( $counter->getType() == TwatchCounter::TYPE_SINGLE ) continue;
					$p->pl( '<p>Counter "'.$counter->name.'"</p>' );
					$p->pl( '<div style="margin-left:20px">' );

					foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $website ) {
						if( $website->parent ) continue;
						$p->pn( '<p>Website "'.$website->name.'"... ' );
						$twatch->db->query( 'INSERT IGNORE INTO '.$twatch->db->tableName( 'h', $website->getSub() )
							.' SELECT dtt, dt, cid, p1, 0, SUM(c) FROM '.$twatch->db->tableName( 'h', $website->getSub() ).' WHERE cid = '.$counter->id
							.' GROUP BY dtt, dt, cid, p1' );
						$p->pl( '<span class="good">successful</span></p>' );
					}
					$p->pl( '</div>' );
				}
				$p->pl( '</div>' );

				$p->pl( '<p>Upgrading Indexes</p>' );
				$p->pl( '<div style="margin-left:20px">' );
				$p->pn( '<p>Dict... ' );
				try { $twatch->db->query( 'ALTER TABLE', 'd', 'DROP INDEX c1' ); } catch( ArdeException $e ) {}
				try { $twatch->db->query( 'ALTER TABLE', 'd', 'DROP INDEX c2' ); } catch( ArdeException $e ) {}
				$twatch->db->query( 'ALTER TABLE', 'd', 'ADD INDEX( c1, did ), ADD INDEX( c2, did )' );
				$p->pl( '<span class="good">successful</span></p>' );

				foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $website ) {
					if( $website->parent ) continue;
					$p->pl( '<p>Website "'.$website->name.'"</p>' );

					$p->pl( '<div style="margin-left:20px">' );
					$p->pn( '<p>Sessions... ' );
					try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 's', 'DROP INDEX ip' ); } catch( ArdeException $e ) { ArdeException::reportError( $e ); }
					try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 's', 'DROP INDEX pcookie' ); } catch( ArdeException $e ) {}
					try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 's', 'DROP INDEX first' ); } catch( ArdeException $e ) {}
					try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 's', 'DROP INDEX last' ); } catch( ArdeException $e ) {}
					$twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 's', 'ADD INDEX( ip ), ADD INDEX( pcookie ), ADD INDEX( first ), ADD INDEX( last )' );
					$p->pl( '<span class="good">successful</span></p>' );

					$p->pn( '<p>Requests... ' );
					try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'sr', 'DROP INDEX pid' ); } catch( ArdeException $e ) {}
					$twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'sr', 'ADD INDEX( pid )' );
					$p->pl( '<span class="good">successful</span></p>' );

					$p->pn( '<p>Request Data... ' );
					try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'rd', 'DROP INDEX sid' ); } catch( ArdeException $e ) {}
					try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'rd', 'DROP INDEX eid' ); } catch( ArdeException $e ) {}
					$twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'rd', 'ADD INDEX( sid ), ADD INDEX( eid, p )' );
					$p->pl( '<span class="good">successful</span></p>' );

					$p->pn( '<p>Counters... ' );
					try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'h', 'DROP INDEX cid' ); } catch( ArdeException $e ) { ArdeException::reportError( $e ); }
					try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'h', 'DROP INDEX inc' ); } catch( ArdeException $e ) {}
					try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'h', 'DROP INDEX report' ); } catch( ArdeException $e ) {}
					$twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'h', 'ADD UNIQUE INDEX inc( cid, p2, p1, dtt, dt ), ADD INDEX report( cid, p1, dtt, dt, c )' );
					$p->pl( '<span class="good">successful</span></p>' );

					if( $twatch->state->get( TwatchState::PATH_ANALYZER_INSTALLED ) ) {
						$p->pn( '<p>Path Analysis... ' );
						try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'p', 'DROP INDEX p1' ); } catch( ArdeException $e ) {}
						try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'p', 'DROP INDEX p2' ); } catch( ArdeException $e ) {}
						try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'p', 'DROP INDEX p3' ); } catch( ArdeException $e ) {}
						try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'p', 'DROP INDEX p4' ); } catch( ArdeException $e ) {}
						try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'p', 'DROP INDEX p5' ); } catch( ArdeException $e ) {}
						$twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'p', 'ADD INDEX( p1 ), ADD INDEX( p2 ), ADD INDEX( p3 ), ADD INDEX( p4 ), ADD INDEX( p5 )' );
						$p->pl( '<span class="good">successful</span></p>' );
					}
					$p->pl( '</div>' );
				}
				$p->pl( '</div>' );
				$this->setVersion( $p, '0.33' );
			}

			if( $version < 0.331 ) {
				foreach( $twatch->plugins as $plugin ) {
					if( $plugin->needsInstall() ) {
						$p->pn( '<p>setting "'.$plugin->getName().'" plugin version' );
						if( !$twatch->config->propertyExists( TwatchConfig::PLUGIN_VERSIONS, $twatch->config->getStartId() + $plugin->id ) ) {
							$twatch->config->set( $plugin->getVersion(), TwatchConfig::PLUGIN_VERSIONS, $twatch->config->getStartId() + $plugin->id );
						}
						$p->pl( '<span class="good">successful</span></p>' );
					}
				}
				$this->setVersion( $p, '0.331' );
			}

			if( $version < 0.332 ) {
				require_once $twatch->path( 'lib/Common.php' );
				$p->pn( '<p>invalidating search engines cache... ' );
				TwatchSearchEngine::invalidateCache();
				$p->pl( '<span class="good">successful</span></p>' );
				$this->setVersion( $p, '0.332' );
			}

			if( $version < 0.335 ) {
				$p->pn( '<p>Converting tables\' character set' );
				require_once $twatch->extPath( 'user', 'lib/Global.php' );
				global $ardeUser;
				$ardeUser->db = new ArdeDb( $ardeUser->settings );
				$ardeUser->db->connect();
				$ardeUser->db->reinterpretColumn( '', 'lr', 'rnd', 'CHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL' );
				$ardeUser->db->reinterpretColumn( '', 'u', 'rnd', 'CHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL' );
				try {
					$ardeUser->db->reinterpretColumn( '', 'rs', 'rnd', 'rnd CHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL' );
				} catch( Exception $e ) {}

				foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $website ) {
					if( $website->parent ) continue;
					$twatch->db->reinterpretColumn( $website->getSub(), 'h', 'dt', 'CHAR(7) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL', true );
				}

				$twatch->db->reinterpretColumn( '', 'cm', 'dt', 'CHAR(6) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL', true );
				$twatch->db->reinterpretColumn( '', 'cm', 'txt', 'TEXT CHARACTER SET '.$twatch->db->getCharset().' COLLATE '.$twatch->db->getCollation() );

				$twatch->db->reinterpretColumn( '', 'd', 'str', 'VARCHAR('.(TwatchDbDict::STRING_SIZE+1).') CHARACTER SET '.$twatch->db->getCharset().' COLLATE '.$twatch->db->getCollation().' NOT NULL DEFAULT \'\'', true );
				$twatch->db->reinterpretColumn( '', 'd', 'ext', 'VARCHAR(255) CHARACTER SET '.$twatch->db->getCharset().' COLLATE '.$twatch->db->getCollation().' NOT NULL DEFAULT \'\'' );

				$twatch->db->reinterpretColumn( '', 'err', 'str', 'TEXT  CHARACTER SET '.$twatch->db->getCharset().' COLLATE '.$twatch->db->getCollation() );

				$twatch->db->reinterpretColumn( '', 't', 'type', 'CHAR(64) CHARACTER SET latin1 COLLATE latin1_bin', true );
				$twatch->db->reinterpretColumn( '', 'tq', 'type', 'CHAR(64) CHARACTER SET latin1 COLLATE latin1_bin', true );
				$twatch->db->reinterpretColumn( '', 't', 'v', 'TEXT CHARACTER SET '.$twatch->db->getCharset().' COLLATE '.$twatch->db->getCollation().' NOT NULL' );
				$twatch->db->reinterpretColumn( '', 'tq', 'v', 'TEXT CHARACTER SET '.$twatch->db->getCharset().' COLLATE '.$twatch->db->getCollation().' NOT NULL' );

				$twatch->db->reinterpretColumn( '', 'c', 'v', 'TEXT CHARACTER SET '.$twatch->db->getCharset().' COLLATE '.$twatch->db->getCollation() );
				$twatch->db->reinterpretColumn( '', 's', 'v', 'TEXT CHARACTER SET '.$twatch->db->getCharset().' COLLATE '.$twatch->db->getCollation() );
				$twatch->db->reinterpretColumn( '', 'ud', 'v', 'TEXT CHARACTER SET '.$twatch->db->getCharset().' COLLATE '.$twatch->db->getCollation() );

				$p->pl( '<span class="good">successful</span></p>' );
				$this->setVersion( $p, '0.335' );
			}
			
			if( $version < 0.338 ) {
				$p->pn( '<p>Changing some indexes... ' );
				foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $website ) {
					if( $website->parent ) continue;	
					try { $twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'sr', 'DROP INDEX sid' ); } catch( ArdeException $e ) {}
					$twatch->db->query_sub( $website->getSub(), 'ALTER TABLE', 'sr', 'ADD INDEX( sid, time, id )' );
				}
				$p->pl( '<span class="good">successful</span></p>' );
				$this->setVersion( $p, '0.338' );
			}
			
			if( $version < 0.350 ) {
				$p->pn( '<p>Making instance ID... ' );
				$twatch->config->set( $twatch->makeInstanceId(), TwatchConfig::INSTANCE_ID );
				$p->pl( '<span class="good">successful</span></p>' );
				
				$p->pn( '<p>Moving some data from config to user data... ' );
				$twatch->db->query( 'UPDATE', 'ud', 'SET utype = 2, uid = 1' );
				$twatch->db->query( 'INSERT INTO '.$twatch->db->table( 'ud' ).' SELECT 0, 0, 0, 14, subid, type, v, pos FROM '.$twatch->db->table( 'c' ).' WHERE id = 5' );
				$twatch->db->query( 'INSERT INTO '.$twatch->db->table( 'ud' ).' SELECT 0, 0, 0, 15, subid, type, v, pos FROM '.$twatch->db->table( 'c' ).' WHERE id = 8' );
				$twatch->db->query( 'INSERT INTO '.$twatch->db->table( 'ud' ).' SELECT 0, 0, 0, 20, subid, type, v, pos FROM '.$twatch->db->table( 'c' ).' WHERE id = 25' );
				$twatch->db->query( 'DELETE FROM', 'c', 'WHERE id IN( 5, 8 ,25 )' );
				$p->pl( '<span class="good">successful</span></p>' );

				$p->pn( '<p>invalidating user agents cache... ' );
				require_once $twatch->path( 'lib/Common.php' );
				TwatchUserAgent::invalidateCache();
				$p->pl( '<span class="good">successful</span></p>' );
				
				$this->setVersion( $p, '0.350' );
			}
			
			if( $version < 0.351 ) {
				$p->pn( '<p>invalidating web areas cache... ' );
				require_once $twatch->path( 'lib/Common.php' );
				TwatchSearchEngine::invalidateCache();
				$p->pl( '<span class="good">successful</span></p>' );
				
				$this->setVersion( $p, '0.351' );
			}
			
			if( $version < 0.352 ) {
				require_once $twatch->path( 'lib/Common.php' );
				$p->pn( '<p>invalidating web areas cache... ' );
				TwatchSearchEngine::invalidateCache();
				$p->pl( '<span class="good">successful</span></p>' );
				
				$p->pn( '<p>invalidating user agents cache... ' );
				TwatchUserAgent::invalidateCache();
				$p->pl( '<span class="good">successful</span></p>' );
				
				$this->setVersion( $p, '0.352' );
			}

			$p->pl( '<div class="block" style="text-align:center;font-weight:bold"><p><span class="fixed">TraceWatch Successfully Updated</span></p></div>' );

			
		}

		public function setVersion( ArdePrinter $p, $version ) {
			global $twatch;
			$twatch->config->set( $version, TwatchConfig::VERSION );
			$p->pl( '<p>Successfully updated to '.$version.'</p>' );
		}
	}

	$twatch->applyOverrides( array( 'TwatchUpdatePage' => true ) );

	$page = $twatch->makeObject( 'TwatchUpdatePage' );

	$page->render( $p );

?>