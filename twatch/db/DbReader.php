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

	global $ardeBase;

	require_once $ardeBase->path( 'db/Db.php' );

	class TwatchDbrGraph {
		var $db;
		var $sub;

		function TwatchDbrGraph( $db, $sub ) {
			$this->db = $db;
			$this->sub = $sub;
		}

		function get( $periodType, $counterId, $groupId, $entityVId, $fromCode = false, $toCode = false ) {
			$res = $this->db->query_sub( $this->sub, 'SELECT dt, c from', 'h',
						"WHERE dtt = ".$periodType." AND cid=".$counterId." AND p1=".$groupId." AND p2=".$entityVId );
			$o = array();
			while( $r = mysql_fetch_row( $res ) ) {
				$o[ $r[0] ] = $r[1];
			}
			return $o;
		}

	}

	class TwatchDbrResultPath {
		public $left;
		public $right;
	}

	class TwatchDbrResultPathCol {
		public $data = array();
		public $sum = 0;

	}

	class TwatchDbrPathCriteria {
		public $criteria;
		public $leftColumnIndex;
		public $rightColumnIndex;
	}

	class TwatchDbrPath {
		var $db;
		var $sub;

		function TwatchDbrPath( $db, $sub ) {
			$this->db = $db;
			$this->sub = $sub;
		}

		function getColumn( $columnIndex ) {

			$res = $this->db->query_sub( $this->sub, 'SELECT p'.$columnIndex.' AS v, SUM(c) AS c FROM', 'p',
													'GROUP BY v ORDER BY c DESC LIMIT 15' );

			$out = new TwatchDbrResultPathCol();
			$out->data = array();

			while( $r = mysql_fetch_row( $res ) ) {
				$out->data[ ardeStrToU32($r[0]) ]= (int)$r[1];
			}

			$res = $this->db->query_sub( $this->sub, 'SELECT SUM(c) FROM', 'p' );

			if( !$r = mysql_fetch_row( $res ) ) return __e( 'db get path column', 'sum was not returned', 0, $res );

			$out->sum = (int)$r[0];
			return $out;
		}

		function get( $criterias ) {
			$leftSel = '0';
			$rightSel = '0';
			$wheres = new ArdeAppender( ' OR ' );
			foreach( $criterias as $criteria ) {
				$where = new ArdeAppender( ' AND ' );
				foreach( $criteria->criteria as $columnIndex => $entityVId ) {
					if( $entityVId != 0 ) {
						$where->append( 'p'.$columnIndex.' = '.$entityVId );
					}
				}
				$wheres->append( '( '.$where->s.' )' );
				$cLeftSel = ( $criteria->leftColumnIndex >= 0 ? 'p' : '' ).$criteria->leftColumnIndex ;
				$leftSel =  'IF( '.$where->s.', '.$cLeftSel.', '.$leftSel.' )';
				$cRightSel = ( $criteria->rightColumnIndex >= 0 ? 'p' : '' ).$criteria->rightColumnIndex;
				$rightSel =  'IF( '.$where->s.', '.$cRightSel.', '.$rightSel.' )';
			}

			$out = new TwatchDbrResultPath();
			$out->left = new TwatchDbrResultPathCol();
			$out->right = new TwatchDbrResultPathCol();

			$res = $this->db->query_sub( $this->sub, 'SELECT '.$leftSel.' AS v, SUM(c) AS cnt FROM','p',
										'WHERE '.$wheres->s.' GROUP BY v ORDER BY cnt DESC LIMIT 20');

			while( $r = mysql_fetch_row( $res ) ) {
				if( $r[0][0] == '-' ) $k = (int)$r[0];
				else $k = ardeStrToU32($r[0]);
				$out->left->data[ $k ] = (int)$r[1];
			}

			$res = $this->db->query_sub( $this->sub, 'SELECT '.$rightSel.' AS v, SUM(c) AS cnt FROM','p',
										'WHERE '.$wheres->s.' GROUP BY v ORDER BY cnt DESC LIMIT 20');
			while( $r = mysql_fetch_row( $res ) ) {
				if( $r[0][0] == '-' ) $k = (int)$r[0];
				else $k = ardeStrToU32($r[0]);
				$out->right->data[ $k ] = (int)$r[1];
			}

			$res = $this->db->query_sub( $this->sub, 'SELECT SUM(c) FROM', 'p','WHERE '.$wheres->s );
			if( !$r = mysql_fetch_row( $res ) ) return __e( 'db read path', 'sum was not returned' );
			$out->left->sum = $out->right->sum = (int)$r[0];

			return $out;
		}
	}


	class TwatchDbrHistoryRequest {
		public $counterId;
		public $periodType;
		public $periodCode;
		public $group;
		public $limit;
		public $total;
		public $single;
		public $entityVId;

		public function TwatchDbrHistoryRequest( $counterId, $periodType, $periodCode, $group, $limit, $total, $single, $entityVId ) {
			$this->counterId = $counterId;
			$this->periodType = $periodType;
			$this->periodCode = $periodCode;
			$this->group = $group;
			$this->limit = $limit;
			$this->total = $total;
			$this->single = $single;
			$this->entityVId = $entityVId;
		}
	}

	class TwatchDbrHistoryResult {

		public $entityVId;
		public $count;

		public function TwatchDbrHistoryResult( $entityVId, $count ) {
			$this->entityVId = $entityVId;
			$this->count = $count;
		}

	}


	class TwatchDbrHistory {
		private $db;
		private $sub;
		private $requests = array();
		private $results = array();
		private $totals = array();

		function TwatchDbrHistory( $db, $sub ) {
			$this->db = $db;
			$this->sub = $sub;
		}


		public function get( $counterId, $periodType, $periodCode, $group = 0, $single, $limit, $total = true, $entityVId = null ) {
			$key = $this->makeKey( $counterId, $periodType, $periodCode, $group, $entityVId );

			if( $limit ) $limit += 1;

			$this->requests[ $key ] = new TwatchDbrHistoryRequest( $counterId, $periodType, $periodCode, $group, $limit, $total, $single, $entityVId );
		}

		public function makeKey( $counterId, $periodType, $periodCode, $group, $entityVId ) {
			$k = $counterId.'-'.$periodType.'-'.$periodCode.'-'.$group;
			if( $entityVId !== null ) $k .= '-'.ardeU32ToStr( $entityVId );
			return $k;
		}

		public function rollGet() {
			if( !count( $this->requests ) ) return;
			foreach( $this->requests as $key => $req ) {
				if( $req->single ) {
					$this->results[ $key ] = 0;
					if( $req->entityVId !== null ) $andEv = ' AND p2='.ardeU32ToStr( $req->entityVId );
					else $andEv = '';
						$this->db->add_union_sub( $this->sub, "SELECT '".$key."' AS k, 0 AS p2, c FROM", 'h',
							'WHERE dtt='.$req->periodType." AND dt='".$req->periodCode."' AND cid=".$req->counterId." AND p1=".$req->group.$andEv );
				} else {
					$this->results[ $key ] = array();
						$this->db->add_union_sub( $this->sub, "SELECT '".$key."' AS k, p2, c FROM", 'h',
							"WHERE dtt=".$req->periodType." AND dt='".$req->periodCode."' AND cid=".$req->counterId." AND p1=".$req->group.
							" ORDER BY c DESC".( $req->limit ? " LIMIT ".$req->limit : '' ) );
						if( $req->total ) {
							$this->totals[ $key ] = 0;
						}
				}
			}

			$i = 0;
			foreach( $this->db->unions as $union ) {
				++$i;
			}
			$res = $this->db->roll_unions( count( $this->db->unions ) > 1 ? 'ORDER BY k,c DESC' : '' );

			while( $r = mysql_fetch_row( $res ) ) {

				if( $this->requests[ $r[0] ]->single ) {
					$this->results[ $r[0] ] = (int)$r[2];
				} else {
					if( $r[1] == 0 ) {

						$this->totals[ $r[0] ] = (int)$r[2];
					} else {
						$this->results[ $r[0] ][] = new TwatchDbrHistoryResult( ardeStrToU32( $r[1] ), (int)$r[2] );
					}
				}
			}

			return true;
		}


		public function getResult( $counterId, $periodType, $periodCode, $group = 0, $entityVId = null ) {
			return $this->results[ $this->makeKey( $counterId, $periodType, $periodCode, $group, $entityVId ) ];
		}

		public function getResultByKey( $key ) {
			return $this->results[ $key ];
		}

		public function getTotalResult( $counterId, $periodType, $periodCode, $group = 0 ) {
			return $this->totals[ $counterId.'-'.$periodType.'-'.$periodCode.'-'.$group ];
		}

		public function getTotalResultByKey( $key ) {
			return $this->totals[ $key ];
		}
	}

	class TwatchDbrResultSession {
		public $id;
		public $ip;
		public $sCookie;
		public $pCookie;
		public $visitorTypeId;
		public $firstTs;
		public $more;

		public $requests = array();

		public function TwatchDbrResultSession( $id, $ip, $sCookie, $pCookie, $visitorTypeId, $firstTs ) {
			$this->id = $id;
			$this->ip = $ip;
			$this->sCookie = $sCookie;
			$this->pCookie = $pCookie;
			$this->visitorTypeId = $visitorTypeId;
			$this->firstTs = $firstTs;
			$this->more = 0;
		}
	}

	class TwatchDbrResultRequest {
		public $id;
		public $timestamp;
		public $pageId;

		public $data = array();

		public function TwatchDbrResultRequest( $id, $timestamp, $pageId ) {
			$this->id = $id;
			$this->timestamp = $timestamp;
			$this->pageId = $pageId;
		}
	}


	class TwatchDbrSession {
		private $db;
		private $sub;

		public $sessions = array();
		public $more;
		public $online;

		function TwatchDbrSession( $db, $sub ) {
			$this->db = $db;
			$this->sub = $sub;
		}

		function onlineVisitorsCount( $visitorTypeId, $minLastTs ) {
			$res = $this->db->query_sub( $this->sub, 'SELECT COUNT(*) FROM ', 's', 'WHERE vt = '.$visitorTypeId.' AND last > '.( $minLastTs ) );
			$r = $this->db->fetchRow( $res );
			return (int)$r[0];
		}

		function getLatestSessions( $start, $limit, $entityFilters = array(), $visitorTypeFilter = array(), $pageId = false, $pCookie = false, $forceExcVt = null, $onlineTolerance = 300, $nowTs = 0 ) {

			$this->more = false;
			$this->online = 0;

			if( $pageId == 'e' ) {
				$pageId = false;
			} elseif( $pageId == 'de' ) {
				return true;
			}

			$visTA = new ArdeAppender( ', ' );
			if( count( $visitorTypeFilter ) != 0 ) {
				if( count( $visitorTypeFilter ) != 1 || $visitorTypeFilter[0] != $forceExcVt ) {
					foreach( $visitorTypeFilter as $visitorTypeId ) {
						if( $visitorTypeId != $forceExcVt ) {
							$visTA->append( $visitorTypeId );
						}
					}
					$forceExcVt = null;
				}
			}

			if( count( $entityFilters ) != 0 || $pageId !== false ) {
				$q = '';
				$qw = new ArdeAppender(' AND ');
				$tables = new ArdeAppender( ', ' );
				$firstTables = $this->db->table( 's', $this->sub ).' AS t1';
				$j = 1;
				foreach( $entityFilters as $entityId => $entityVId ) {
					if( $entityVId === 'de' ) {
						$firstTables .= ' LEFT JOIN '.$this->db->table( 'rd', $this->sub ).' AS jt'.$j.' on( t1.id = jt'.$j.'.sid AND jt'.$j.'.eid = '.$entityId.' )';
						$qw->append( 'jt'.$j.'.p IS NULL' );
						++$j;
					}


				}
				$tables->append( $firstTables );
				$i = 2;
				foreach( $entityFilters as $entityId => $entityVId ) {
					if( $entityVId === 'de' ) continue;
					$qw->append( "t$i.eid = ".$entityId.($entityVId==='e'?'':" AND t$i.p = ".ardeU32ToStr( $entityVId ))." AND t$i.sid = t1.id" );
					$tables->append( $this->db->table( 'rd', $this->sub ).' AS t'.$i );
					$i++;
				}
				if( $pageId !== false ) {
					$qw->append( "t$i.pid = ".$pageId." AND t$i.sid = t1.id" );
					$tables->append( $this->db->table( 'sr', $this->sub ).' AS t'.$i );
					$i++;
				}


				if( $pCookie !== false ) {
					if( $pCookie === 'e' ) {
						$qw->append( 't1.pcookie <> 0' );
					} elseif( $pCookie === 'de' ) {
						$qw->append( 't1.pcookie = 0 ' );
					} else {
						$qw->append( 't1.pcookie = '.$pCookie );
					}
				}

				if( $visTA->c != 0 ) {
					$qw->append( "t1.vt IN( ".$visTA->s." )" );
				}

				if( $forceExcVt !== null ) {
					$qw->append( "t1.vt <> ".$forceExcVt );
				}
				$res = $this->db->query("SELECT t1.id, t1.ip, t1.scookie, t1.pcookie, t1.vt, t1.first FROM ".$tables->s.
										" WHERE ".$qw->s." GROUP BY t1.id ORDER BY t1.first DESC ".
										"LIMIT ".( $limit + 1 )." OFFSET $start" );

				$resOnline = $this->db->query("SELECT COUNT( DISTINCT t1.id ) FROM ".$tables->s." WHERE ".$qw->s." AND t1.last > ".($nowTs - $onlineTolerance) );
			} else {
				$where = new ArdeAppender(' AND ');
				if( $pCookie !== false ) {
					if( $pCookie === 'e' ) {
						$where->append( "pcookie <> 0" );
					} elseif( $pCookie === 'de' ) {
						$where->append( "pcookie = 0" );
					} else {
						$where->append( "pcookie = $pCookie" );
					}
				}
				if( $visTA->c != 0 ) {
					$where->append( "vt IN( ".$visTA->s." )" );
				}
				if( $forceExcVt !== null ) {
					$where->append( "vt <> ".$forceExcVt );
				}
				$res = $this->db->query_sub( $this->sub, "SELECT id, ip, scookie, pcookie, vt, first FROM", 's',
										( $where->c ? 'WHERE '.$where->s.' ' : '' )."ORDER BY first DESC ".
										"LIMIT ".( $limit + 1 )." OFFSET ".$start );
				$resOnline = $this->db->query_sub( $this->sub, "SELECT COUNT( DISTINCT id ) FROM", 's',
										'WHERE '.( $where->c ? $where->s.' AND ' : '' )." last > ".($nowTs - $onlineTolerance) );
			}

			$r = $this->db->fetchRow( $resOnline );
			$this->online = (int)$r[0];

			
			$i = 0;


			while( $r = mysql_fetch_row( $res ) ) {
				$i++;
				if( $i > $limit ) {
					$this->more = true;
					break;
				}
				$this->sessions[ (int)$r[0] ] = new TwatchDbrResultSession( (int)$r[0], ardeStrToU32( $r[1] ), ardeStrToU32( $r[2] ), ardeStrToU32( $r[3] ), (int)$r[4], (int)$r[5] );
				
			}

			return true;
		}
		
		function lookupData( $sessionId, $eids ) {
			if( !count( $eids ) ) return array();
			$qs = new ArdeAppender( ' UNION ALL ' );
			foreach( $eids as $eid => $direction ) {
				$q =  'SELECT '.$eid.', p FROM '.$this->db->table( 'sr', $this->sub ).' AS r, '.$this->db->table( 'rd', $this->sub ).' AS rd';
				$q .= ' WHERE r.sid = '.$sessionId.' AND r.id = rd.rid AND rd.eid = '.$eid.' ORDER BY r.time ';
				$q .= ($direction?'ASC':'DESC').', r.id '.($direction?'ASC':'DESC').' LIMIT 1';
				$qs->append( '( '.$q.' )' );
			}
			
			$res = $this->db->query( $qs->s );
			$o = array();
			while( $r = $this->db->fetchRow( $res ) ) {
				$o[ (int)$r[0] ] = ardeStrToU32( $r[1] );
			}
			return $o;
		}
		
		function getSession( $sessionId, $forceExcVt = null ) {

			$res = $this->db->query_sub( $this->sub, "SELECT id, ip, scookie, pcookie, vt, first FROM", 's', 'WHERE id = '.$sessionId.($forceExcVt===null?'':' AND vt <> '.$forceExcVt ) );

			if( $r = mysql_fetch_row( $res ) ) {
				$this->sessions[ (int)$r[0] ] = new TwatchDbrResultSession( (int)$r[0], ardeStrToU32( $r[1] ), ardeStrToU32( $r[2] ), ardeStrToU32( $r[3] ), (int)$r[4], (int)$r[5] );
			}

			return true;
		}
		
		function getRequests( $sessionId, $offset = 0, $count = 20 ) {
			if( isset( $this->sessions[ $sessionId ] ) ) {
				
				$res = $this->db->query_sub( $this->sub, "SELECT id, time, pid FROM", 'sr',
						'WHERE sid = '.$sessionId.' ORDER BY time DESC, id DESC'.($count === null?'':' LIMIT '.$offset.', '.$count ) );
				
				$i = 0;
				$rids = new ArdeAppender( ' UNION ' );
				while( $r = mysql_fetch_row( $res ) ) {
					$rid = (int)$r[0];
					$this->sessions[ $sessionId ]->requests[ $rid ] = new TwatchDbrResultRequest( (int)$r[0], (int)$r[1], ardeStrToU32( $r[2] ) );
					$rids->append( '( SELECT '.$rid.( $i ? '' : ' AS id' ).' )' );
					++$i;
				}
				
				$this->sessions[ $sessionId ]->requests = array_reverse( $this->sessions[ $sessionId ]->requests, true );

				$res = $this->db->query_sub( $this->sub, "SELECT count(*) FROM", 'sr', 'WHERE sid = '.$sessionId );
				$this->sessions[ $sessionId ]->more = $this->db->fetchInt( $res ) - $offset - $count;
				if( $this->sessions[ $sessionId ]->more < 0 ) $this->sessions[ $sessionId ]->more = 0; 
				
				if( $rids->c ) {
					$res = $this->db->query( "SELECT rd.rid, rd.eid, rd.p FROM "
						.'( '.$rids->s.' ) AS ids,'.$this->db->table( 'rd', $this->sub ).' AS rd'
						.' WHERE ids.id = rd.rid' );
	
					while( $r = mysql_fetch_row( $res ) ) {
						$this->sessions[ $sessionId ]->requests[ (int)$r[0] ]->data[ (int)$r[1] ] = ardeStrToU32( $r[2] );
					}
				}
			}
		}
		
		

	}

	class TwatchDbrDictResult {
		public $str;
		public $cache1;
		public $cache2;
		public $extra;
		public function TwatchDbrDictResult( $str, $cache1, $cache2, $extra ) {
			$this->str = $str;
			$this->cache1 = $cache1;
			$this->cache2 = $cache2;
			$this->extra = $extra;
		}

	}

	class TwatchDbrDict {
		var $db;
		var $requests = array();
		var $results = array();

		function TwatchDbrDict( $db ) {
			$this->db = $db;
		}

		function get( $dictId, $id ) {
			$this->requests[ $dictId.'-'.$id ] = true;
		}

		public static function getEntries( ArdeDb $db, $dictId, $offset, $count, $beginWith = null, $idsStartFrom = 1, $getStrsToo = false, $cache = array() ) {
			if( $beginWith != null ) {
				$beginWith = " AND str LIKE ".$db->string( $beginWith.'%' );
			} else {
				$beginWith = '';
			}
			if( $idsStartFrom > 1 ) {
				$ignore = ' AND id <> '.$idsStartFrom;
			} else {
				$ignore = '';
			}
			$cacheWhere = '';
			foreach( $cache as $cacheNo => $value ) {
				$cacheWhere .= ' AND c'.$cacheNo.' = '.$value;
			}

			$res = $db->query( 'SELECT id'.($getStrsToo?', '.$db->stringResult( 'str' ):'').' FROM', 'd', 'WHERE did = '.$dictId.$beginWith.$ignore.$cacheWhere.' ORDER BY str LIMIT '.$offset.', '.$count );
			$o = array();
			if( $getStrsToo ) {
				while( $r = $db->fetchRow( $res ) ) {
					$o[ ardeStrToU32( $r[0] ) ] = $r[1];
				}
			} else {
				while( $r = $db->fetchRow( $res ) ) {
					$o[] = ardeStrToU32( $r[0] );
				}
			}
			return $o;
		}


		public static function getIdsCount( ArdeDb $db, $dictId, $beginWith = null, $ignore = -1 ) {
			if( $beginWith != null ) {
				$beginWith = " AND str LIKE ".$db->string( $beginWith.'%' );
			} else {
				$beginWith = '';
			}
			if( $ignore > 0 ) {
				$ignore = ' AND id <> '.$ignore;
			} else {
				$ignore = '';
			}
			$res = $db->query( 'SELECT COUNT(*) FROM', 'd', 'WHERE did = '.$dictId.$beginWith.$ignore );
			if( !( $r = $db->fetchRow( $res ) ) ) throw new TwatchException( 'bad result from db' );
			return (int)$r[0];
		}

		function rollGet() {

			$dids = new ArdeAppender( ', ' );
			$ids = new ArdeAppender( ', ' );
			$i = 0;
			foreach( $this->requests as $k => $v ) {
				if( !isset( $this->results[ $k ] ) ) {
					list( $dictId, $id ) = explode( '-', $k );
					$dids->append( $dictId );
					$ids->append( $id );
					++$i;
				}
			}

			if( $i != 0 ) {
				$res = $this->db->query( "SELECT t2.did, t2.id, ".$this->db->stringResult( 't2.str' ).", t2.c1, t2.c2, ".$this->db->stringResult( 't2.ext' )." FROM", array( 'du', 'd' ),
					"WHERE tu.i <= ".$ids->c." AND t2.did = ELT( tu.i, ".$dids->s." ) AND t2.id = ELT( tu.i, ".$ids->s." )" );


				while( $r = $this->db->fetchRow( $res ) ) {
					$this->results[ $r[0].'-'.$r[1] ] = new TwatchDbrDictResult( $r[2], ardeStrToU32($r[3]), ardeStrToU32($r[4]), $r[5] );
				}
			}

			$this->requests = array();
		}


		function getResult( $dictId, $id ) {
			if( isset( $this->results[ $dictId.'-'.$id ] ) ) {
				return $this->results[ $dictId.'-'.$id ];
			}
			return false;
		}
	}
?>