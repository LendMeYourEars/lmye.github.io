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

	require_once $twatch->path( 'db/DbLogger.php' );
	require_once $ardeBase->path( 'lib/ArdeExpression.php' );
	require_once $twatch->path( 'lib/General.php' );
	require_once $twatch->path( 'lib/Entity.php' );
	require_once $twatch->path( 'lib/Common.php' );


	class TwatchRequest {
		var $data = array();

		var $dict;



		var $entityVIds = array();



		var $det_vt = TwatchVisitorType::NORMAL;

		var $req_info;

		var $latest_data = array();

		var $websiteId;
		var $parentWebsiteId;

		var $genes = array();
		var $activeGenes = array();
		var $doneGenes = array();

		var $otherWebsitesPCookies = array();
		var $otherWebsitesSCookies = array();

		const NEW_SESSION = 1;
		const VISITOR_DAYFIRST = 2;
		const NEW_VISITOR = 3;
		const DELAYED = 4;
		const CHANGED = 5;
		const SESSION_ID = 6;
		const REQUEST_ID = 7;

		public static $infoStrings = array(
			 self::NEW_SESSION => 'is New Session'
			,self::VISITOR_DAYFIRST => "is First Request Today"
			,self::NEW_VISITOR => 'is New Visitor'
			,self::DELAYED => 'is Delayed'
			,self::CHANGED => 'Has Changed'
			,self::SESSION_ID => 'Session ID'
			,self::REQUEST_ID => 'Request ID'
		);

		function __construct( $websiteId = 1 ) {
			global $twatch;

			$this->websiteId = $websiteId;

			$this->dict = new TwatchDbDict( $twatch->db );


		}

		function loadConfig() {
			global $twatch;

			require_once $twatch->path( 'data/DataGlobal.php' );

			$twatch->config = new TwatchConfig( $twatch->db );
			$twatch->config->addDefaults( TwatchConfig::$defaultProperties );
			$twatch->config->applyAllChanges();

			$twatch->now = new TwatchTime( time() + $twatch->config->get( TwatchConfig::TIME_DIFFERENCE ) );

			$twatch->state = new TwatchState( $twatch->db );
			$twatch->state->addDefaults( TwatchState::$defaultProperties );
			$twatch->state->applyAllChanges();


			$this->website = &TwatchWebsite::getWebsiteFromId( $this->websiteId );
			$this->websiteId = $this->website->getId();

			if( $this->website->parent ) {
				if( !$twatch->config->propertyExists( TwatchConfig::WEBSITES, $this->website->parent ) ) throw new TwatchException( 'parent website '.$this->website->parent.' not found' );
				$this->parentWebsiteId = $this->website->parent;
				$this->parent_website = &$twatch->config->get( TwatchConfig::WEBSITES, $this->website->parent );
			} else {
				$this->parentWebsiteId = $this->websiteId;
				$this->parent_website = &$this->website;
			}
			$this->sub = $this->parent_website->getSub();

			$this->db_req = new TwatchDbRequestData( $twatch->db, $this->sub );

			return true;
		}


		function data_changed( $ent, $id ) {
			if( !isset( $this->latest_data[ $ent ] )) return true;
			if( $this->latest_data[$ent] != $id ) return true;
			return false;
		}

		public function geneExists( $entityId ) {
			return isset( $this->genes[ $entityId ] );
		}

		public function &getGene( $entityId ) {
			return $this->genes[ $entityId ];
		}

		public function generateEntities() {
			global $twatch;

			$genes = array();
			$this->genes = array();


			foreach( $twatch->config->getList( TwatchConfig::ENTITIES ) as $entity ) {
				if( !$twatch->state->propertyExists( TwatchState::OFF_ENTITY, $entity->id ) ) {
					$genes[ $entity->id ] = &$entity->gene;
					$this->genes[ $entity->id ] = &$entity->gene;
				}
			}

			while( count( $genes ) ) {
				reset( $genes );
				$this->insertPrecedents( $genes, key( $genes ) );
			}



			$this->generateEntitiesAttempt( 1 );

			$this->dict->rollGet();

			$this->generateEntitiesAttempt( 2 );

			$this->dict->rollGet();

			$this->generateEntitiesAttempt( 3 );

			$putOrder = array();
			foreach( $this->activeGenes as $key => $gene ) {
				$this->insertDictPutPrecedents( $key, $putOrder );
			}

			$this->dict->rollPut( $putOrder );

			$this->generateEntitiesAttempt( 4 );


			foreach( $this->doneGenes as $gene ) {
				$this->entityVIds[ $gene->entityId ] = $gene->valueId;
			}

		}

		private function insertDictPutPrecedents( $key, &$dest ) {
			if( in_array( $key, $dest ) ) return;
			if( !isset( $this->activeGenes[ $key ] ) ) return;
			$pres = $this->activeGenes[ $key ]->getDictPutPrecedents();
			foreach( $pres as $pre ) {
				$this->insertDictPutPrecedents( $pre, $dest );
			}
			$dest[] = $key;
		}

		private function insertPrecedents( &$src, $key ) {
			if( !isset( $src[ $key ] ) ) return;
			$pres = $src[ $key ]->getPrecedents();
			foreach( $pres as $pre ) {
				$this->insertPrecedents( $src, $pre );
			}
			$this->activeGenes[ $key ] = &$src[ $key ];
			unset( $src[ $key ] );
		}

		function generateEntitiesAttempt( $no ) {
			$methodName = 'attempt'.$no;
			foreach( $this->activeGenes as &$gene ) {
				try {
					$res = $gene->$methodName( $this );
					if( $res === true ) {
						$this->doneGenes[ $gene->entityId ] = &$gene;
						unset( $this->activeGenes[ $gene->entityId ] );
					} elseif( $res === false ) {
						unset( $this->activeGenes[ $gene->entityId ] );
					}
				} catch( ArdeException $e ) {


					ArdeException::reportError( $e );
					unset( $this->activeGenes[ $gene->entityId ] );
				}
			}
		}

		function identifyVisitorType() {
			$this->det_vt = TwatchVisitorType::identifyVisitorType( $this->entityVIds, $this );
		}


		function runCounters() {
			global $twatch;


			$history = new TwatchDbHistory( $twatch->db, $this->sub );
			$cous = &$twatch->config->getList( TwatchConfig::COUNTERS );

			foreach( $cous as $cou ) {

				if( $twatch->state->propertyExists( TwatchState::OFF_COUNTER, $cou->id ) ) continue;


				if( !count( $cou->when )) $res = true;

				else {
					$s = new TwatchExpression( $cou->when, $this );
					$res = $s->evaluate();

				}
				if( $res ) {
					$p1 = null;
					if( $cou->getType() == TwatchCounter::TYPE_SINGLE ) {
						$p1 = 0;
						$p2 = 0;
					} elseif( $cou->getType() == TwatchCounter::TYPE_LIST ) {
						if( isset( $this->entityVIds[$cou->entityId] )) {
							$p1 = 0;
							$p2 = $this->entityVIds[$cou->entityId];
						}
					} elseif( $cou->getType() == TwatchCounter::TYPE_GROUPED ) {
						if( isset( $this->entityVIds[$cou->entityId] )
							&& isset( $this->entityVIds[$cou->groupEntityId]) ) {
							$p1 = $this->entityVIds[$cou->groupEntityId];
							$p2 = $this->entityVIds[$cou->entityId];
						}
					}

					if( $p1 !== null ) {
						$cou->increment( $history, $p1, $p2 );
					}
				}
			}
			$history->roll_increments();
		}



		function writeRequestData() {
			global $twatch;

			$wris = &$twatch->config->getList( TwatchConfig::RDATA_WRITERS );
			$written = array();
			foreach( $wris as $wri ) {
				if( isset( $written[ $wri->entityId ] ) ) continue;
				if( !count( $wri->when )) $res = true;
				else {
					$s = new TwatchExpression( $wri->when, $this );
					$res = $s->evaluate();
				}
				if( $res ) {
					if( isset( $this->entityVIds[$wri->entityId] )) {
						$written[ $wri->entityId ] = true;
						$this->db_req->add( $this->req_info[ TwatchRequest::REQUEST_ID ], $this->req_info[ TwatchRequest::SESSION_ID ], $wri->entityId, $this->entityVIds[ $wri->entityId ] );
					}
				}
			}
			$this->db_req->roll_adds();
		}

		function runDistributedTasks() {
			global $twatch;
			$taskm = new TwatchTaskManager();
			$tasks = $taskm->popTasks( $twatch->now->ts );
			foreach( $tasks as $k => $v ) {
				$tasks[$k]->run();
			}
		}



		public function valueChanged( $entityId ) {

			if( !isset( $this->latest_data[$entityId ] ) ) {
				return true;
			}
			if( !isset( $this->entityVIds[ $entityId ] )) {
				return false;
			}
			return $this->latest_data[ $entityId ] != $this->entityVIds[ $entityId ];

		}

		function in_today($ts) {
			global $twatch;
			return $ts >= $twatch->now->getDayStart();;
		}


		function identifyRequest() {
			global $twatch;
			$this->db_session = new TwatchDbSession( $twatch->db, 300, $this->sub );

			if( !isset( $this->entityVIds[TwatchEntity::PAGE] ) )
				throw new TwatchException( 'Required data page not set' );
			if( !isset( $this->entityVIds[TwatchEntity::IP] ) )
				throw new TwatchException( 'Required data IP not set' );
			$page = $this->entityVIds[TwatchEntity::PAGE];
			$ip = $this->entityVIds[TwatchEntity::IP];
			if( isset( $this->entityVIds[TwatchEntity::SCOOKIE] ))
				$scookie = $this->entityVIds[TwatchEntity::SCOOKIE];
			if( isset( $this->entityVIds[TwatchEntity::PCOOKIE] ))
				$pcookie = $this->entityVIds[TwatchEntity::PCOOKIE];
			$now = $GLOBALS['twatch']->now->ts;
			$session_space = 300;


			$this->req_info[TwatchRequest::NEW_VISITOR] = false;
			$this->req_info[TwatchRequest::NEW_SESSION] = false;
			$this->req_info[TwatchRequest::VISITOR_DAYFIRST] = false;

			$oldVt = null;

			if( !isset( $scookie ) && !isset( $pcookie ) ) {
				$old_session = $this->db_session->get_session_by_ip( $ip );

				if( $old_session !== null ) {
					$this->req_info[TwatchRequest::VISITOR_DAYFIRST] = !$this->in_today( $old_session['last'] );

					if( $old_session['last'] > $now - $session_space ) {
						$sid = $old_session['sid'];

						$oldVt = $old_session[ 'vt' ];

						$this->db_session->update_session_last( $sid, $now );

					} else {
						$this->req_info[TwatchRequest::NEW_SESSION] = true;
						$sid = $this->db_session->new_session( 0, 0, $ip, $now, $now, $this->det_vt );
					}

				} else {
					$this->req_info[TwatchRequest::NEW_SESSION] = true;
					$this->req_info[TwatchRequest::NEW_VISITOR] = true;
					$this->req_info[TwatchRequest::VISITOR_DAYFIRST] = true;
					$sid = $this->db_session->new_session( 0, 0, $ip, $now, $now, $this->det_vt );
				}

				$rid = $this->db_session->new_request( $sid, $page, $now );

				$this->scookie_to_set = $rid;
				$this->pcookie_to_set = $rid;

			} elseif( isset( $pcookie ) && !isset( $scookie ) ) {

				$old_session = $this->db_session->get_session_by_pcookie( $pcookie );

				if( $old_session !== null ) {

					$this->req_info[TwatchRequest::VISITOR_DAYFIRST] = !$this->in_today( $old_session['last'] );

					if( $old_session['last'] > $now - $session_space ) {
						$sid = $old_session[ 'sid' ];
						$oldVt = $old_session[ 'vt' ];
						$this->db_session->update_session_last( $sid, $now );

					} else {
						$this->req_info[TwatchRequest::NEW_SESSION] = true;
						$sid = $this->db_session->new_session( 0, $pcookie, $ip, $now, $now, $this->det_vt );
					}

				} else {
					$scookie_session = $this->db_session->get_session_by_scookie( $pcookie );

					if( $scookie_session !== null ) {
						$visitor_last = $this->db_session->get_visitor_last( $pcookie );
						$tis->req_info[TwatchRequest::VISITOR_DAYFIRST] = !$this->in_today( $visitor_last );

						$this->req_info[TwatchRequest::NEW_SESSION] = true;
						$sid = $this->db_session->new_session( 0, $pcookie, $ip, $now, $now, $this->det_vt );
					} else {

						$old_session = $this->db_session->get_request_first_last( $pcookie );

						if( isset( $old_session['request'] ) ) {

							$this->req_info[TwatchRequest::VISITOR_DAYFIRST] = !$this->in_today( $old_session['request']['time'] );

							if( $old_session['first']['id'] == $pcookie && $old_session['last']['id'] == $pcookie ) {
								$this->db_session->delete_session( $old_session['request']['sid'] );

							} else {

								if( $old_session['first']['id'] == $pcookie ) {
									$this->db_session->update_session_first( $old_session['request']['sid'], $old_session['after first']['time'] );
								} elseif( $old_session['last']['id'] == $pcookie ) {
									$this->db_session->update_session_last( $old_session['request']['sid'], $old_session['before last']['time'] );
								} else {
								}
							}
							if( $old_session['request']['time'] <= $now - $session_space ) {

								$sid = $this->db_session->new_session( 0, $pcookie, $ip, $old_session['request']['time'], $old_session['request']['time'], $this->det_vt );

								$this->db_session->update_request_sid( $sid, $old_session['request']['id'] );

								$this->req_info[TwatchRequest::NEW_SESSION] = true;
								$sid = $this->db_session->new_session( 0, $pcookie, $ip, $now, $now, $this->det_vt );

							} else {
								$sid = $this->db_session->new_session( 0, $pcookie, $ip, $old_session['request']['time'], $now, $this->det_vt );

								$this->db_session->update_request_sid( $sid, $old_session['request']['id'] );
							}

						} else {
							$this->req_info[TwatchRequest::VISITOR_DAYFIRST] = true;
							$this->req_info[TwatchRequest::NEW_SESSION] = true;
							$sid = $this->db_session->new_session( 0, $pcookie, $ip, $now, $now, $this->det_vt );
						}
					}
				}
				$rid = $this->db_session->new_request( $sid, $page, $now );

				$this->scookie_to_set = $rid;

			} elseif( isset( $scookie ) ) {
				$old_session = $this->db_session->get_session_by_scookie( $scookie );

				if( $old_session !== null ) {
					$sid = $old_session[ 'sid' ];
					$oldVt = $old_session[ 'vt' ];
					$this->req_info[TwatchRequest::VISITOR_DAYFIRST] = !$this->in_today( $old_session['last'] );

					$this->db_session->update_session_last( $sid, $now );

				} else {
					$old_session = $this->db_session->get_request_first_last( $scookie );

					if( isset( $old_session['request'] )) {

						$this->req_info[TwatchRequest::VISITOR_DAYFIRST] = !$this->in_today( $old_session['request']['time'] );

						if( $old_session['first']['id'] == $scookie && $old_session['last']['id'] == $scookie ) {
							$this->db_session->delete_session( $old_session['request']['sid'] );
						} else {

							if( $old_session['first']['id'] == $scookie ) {
								$this->db_session->update_session_first( $old_session['request']['sid'], $old_session['after first']['time'] );
							} elseif( $old_session['last']['id'] == $scookie ) {
								$this->db_session->update_session_last( $old_session['request']['sid'], $old_session['before last']['time'] );
							} else {
							}
						}

						$sid = $this->db_session->new_session( $scookie, isset($pcookie)?$pcookie:0, $ip, $old_session['request']['time'], $now, $this->det_vt );

						$this->db_session->update_request_sid( $sid, $old_session['request']['id'] );

					} else {
						$this->req_info[TwatchRequest::VISITOR_DAYFIRST] = true;
						$sid = $this->db_session->new_session( $scookie, isset($pcookie)?$pcookie:0, $ip, $now, $now, $this->det_vt );
					}

				}

				$rid = $this->db_session->new_request( $sid, $page, $now );

				if( !isset( $pcookie ) ) $this->pcookie_to_set = $rid;
			}

			if( $oldVt !== null && $oldVt != $this->det_vt ) {
				if( $oldVt == TwatchVisitorType::NORMAL || $this->det_vt == TwatchVisitorType::ADMIN ) {
					$this->db_session->updateVisitorType( $sid, $this->det_vt );
				}
			}

			$this->req_info[TwatchRequest::SESSION_ID] = $sid;
			$this->req_info[TwatchRequest::REQUEST_ID] = $rid;



			$this->latest_data = $this->db_req->get_latest( $this->req_info[ TwatchRequest::SESSION_ID ] );

			return true;

		}

		function getCookieDomain() {
			global $twatch;
			if( $this->website->cookieDomain ) return $this->website->cookieDomain;
			if( $this->website->parent && $this->parent_website->cookieDomain ) return $this->parent_website->cookieDomain;
			return $twatch->settings[ 'cookie_domain' ];
		}

		function getCookieFolder() {
			global $twatch;
			if( $this->website->cookieFolder ) return $this->website->cookieFolder;
			if( $this->website->parent && $this->parent_website->cookieFolder ) return $this->parent_website->cookieFolder;
			return $twatch->settings[ 'cookie_folder' ];
		}

		function getPcookieToSet() {
			global $twatch;
			if( !isset( $this->pcookie_to_set ) ) return false;
			$rids = $this->otherWebsitesPCookies;
			$rids[ $this->parentWebsiteId ] = $this->pcookie_to_set;
			return $twatch->config->get( TwatchConfig::COOKIE_KEYS )->encrypt( $rids, false );
		}

		function getScookieToSet() {
			global $twatch;
			if( !isset( $this->scookie_to_set ) ) return false;
			$rids = $this->otherWebsitesSCookies;
			$rids[ $this->parentWebsiteId ] = $this->scookie_to_set;
			return $twatch->config->get( TwatchConfig::COOKIE_KEYS )->encrypt( $rids, true );
		}

		public function log() {
			global $twatch;

			$this->loadConfig();

			$this->generateEntities();

			$this->identifyVisitorType();

			$loggerWhen = new TwatchExpression( $twatch->config->get( TwatchConfig::LOGGER_WHEN ), $this );
			$loggerWhen->emptyValue = true;

			if( $loggerWhen->evaluate() ) {

				$this->identifyRequest();

				try {
					$countersWhen = new TwatchExpression( $twatch->config->get( TwatchConfig::COUNTERS_WHEN ), $this );
					$countersWhen->emptyValue = true;
	
					if( $countersWhen->evaluate() ) {
						$this->runCounters();
					}
	
					$this->writeRequestData();
				}  catch( ArdeException $e ) {
					ArdeException::reportError( $e );
				}

			}
			
			try {
				$this->runDistributedTasks();
			}  catch( ArdeException $e ) {
				ArdeException::reportError( $e );
			}
		}

	}




?>