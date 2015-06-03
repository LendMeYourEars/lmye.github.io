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

	class TwatchDbVisitorTypeId {
		public $entityId;
		public $entityVId;

		public function __construct( $entityId, $entityVId ) {
			$this->entityId = $entityId;
			$this->entityVId = $entityVId;
		}
	}

	class TwatchDbVisitorTypes {
		var $db;

		function __construct( ArdeDb $db ) {
			$this->db = $db;
		}

		function get( $entityValues ) {
			$entityIds = new ArdeAppender( ', ' );
			$entityVIds = new ArdeAppender( ', ' );
			foreach( $entityValues as $entityId => $entityVId ) {
				$entityIds->append( ardeU32ToStr( $entityId ) );
				$entityVIds->append( ardeU32ToStr( $entityVId ) );
			}
			$res = $this->db->query('SELECT vt FROM', array( 'du', 'vti' ), "WHERE tu.i <= {$entityIds->c} and t2.eid = ELT( tu.i, ".$entityIds->s." ) and t2.id = ELT( tu.i, ".$entityVIds->s." ) limit 1" );
			if( !$r = $this->db->fetchRow( $res ) ) return null;
			else return ardeStrToU32( $r[0] );
		}

		function set( $entityId, $entityVId, $visitorTypeId ) {
			return $this->db->query( 'INSERT INTO', 'vti', '( eid, id, vt ) VALUES ( '.$entityId.', '.ardeU32ToStr( $entityVId ).', '.$visitorTypeId.' ) ON DUPLICATE KEY UPDATE vt = VALUES( vt )' );
		}

		function removeVisitorType( $visitorTypeId ) {
			return $this->db->query( 'DELETE FROM', 'vti', 'WHERE vt = '.$visitorTypeId );
		}

		function getIdentifiers( $visitorTypeId ) {
			$res = $this->db->query( 'SELECT eid, id FROM', 'vti', 'WHERE vt = '.$visitorTypeId );
			$o = array();
			while( $r = $this->db->fetchRow( $res ) ) {
				$o[] = new TwatchDbVisitorTypeId( (int)$r[0], ardeStrToU32( $r[1] ) );
			}
			return $o;

		}

		public function getEntityVIdReference( $entityId ) {
			return new ArdeDbReference( '', 'vti', 'id', array( 'eid', ' = '.$entityId ) );
		}

		function removeIdentifier( $entityId, $entityVId ) {
			$this->db->query( 'DELETE FROM', 'vti', 'WHERE eid = '.$entityId.' AND id = '.$entityVId );
		}

		function install( $overwrite = false ) {
			$this->db->createTable( '', 'vti', '( eid INT UNSIGNED NOT NULL, id INT UNSIGNED NOT NULL, vt TINYINT UNSIGNED NOT NULL, UNIQUE( eid, id ) )', $overwrite );
		}

		function uninstall() {
			$this->db->dropTableIfExists( '', 'vti' );
		}
	}


	class TwatchDbRequestData {
		var $db;
		var $sub;
		var $toadd;

		function __construct( ArdeDb $db, $sub ) {
			$this->db = $db;
			$this->sub = $sub;
			$this->toadd = array();
		}

		function get_latest( $sid ) {
			$sid = ardeU32ToStr( $sid );
			$res = $this->db->query_sub( $this->sub,
					'SELECT t1.eid, t2.p FROM '
					.'(SELECT eid, MAX(rid) AS rid FROM '.$this->db->table( 'rd', $this->sub )." WHERE sid=$sid GROUP BY eid) AS t1"
					.', '.$this->db->table( 'rd', $this->sub ).' AS t2 WHERE t1.rid=t2.rid AND t1.eid=t2.eid' );
			$o = array();
			while( $r = $this->db->fetchRow( $res ) ) {
				$o[ardeStrToU32($r[0])] = ardeStrToU32($r[1]);
			}

			return $o;
		}

		function add( $rid, $sid, $entity, $entity_value ) {
			$this->toadd[] = array( $rid, $sid, $entity, $entity_value );
		}

		function delete( $eid ) {
			$this->db->query_sub( $this->sub, 'DELETE FROM', 'rd', 'WHERE eid = '.$eid );
		}

		function roll_adds() {
			if( !count( $this->toadd )) return;
			$q = '';
			$i = 0;
			foreach( $this->toadd as $ta ) {
				$q .= ($i?',':'')."(".ardeU32ToStr($ta[0]).','.ardeU32ToStr($ta[1]).','.ardeU32ToStr($ta[2]).','.ardeU32ToStr($ta[3]).')';
				$i++;
			}
			$this->db->query_sub( $this->sub, 'REPLACE INTO', 'rd', '(rid,sid,eid,p) VALUES '.$q );

		}
	}

	class TwatchDbHistory {
		var $db;
		var $toinc;
		var $sub;

		function __construct( ArdeDb $db, $sub ) {
			$this->db=$db;
			$this->toinc=array();
			$this->sub=$sub;
		}

		function increment( $cid, $p1, $p2, $periodType, $periodCode, $list = false, $withBuffer = false ) {
			$this->toinc[] = array( 'cid' => $cid, 'p1' => $p1, 'p2' => $p2, 'period_type' => $periodType, 'period_code' => $periodCode );
			if( $list ) {
				$this->toinc[] = array( 'cid' => $cid, 'p1' => $p1, 'p2' => 0, 'period_type' => $periodType, 'period_code' => $periodCode );
			}
			if( $withBuffer ) {
				$this->toinc[] = array( 'cid' => $cid, 'p1' => $p1, 'p2' => $p2, 'period_type' => $periodType, 'period_code' => '$'.$periodCode );
			}
		}

		function roll_increments() {
			if( !count( $this->toinc )) return;
			$q = '';
			$i = 0;

			foreach( $this->toinc as $toInc ) {
				$q .= ($i?', ':'').'( '.$toInc[ 'period_type' ].", ".$this->db->string( $toInc[ 'period_code' ], 'latin1' ).", ".$toInc[ 'cid' ].', '.
					($toInc[ 'p1' ]?ardeU32ToStr( $toInc[ 'p1' ] ):'0').', '.($toInc[ 'p2' ]?ardeU32ToStr( $toInc[ 'p2' ] ):'0').', 1 )';
				++$i;
			}
			$this->db->query_sub( $this->sub, 'INSERT INTO', 'h', "VALUES $q ON DUPLICATE KEY UPDATE c=c+1" );


			$this->history_incs = array();
			return true;
		}


		public function replace( $counterId, $periodType, $periodCode, $p1, $p2, $count, $mergeRatio = 0 ) {

			if( $mergeRatio != 0 ) {
				$oldValue = $this->db->querySubInt( $this->sub, 'SELECT c FROM', 'h', 'WHERE cid = '.$counterId.' AND dtt = '.$periodType." AND dt ='".$periodCode."' AND p1 = ".ardeU32ToStr( $p1 ).' AND p2 = '.ardeU32ToStr( $p2 ) );
				if( $oldValue === null ) $oldValue = 0;
				$count += (int)round( $mergeRatio * $oldValue );
			}

			if( $count == 0 ) {
				$this->db->query_sub( $this->sub, 'DELETE FROM', 'h', 'WHERE cid = '.$counterId.' AND dtt = '.$periodType." AND dt ='".$periodCode."' AND p1 = ".ardeU32ToStr( $p1 ).' AND p2 = '.ardeU32ToStr( $p2 ) );
			} else {
				$this->db->query_sub( $this->sub, 'REPLACE INTO', 'h', "VALUES ( ".$periodType.", '".$periodCode."', ".$counterId.", ".ardeU32ToStr( $p1 ).", ".ardeU32ToStr( $p2 ).", ".$count." )" );
			}

		}

		public function multiplyValues( $factor, $counterId, $periodType, $periodCode, $p1, $excludeP2s ) {
			$excP2 = new ArdeAppender( ', ' );
			foreach( $excludeP2s as $id ) $excP2->append( ardeU32ToStr( $id ) );
			$this->db->query_sub( $this->sub, 'UPDATE', 'h', ' SET c = ROUND( c * '.$factor.' ) WHERE cid = '.$counterId.' AND dtt = '.$periodType." AND dt = '".$periodCode."' AND p1 = ".ardeU32ToStr( $p1 ).($excP2->c?' AND p2 NOT IN( '.$excP2->s.' )':'') );
		}

		public function recomputeTotal( $counterId, $periodType, $periodCode, $groupId = null ) {
			$q =  'REPLACE INTO '.$this->db->tableName( 'h', $this->sub );
			$q .= ' SELECT '.$periodType.", '".$periodCode."', ".$counterId.", p1, 0, SUM( c ) FROM ".$this->db->tableName( 'h', $this->sub );
			$q .= ' WHERE cid = '.$counterId.' AND dtt = '.$periodType." AND dt ='".$periodCode."'".($groupId===null?'':' AND p1 = '.ardeU32ToStr( $groupId ))." AND p2 <> 0 GROUP BY p1";
			$this->db->query( $q );
			$this->db->query_sub( $this->sub, 'DELETE FROM', 'h', 'WHERE cid = '.$counterId.' AND dtt = '.$periodType." AND dt = '".$periodCode."'".($groupId===null?'':' AND p1 = '.ardeU32ToStr( $groupId ))." AND p2 = 0 AND c = 0" );
		}

		public function recomputeAllTotals( $counterId ) {
			$q =  'REPLACE INTO '.$this->db->tableName( 'h', $this->sub );
			$q .= ' SELECT dtt, dt, '.$counterId.", p1, 0, SUM( c ) FROM ".$this->db->tableName( 'h', $this->sub );
			$q .= ' WHERE cid = '.$counterId.' AND p2 <> 0 GROUP BY dtt, dt, p1';
			$this->db->query( $q );

			$this->db->query_sub( $this->sub, 'DELETE FROM', 'h', 'WHERE cid = '.$counterId.' AND p2 = 0 AND c = 0' );
		}



		public function getCounts( $counterId, $periodType, $periodCode, $groupId = null ) {
			$res = $this->db->query_sub( $this->sub, 'SELECT p1, p2, c FROM', 'h', 'WHERE cid = '.$counterId.' AND dtt = '.$periodType." AND dt = '".$periodCode."'".($groupId===null?'':' AND p1 = '.ardeU32ToStr( $groupId )) );
			$o = array();
			while( $r = $this->db->fetchRow( $res ) ) {
				$o[ ardeStrToU32( $r[0] ).'-'.ardeStrToU32( $r[1] ) ] = (int)$r[2];
			}
			return $o;
		}

		public function clearPeriod( $counterId, $periodType, $periodCode, $groupId = null ) {
			$this->db->query_sub( $this->sub, 'DELETE FROM', 'h', "WHERE cid = ".$counterId." AND dtt = ".$periodType." AND dt = '".$periodCode."'".($groupId===null?'':' AND p1 = '.ardeU32ToStr( $groupId )) );
		}

		public static function getEntityReference( $counterId, $sub ) {
			return new ArdeDbReference( $sub, 'h', 'p2', array( 'cid', ' = '.$counterId ) );
		}

		public static function getGroupReference( $counterId, $sub ) {
			return new ArdeDbReference( $sub, 'h', 'p1', array( 'cid', ' = '.$counterId ) );
		}

		public function deleteCounterData( $counterId, $periodType = null ) {
			if( $periodType === null ) {
				$ptWhere = '';
			} else {
				$ptWhere = ' AND dtt='.$periodType;
			}
			$this->db->query_sub( $this->sub, 'DELETE FROM', 'h', 'WHERE cid='.$counterId.$ptWhere );
		}

		public function deleteOldData( $counterId, $periodType, $periodCode ) {
			$this->db->query_sub( $this->sub, 'DELETE FROM', 'h', "WHERE dtt=".$periodType." AND dt<='".$periodCode."' AND cid=".$counterId );
		}

		public function deleteData( $counterId, $groupId, $entityVId, $periodType ) {
			$where = new ArdeAppender( ' AND ' );
			if( $counterId ) {
				$where->append( 'cid = '.$counterId );
			}
			if( $groupId !== null ) {
				$where->append( 'p1 = '.$groupId );
			}
			if( $entityVId !== null ) {
				$where->append( 'p2 = '.$entityVId );
			}
			if( $periodType !== null ) {
				$where->append( 'dtt = '.$periodType );
			}
			if( !$where->c ) {
				$this->db->query_sub( $this->sub, 'TRUNCATE ', 'h' );
			} else {
				$this->db->query_sub( $this->sub, 'DELETE FROM', 'h', "WHERE ".$where->s );
			}
		}

		public function trimData( $counterId, $periodType, $periodCode, $count ) {
			++$count;
			$this->db->query( 'SET @tmi=-1, @tmcp1=-1' );

			$sq = 'SELECT @tmi := IF( IFNULL( @tmcp1, -1 ) <> p1, 0, @tmi + 1 ) AS i, dtt, dt, cid, @tmcp1 := p1 as p1, p2, c FROM '.$this->db->table( 'h', $this->sub )." WHERE dtt=".$periodType." AND dt='".$periodCode."' AND cid=".$counterId." ORDER BY p1, c DESC";
			$this->db->query( 'DELETE '.$this->db->table( 'h', $this->sub ).' FROM '.$this->db->table( 'h', $this->sub ).', ('.$sq.") AS t2 WHERE t2.i >= ".$count." AND ".$this->db->table( 'h', $this->sub ).".dtt = t2.dtt AND ".$this->db->table( 'h', $this->sub ).".dt = t2.dt AND ".$this->db->table( 'h', $this->sub ).".cid = t2.cid AND ".$this->db->table( 'h', $this->sub ).".p1 = t2.p1 AND ".$this->db->table( 'h', $this->sub ).".p2 = t2.p2" );
		}

		public function activeTrimData( $counterId, $periodType, $rows ) {
			$this->db->query( "SET @cdt = '', @cpos = 0" );
			$subSelect = "SELECT SUBSTR( dt, 2 ) as sdt, p1, p2 + IF( @cdt <> CONCAT( dt, '_', p1 ), @cpos := 0 + IF( @cdt := CONCAT( dt, '_', p1 ), 0, 0 ), IF( @cpos := @cpos + 1, 0, 0 ) ) as p2, c"
				.", @cpos AS pos "
				."FROM ".$this->db->tableName( 'h', $this->sub )." WHERE cid = ".$counterId." AND dtt = ".$periodType." AND dt LIKE '$%' ORDER BY dt, p1, c DESC";
			$this->db->query( 'UPDATE '.$this->db->tableName( 'h', $this->sub ).' AS ct, ( '.$subSelect.' ) AS sub '
				."SET ct.c = IF( ct.c - sub.c > 0, ct.c - sub.c, 0  ) "
				.'WHERE ct.cid = '.$counterId.' AND ct.dtt = '.$periodType.' '
				."AND sub.pos >= ".$rows." AND ct.dt = sub.sdt AND ct.p1 = sub.p1 AND ct.p2 = sub.p2" );
			$this->deleteBufferCounters( $counterId, $periodType );
			$this->db->query_sub( $this->sub, 'DELETE FROM', 'h', 'WHERE cid = '.$counterId.' AND dtt = '.$periodType." AND c = 0" );
		}

		public function deleteBufferCounters( $counterId, $periodType ) {
			$this->db->query_sub( $this->sub, 'DELETE FROM', 'h', 'WHERE cid = '.$counterId.' AND dtt = '.$periodType." AND dt LIKE '$%'" );
		}

		public function trimOldData( $counterId, $periodType, $periodCode, $count ) {
			$res = $this->db->query_sub( $this->sub, 'SELECT DISTINCT dt FROM', 'h', 'WHERE cid='.$counterId.' AND dtt='.$periodType." AND dt<='".$periodCode."'" );
			while( $periodCode = $this->db->fetchRow( $res ) ) {
				$this->trimData( $counterId, $periodType, $periodCode, $count );
			}
		}

		public static function addDbAccessUnitInfo( ArdeDb $db, ArdeDbAccessUnitInfo $unit, $name, $sub ) {
			$unit->subs[] = new ArdeDbAccessUnitInfo( $name, array( $db->tableName( 'h', $sub, false ) ) );
			$unit->tableNames[] = $db->tableName( 'h', $sub, false );
		}

		public function getSizes() {
			$res = $this->db->query_sub( $this->sub, 'SELECT cid, COUNT(*) FROM', 'h', 'GROUP BY cid' );
			$o = array();
			while( $r = $this->db->fetchRow( $res ) ) {
				$o[ (int)$r[0] ] = (int)$r[1];
			}
			return $o;
		}

		public function getTotalSize() {
			$res = $this->db->getTableInfo( 'h', $this->sub );
			return $res->indexSize + $res->dataSize;
		}

		public static function install( ArdeDb $db, $sub, $overwrite = false ) {
			$db->createTable( $sub, 'h', '( dtt TINYINT UNSIGNED NOT NULL, dt CHAR(7) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL, cid INT UNSIGNED NOT NULL, p1 INT UNSIGNED NOT NULL, p2 INT UNSIGNED NOT NULL, c INT UNSIGNED NOT NULL, UNIQUE inc( cid, p2, p1, dtt, dt ), INDEX report( cid, p1, dtt, dt, c ) )', $overwrite );
		}

		public static function uninstall( ArdeDb $db, $sub ) {
			$db->dropTableIfExists( $sub, 'h' );
		}


	}


	class TwatchDbDictResult {
		public $id;
		public $cache1;
		public $cache2;
		public $extra;
		public $timestamp;

		function __construct( $id, $cache1, $cache2, $extra, $timestamp ) {
			$this->id = $id;
			$this->cache1 = $cache1;
			$this->cache2 = $cache2;
			$this->extra = $extra;
			$this->timestamp = $timestamp;
		}

	}


	class TwatchDbDict {

		const STRING_SIZE = 254;

		var $db;
		var $data = array();
		var $results = array();
		var $insertIds = array();
		var $toput = array();
		var $toget = array();
		var $cachePut = array();

		function __construct( ArdeDb $db ) {
			$this->db = $db;
		}

		public static function getDbAccessUnitInfo( ArdeDb $db ) {
			return new ArdeDbAccessUnitInfo( 'Dictionaries', array( $db->tableName( 'd', null, false ) ) );
		}

		function put( $key, $did, $str, $cache = array(), $ext = '', $id = 0, $cts = null ) {
			if( $str == '' ) $str = "\t";
			if( function_exists( 'mb_substr' ) ) {
				$str = mb_substr( $str, 0, self::STRING_SIZE, "UTF-8" );
			} elseif( function_exists( 'iconv_substr' ) ) {
				$str = iconv_substr( $str, 0, self::STRING_SIZE, "UTF-8" );
			} else {
				$str = substr( $str, 0, self::STRING_SIZE );
			}
			$this->toput[ $key ] = array( 'did' => $did,'str' => $str,'cache' => $cache,'ext' => $ext,'id' => $id, 'cts' => $cts );
		}



		function putCache( $key, $pos, $value ) {
			$this->cachePut[] = array( 'key' => $key, 'pos' => $pos, 'value' => $value );
		}

		function setExtra( $dictId, $id, $extra ) {
			$this->db->query( 'UPDATE', 'd', 'SET ext = '.$this->db->string( $extra ).' WHERE did='.$dictId.' AND id='.$id );
		}

		function updateCache( $did, $id, $pos, $value ) {
			$this->db->query( 'UPDATE', 'd', 'SET c'.$pos.' = '.$value.', cts = UNIX_TIMESTAMP() WHERE did = '.$did.' AND id = '.$id );
		}

		function putInsertIdIntoCache( $key, $pos, $sourceKey ) {
			$this->cachePut[] = array( 'key' => $key, 'pos' => $pos, 'value' => 'd'.$sourceKey );
		}

		function rollPut( $order = array() ) {

			if( !count( $this->toput ) ) return true;
			$q = new ArdeAppender( ',' );


			foreach( $this->cachePut as $cp ) {
				if( !isset( $this->toput[ $cp[ 'key' ] ] ) ) continue;
				$this->toput[ $cp[ 'key' ] ][ 'cache' ][ $cp[ 'pos' ] - 1 ] = $cp[ 'value' ];
			}

			$newToput = array();
			foreach( $order as $key ) {
				if( isset( $this->toput[ $key ] ) ) {
					$newToput[ $key ] = $this->toput[ $key ];
					unset( $this->toput[ $key ] );
				}
			}
			foreach( $this->toput as $key => $toput ) {
				$newToput[ $key ] = $toput;
			}
			$this->toput = $newToput;

			$conflictingKeys = array();
			foreach( $this->toput as $key => &$v ) {
				for( $i=0; $i < 2; $i++ ) {
					if( !isset( $v['cache'][$i] ) ) $v['cache'][$i] = 0;
					if( is_string($v['cache'][$i]) && $v['cache'][$i][0]=='d' ) {
						$srcKey = substr( $v['cache'][$i], 1 );
						$v['cache'][$i] = $this->insertIds[ $srcKey ];
					} else {
						$v['cache'][$i] = ardeU32ToStr( $v['cache'][$i] );
					}
				}

				$q = $this->db->makeQuery( 'INSERT IGNORE INTO', 'd','(did,id,str,c1,c2,ext,cts) VALUES( '.ardeU32ToStr( $v['did'] ).", ".($v['id']?ardeU32ToStr($v['id']):'NULL').", ".$this->db->string( $v['str'] ).", ".$v['cache'][0].",".$v['cache'][1].", ".$this->db->string( $v['ext'] ).", ".($v['cts']===null?'UNIX_TIMESTAMP()':$v['cts'] )." )" );
				$this->db->query( $q );

				if( $v[ 'id' ] ) continue;

				$this->insertIds[ $key ] = $this->db->lastInsertId();

				if( !$this->insertIds[ $key ] ) {
					$this->get( $key, $v['did'], $v['str'] );
					$conflictingKeys[ $key ] = $q;

				}

			}

			if( count( $conflictingKeys ) ) {
				$this->rollGet();
				foreach( $conflictingKeys as $key => $query ) {
					if( !isset( $this->results[ $key ] ) ) {
						ArdeException::reportError( new ArdeWarning( 'lastInsertId was zero for key '.$key.' and I couldn\'t find any conflicting entry in database.', 0, null, ' query was: '.$query ) );
						$this->insertIds[ $key ] = 0;
					} else {
						$this->insertIds[ $key ] = $this->results[ $key ]->id;
					}
				}
			}


			$this->toput = array();

			return true;
		}

		public static function prepString( $string ) {
			if( $string == '' ) $string = "\t";

			return substr( $string, 0, self::STRING_SIZE );
		}

		function get( $key, $did, $str ) {
			$str = self::prepString( $str );
			$this->toget[]=array( $did, $str, $key );
		}

		function rollGet() {
			if(!count($this->toget)) return true;

			$qk=new ArdeAppender( ', ' );
			$qdid=new ArdeAppender( ', ' );
			$qstr=new ArdeAppender( ', ' );

			foreach($this->toget as $v) {
				$qdid->append( ardeU32ToStr( $v[0] ) );
				$qstr->append( $this->db->stringCol( $v[1] ) );
				$qk->append( $v[2] );
			}

			$res=$this->db->query("select elt( tu.i, {$qk->s} ), t2.id, t2.c1, t2.c2, t2.ext, t2.cts from",array( 'du', 'd' ),"where  tu.i <= {$qdid->c} and t2.did=elt( tu.i, {$qdid->s} ) and t2.str = elt( tu.i, {$qstr->s} )");

			while($r=mysql_fetch_row($res)) {
				$this->results[ (int)$r[0] ] = new TwatchDbDictResult( ardeStrToU32($r[1]), ardeStrToU32($r[2]), ardeStrToU32($r[3]), $r[4], (int)$r[5] );
			}
			$this->toget=array();
			return true;
		}

		public static function getCacheRef( $dictId, $pos ) {
			return new ArdeDbReference( '', 'd', 'c'.$pos, array( 'did', ' = '.$dictId ) );
		}

		public static function install( ArdeDb $db, $overwrite = false ) {
			global $twatch;
			$db->createTable( '', 'd', '( did INT UNSIGNED NOT NULL, id INT UNSIGNED NOT NULL AUTO_INCREMENT, str VARCHAR('.(self::STRING_SIZE+1).') CHARACTER SET '.$db->getCharset().' COLLATE '.$db->getCollation().' NOT NULL DEFAULT \'\', c1 INT UNSIGNED NOT NULL DEFAULT 0, c2 INT UNSIGNED NOT NULL DEFAULT 0, ext VARCHAR(255) CHARACTER SET '.$db->getCharset().' COLLATE '.$db->getCollation().' NOT NULL DEFAULT \'\', cts INT UNSIGNED NOT NULL DEFAULT 0, UNIQUE( did, str ), UNIQUE( did, id ), INDEX( c1, did ), INDEX( c2, did ) )', $overwrite );
		}

		public static function uninstall( ArdeDb $db ) {
			$db->dropTableIfExists( '', 'd' );
		}




		function setIdsStartFrom( $dictId, $idsStartFrom ) {
			if( $idsStartFrom <= 1 ) return;
			$this->db->query( 'INSERT INTO', 'd', "( did, id ) VALUES ( ".$dictId.", ".$idsStartFrom." )" );
		}

		function removeAllDictEntries( $dictId ) {
			$this->db->query( 'DELETE FROM', 'd', "WHERE did=".$dictId );
		}

		public static function getBoundaryIds( ArdeDb $db, $groupSize, $dictId, $idsStartFrom = 1 ) {

			if( $idsStartFrom > 1 ) {
				$ignore = ' AND id <> '.$idsStartFrom;
			} else {
				$ignore = '';
			}

			$db->query( 'SET @i=-1' );

			$res = $db->query( 'SELECT t1.id from (SELECT @i:=@i+1 as i,id FROM', 'd', 'WHERE did='.$dictId.$ignore.' ORDER BY id) AS t1 WHERE MOD(t1.i,'.$groupSize.')=0 OR MOD(t1.i,'.$groupSize.')='.($groupSize-1) );
			$boundaryIds = array();
			while( $r = $db->fetchRow( $res ) ) {
				$boundaryIds[] = ardeStrToU32( $r[0] );
			}
			if( count( $boundaryIds ) % 2 ) {
				$res = $db->query( 'SELECT MAX( id ) FROM', 'd', 'WHERE did='.$dictId.$ignore );
				$r = $db->fetchRow( $res );
				$boundaryIds[] = ardeStrToU32( $r[0] );
			}

			return $boundaryIds;
		}

		public static function getTotalSize( ArdeDb $db ) {
			$res = $db->getTableInfo( 'd' );
			return $res->indexSize + $res->dataSize;
		}

		public static function getSizes( ArdeDb $db ) {
			$res = $db->query( 'SELECT did, COUNT(*) FROM', 'd', 'GROUP BY did' );
			$o = array();
			while( $r = $db->fetchRow( $res ) ) {
				if( $r[0] == 0 ) continue;
				$o[ (int)$r[0] ] = (int)$r[1];
			}
			return $o;
		}


		public static function cleanupDict( ArdeDb $db, $dictId, $refs, $keepAnyway = 0, $idsStartFrom = 1, $startId = null, $endId = null ) {
			
			$dictt = $db->table( 'd' );
			
			if( !count( $refs ) ) return self::deleteDictEntries( $db, $dictId, $startId, $endId );
			$wheres = $dictt.'.did = '.$dictId;
			if( $startId !== null ) $wheres .= ' AND '.$dictt.'.id >= '.ardeU32ToStr( $startId );
			if( $endId !== null ) $wheres .= ' AND '.$dictt.'.id <= '.ardeU32ToStr( $endId );
			if( $idsStartFrom > 1 ) $wheres .= ' AND '.$dictt.'.id <> '.$idsStartFrom;
			if( $keepAnyway > 0 ) $wheres .= ' AND UNIX_TIMESTAMP() - '.$dictt.'.cts > '.( $keepAnyway * 86400 );

			$joins = '';
			$i = 1;
			while( $ref = array_pop( $refs ) ) {

				$tableAlias = 't'.$i;
				$joins .= ' LEFT JOIN '.$ref->getTableName( $db ).' AS '.$tableAlias.' ON( '.self::getRefCondition( $db, $ref, $dictt, $tableAlias ).' )';
				$wheres .= ' AND '.$tableAlias.'.'.$ref->getColumnName().' IS NULL';
				if( $i > $db->getMaxJoins() - 10 ) break;
				++$i;
			}

			if( !count( $refs ) ) {
				$q = 'DELETE '.$dictt.' FROM '.$db->table( 'd' ).$joins.' WHERE '.$wheres;
				$db->query( $q );
			} else {
				$db->query( 'CREATE TEMPORARY TABLE '.$db->getDatabaseName().'.temp (ID INT UNSIGNED NOT NULL PRIMARY KEY) ENGINE MEMORY SELECT '.$dictt.'.id FROM '.$dictt.$joins.' WHERE '.$wheres );
				while( count( $refs ) ) {
					$wheres = '';
					$i = 1;
					while( $ref = array_pop( $refs ) ) {
						$tableAlias = 't'.$i;
						$wheres .= ($wheres?' OR ':'').'EXISTS( SELECT * FROM '.$ref->getTableName( $db ).' AS '.$tableAlias.' WHERE '.self::getRefCondition( $db, $ref, null, $tableAlias ).' LIMIT 1 )';
						if( $i > $db->getMaxJoins() - 10 ) break;
						++$i;
					}
					$db->query( 'DELETE FROM '.$db->getDatabaseName().'.temp WHERE '.$wheres );
				}
				$db->query( 'DELETE '.$dictt.' FROM '.$db->getDatabaseName().'.temp as temp, '.$dictt.' WHERE '.$dictt.'.did = '.$dictId.' AND temp.id = '.$dictt.'.id' );
				$db->query( 'DROP TABLE '.$db->getDatabaseName().'.temp' );
			}

		}

		private static function getRefCondition( ArdeDb $db, ArdeDbReference $ref, $idsTableAlias, $tableAlias ) {
			$extraCondition = $ref->getExtraCondition( $tableAlias );
			$extraCondition = $extraCondition ? ' AND '.$extraCondition : '';
			return $tableAlias.'.'.$ref->getColumnName().' = '.($idsTableAlias===null?'id':$idsTableAlias.'.id').$extraCondition;
		}

		public static function deleteDictEntries( ArdeDb $db, $dictId, $idsStartFrom = 1, $startId = null, $endId = null ) {
			$wheres = 'did = '.$dictId;
			if( $startId !== null ) $wheres .= ' AND id >= '.ardeU32ToStr( $startId );
			if( $endId !== null ) $wheres .= ' AND id <= '.ardeU32ToStr( $endId );
			if( $idsStartFrom > 1 ) $wheres .= ' AND id <> '.$idsStartFrom;
			$db->query( 'DELETE FROM', 'd', 'WHERE '.$wheres );
		}
	}

	class TwatchDbSession {
		var $db;
		var $session_space;
		var $sub;

		var $old_sid;
		var $old_sid_first=-1;
		var $old_sid_last=-1;
		var $this_sid_first=-1;
		var $out_of_session_space=false;
		var $req_not_there=false;
		var $last=-1;
		var $visitor_last=-1;
		function __construct( ArdeDb $db, $session_space, $sub ) {
			$this->db = $db;
			$this->session_space = $session_space;
			$this->sub = $sub;
		}

		function deleteSessions( $startTs = null, $endTs = null ) {
			$where = new ArdeAppender( ' AND ' );
			if( $startTs !== null ) {
				$where->append( $this->db->table( 's', $this->sub ).'.first >= '.$startTs );
			}
			if( $endTs !== null ) {
				$where->append( $this->db->table( 's', $this->sub ).'.first < '.$endTs );
			}

			$where->append( $this->db->table( 'sr', $this->sub ).'.sid = '.$this->db->table( 's', $this->sub ).'.id' );
			$where->append( $this->db->table( 'rd', $this->sub ).'.sid = '.$this->db->table( 's', $this->sub ).'.id' );

							
			$this->db->query( 'DELETE '.$this->db->table( 's', $this->sub ).', '.$this->db->table( 'sr', $this->sub ).', '.$this->db->table( 'rd', $this->sub ).' FROM '
							.$this->db->table( 's', $this->sub ).' LEFT JOIN '
							.$this->db->table( 'sr', $this->sub ).' ON( '.$this->db->table( 'sr', $this->sub ).'.sid = '.$this->db->table( 's', $this->sub ).'.id ) LEFT JOIN '
							.$this->db->table( 'rd', $this->sub ).' ON( '.$this->db->table( 'rd', $this->sub ).'.rid = '.$this->db->table( 'sr', $this->sub ).'.id )'
							.($where->c?" WHERE ".$where->s:'') );
		}

		function get_session_by_scookie($scookie) {
			$scookie=ardeU32ToStr($scookie);
			$res=$this->db->query_sub( $this->sub, "SELECT id, last, vt FROM", 's', " WHERE scookie=".$scookie );
			if(!$r=mysql_fetch_row($res)) return null;
			return array('last' => (int)$r[1] , 'sid' => ardeStrToU32($r[0]), 'vt' => (int)$r[2] );
		}

		function get_session_by_pcookie( $cookie ) {
			$cookie = ardeU32ToStr( $cookie );
			$res = $this->db->query_sub( $this->sub, 'SELECT id, last, vt FROM', 's', 'WHERE pcookie='.$cookie.' AND scookie=0 ORDER BY last DESC LIMIT 1' );
			if( !$r = mysql_fetch_row( $res ) ) return null;
			return array( 'last' => (int)$r[1], 'sid' => ardeStrToU32( $r[0] ), 'vt' => (int)$r[2] );
		}

		function get_visitor_last($pcookie) {
			$pcookie = ardeU32ToStr( $pcookie );
			$res = $this->db->query_sub( $this->sub, 'SELECT MAX(last) FROM', 's', 'WHERE pcookie='.$pcookie );
			if( !$r = mysql_fetch_row( $res ) ) return 0;
			if( !$r[0] ) return 0;
			return ardeStrToU32( $r[0] );
		}

		function get_session_by_ip( $ip ) {
			$ip = ardeU32ToStr($ip);
			$res = $this->db->query_sub( $this->sub, "SELECT id, last, vt FROM", 's',
				" WHERE ip=$ip AND scookie=0 AND pcookie=0 ORDER BY last DESC LIMIT 1" );
			if( !$r = mysql_fetch_row($res) ) return null;
			return array( 'last' => (int)$r[1], 'sid' => ardeStrToU32($r[0]), 'vt' => (int)$r[2] );
		}

		function updateVisitorType( $sessionId, $vtId ) {
			$this->db->query_sub( $this->sub, "UPDATE", 's', "SET vt=".$vtId." WHERE id=".$sessionId );
		}

		function get_ip_last($ip) {
			$ip=ardeU32ToStr($ip);
			if(!$res=$this->db->query_sub($this->sub,"SELECT MAX(last) FROM",'s',"WHERE pcookie=0 AND ip=$ip")) return false;
			if(!$r=mysql_fetch_row($res)) return -1;
			if(!$r[0]) return -1;
			return (int)$r[0];
		}

		function new_session($scookie,$pcookie,$ip,$first,$last,$vt=1) {
			$rnd=mt_rand(0,255);
			$this->db->query_sub($this->sub,'INSERT INTO','s',"(ip,scookie,pcookie,first,last,vt,rnd) VALUES ("
				.ardeU32ToStr($ip).','.ardeU32ToStr($scookie).','.ardeU32ToStr($pcookie).','.$first.','.$last.','.ardeU32ToStr($vt).','.$rnd.")");
			return $this->db->insert_id();
		}
		function update_session_last($sid,$last) {
			return $this->db->query_sub($this->sub,'UPDATE','s',"SET last=$last WHERE id=".ardeU32ToStr($sid));
		}
		function update_session_first($sid,$first) {
			return $this->db->query_sub($this->sub,'UPDATE','s',"SET first=$first WHERE id=".ardeU32ToStr($sid));
		}



		function get_request_first_last( $rid ) {



			$res = $this->db->query_sub( $this->sub, 'SELECT id, sid, time FROM', 'sr', ' WHERE id='.ardeU32ToStr($rid) );
			$o = array();
			if( $r = $this->db->fetchRow( $res ) ) {
				$o[ 'request' ] = array( 'id' => ardeStrToU32( $r[0] ), 'sid' => ardeStrToU32( $r[1] ), 'time' => (int)$r[2] );
				$res = $this->db->query( '(SELECT 1, id, sid, time FROM '.$this->db->table('sr',$this->sub).' WHERE sid = '.$r[1].' ORDER BY time, id LIMIT 2)'
					.' UNION ALL (SELECT 2, id, sid, time FROM '.$this->db->table('sr',$this->sub).' WHERE sid = '.$r[1].' ORDER BY time desc, id desc LIMIT 2) ORDER BY time, id' );
				while( $r = mysql_fetch_row( $res ) ) {
					if( $r[0] == '1' ) {
						if( isset( $o['first'] ) ) $key = 'after first';
						else $key = 'first';
					} else {
						if( isset( $o['before last'] ) ) $key = 'last';
						else $key = 'before last';
					}
					$o[$key] = array( 'id' => ardeStrToU32( $r[1] ), 'sid' => ardeStrToU32( $r[2] ), 'time' => (int)$r[3] );
				}
			}


			if( isset( $o['request'] ) && !isset( $o['last'] )) {
				$o['last'] = $o['before last'];
				unset( $o['before last'] );
			}

			return $o;
		}

		function check_older_requests($rid) {
			$rid=ardeU32ToStr($rid);
			if(!$res=$this->db->query_sub(
				$this->sub,
				'select t2.id,t1.sid,t2.time,t1.time from',
				array('sr','sr'),
				"where t1.id=$rid and t1.sid=t2.sid order by t2.id desc limit 2"))
				return false;
			$this->req_not_there=false;
			if(!$r=mysql_fetch_row($res)) {
				$this->req_not_there=true;
				return true;
			}
			$this->old_sid=ardeStrToU32($r[1]);
			$this->this_sid_first=(int)$r[3];
			$req_is_last=($r[0]==$rid);
			if(!$r=mysql_fetch_row($res)) return -1;
			$this->old_sid_last=$req_is_last?((int)$r[2]):-1;
			if(!$req_is_last) {
				if(!$res=$this->db->query_sub(
					$this->sub,
					'select id,time from','sr',
					"where sid=".ardeU32ToStr($this->old_sid)." order by id asc limit 2"))
					return false;
				$r=mysql_fetch_row($res);
				if($r[0]==$rid) {
					$r=mysql_fetch_row($res);
					$this->old_sid_first=(int)$r[1];
				}
			}
			return true;
		}

		function delete_session($sid) {
			return $this->db->query_sub( $this->sub, 'DELETE FROM', 's', "WHERE id=".ardeU32ToStr($sid) );
		}

		function update_request_sid($sid,$rid) {
			$sid=ardeU32ToStr($sid);
			$rid=ardeU32ToStr($rid);
			$this->db->query_sub($this->sub,'UPDATE','sr',"SET sid=$sid WHERE id=$rid");
			$this->db->query_sub($this->sub,'UPDATE','rd',"SET sid=$sid WHERE rid=$rid");

		}

		function new_request($sid,$pid,$time) {
			$this->db->query_sub($this->sub,'INSERT INTO','sr',"(sid,pid,time) VALUES (".ardeU32ToStr($sid).','.ardeU32ToStr($pid).','.$time.')');
			return $this->db->insert_id();
		}

		public static function addDbAccessUnitInfo( ArdeDb $db, ArdeDbAccessUnitInfo $unit, $name, $sub ) {
			$unit->subs[] = new ArdeDbAccessUnitInfo( $name, array( $db->tableName( 's', $sub, false ), $db->tableName( 'sr', $sub, false ), $db->tableName( 'rd', $sub, false ) ) );
			$unit->tableNames[] = $db->tableName( 's', $sub, false );
			$unit->tableNames[] = $db->tableName( 'sr', $sub, false );
			$unit->tableNames[] = $db->tableName( 'rd', $sub, false );
		}

		public static function getIpRef( $sub ) {
			return new ArdeDbReference( $sub, 's', 'ip' );
		}

		public static function getPCookieRef( $sub ) {
			return new ArdeDbReference( $sub, 's', 'pcookie' );
		}

		public static function getSCookieRef( $sub ) {
			return new ArdeDbReference( $sub, 's', 'scookie' );
		}

		public static function getPageRef( $sub ) {
			return new ArdeDbReference( $sub, 'sr', 'pid' );
		}

		public static function getDataRef( $entityId, $sub ) {
			return new ArdeDbReference( $sub, 'rd', 'p', array( 'eid', ' = '.$entityId ) );
		}



		public static function install( ArdeDb $db, $sub, $overwrite = false ) {
			$db->createTable( $sub, 's', '( id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, scookie INT UNSIGNED NOT NULL, pcookie INT UNSIGNED NOT NULL, ip INT UNSIGNED NOT NULL, first INT UNSIGNED NOT NULL,last INT UNSIGNED NOT NULL, vt TINYINT UNSIGNED NOT NULL DEFAULT 1, rnd TINYINT UNSIGNED NOT NULL, INDEX( ip ), INDEX( pcookie ), INDEX( scookie ), INDEX( rnd ), INDEX( first ), INDEX( last ) )', $overwrite );
			$db->createTable( $sub, 'sr', '( id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, sid INT UNSIGNED NOT NULL, pid INT UNSIGNED NOT NULL, time INT UNSIGNED NOT NULL, INDEX( sid, time, id ), INDEX( pid ) )', $overwrite );
			$db->createTable( $sub, 'rd', '( rid INT UNSIGNED NOT NULL, sid INT UNSIGNED NOT NULL, eid INT UNSIGNED NOT NULL, p INT UNSIGNED NOT NULL, UNIQUE( rid, eid, p ), INDEX( sid ), INDEX( eid, p ) )', $overwrite );
		}

		public static function uninstall( ArdeDb $db, $sub ) {
			$db->dropTableIfExists( $sub, 's' );
			$db->dropTableIfExists( $sub, 'sr' );
			$db->dropTableIfExists( $sub, 'rd' );
		}
	}

	class TwatchPathCountInfo {
		public $count;
		public $unique;
		function __construct( $count, $unique ) {
			$this->count = $count;
			$this->unique = $unique;
		}
	}
	class TwatchDbPathAnalyzer {
		private $db;
		private $sub;

		function __construct( ArdeDb $db, $sub ) {
			$this->db = $db;
			$this->sub = $sub;
		}


		public function getPathsCount() {
			$res = $this->db->query_sub( $this->sub, 'SELECT cr, SUM(c), COUNT(*) from', 'p', 'GROUP BY cr' );
			$out = array();
			while( $r = $this->db->fetchRow( $res ) ) {
				$out[ (int)$r[0] ] = new TwatchPathCountInfo( (int)$r[1], (int)$r[2] );
			}
			return $out;
		}

		public function addDbAccessUnitInfo( ArdeDbAccessUnitInfo $unitInfo, $name ) {
			$unitInfo->subs[] = new ArdeDbAccessUnitInfo( $name, array( $this->db->tableName( 'p', $this->sub, false ) ) );
			$unitInfo->tableNames[] = $this->db->tableName( 'p', $this->sub, false );
		}

		public static function getColumnRef( $sub, $index ) {
			return new ArdeDbReference( $sub, 'p', 'p'.$index );
		}

		public function samplePaths( $startTs, $endTs, $offset, $limit, $depth, $dataColumns, $pathsLiveFor, $nextCleanupRound, $vtId ) {

			$sessionsQuery = $this->db->make_query_sub( $this->sub, "SELECT id AS sid FROM", 's',
				"WHERE first >= ".$startTs." AND first < ".$endTs." AND vt=".$vtId." ".
				"ORDER BY rnd, sid LIMIT ".$offset.", ".$limit );

			$requestsQuery = "SELECT t2.sid AS sid, t2.id AS rid, t2.pid AS pid ".
				"FROM ( $sessionsQuery ) AS t1, ".$this->db->table('sr',$this->sub)." AS t2 ".
				"WHERE t1.sid = t2.sid ORDER BY t2.sid, t2.time, t2.id";

			$initQuery = 'SET @current_sid = 0, @first_rid = 0, @i=0';
			for( $i = 0; $i < $depth; $i++ ) $initQuery .= ", @p$i = 0";
			$this->db->query( $initQuery );

			$pathsQuery = "SELECT ";
			$ps = new ArdeAppender( ', ' );
			for( $i = 0; $i < $depth; $i++ ) {
				$ps->append( "@p$i AS p$i" );
			}
			$pathsQuery .= $ps->s.", @current_sid as current_sid, @first_rid as first_rid, t.pid";
			$pathsQuery .= ", IF( @current_sid != t.sid,".
				" @i := 0 + IF( @current_sid := t.sid, 0, 0 ) + IF( @first_rid := t.rid, 0, 0 ) + IF( ";
			for( $i = 0 ; $i < $depth; $i++ ) {
				$pathsQuery .= " @p$i := ";
			}
			$pathsQuery .= "0, 0, 0 )";
			$pathsQuery .= " , @i := @i + 1 ".
				") AS dummy1, ";

			$pathsQuery .= "CASE @i";
			for( $i = 0; $i < $depth; $i++ ) {
				$pathsQuery .= " WHEN ".$i." THEN @p".$i." := t.pid";
			}

			$pathsQuery .= " END AS dummy2";



			$pathsQuery .= ", @i as i";

			$pathsQuery .= " FROM ( ".$requestsQuery." ) as t";


			$selects = new ArdeAppender( ', ' );

			$i = 0;
			$joins = '';
			foreach( $dataColumns as $entityId ) {
				$joins .= ' LEFT JOIN '.$this->db->table( 'rd', $this->sub )." AS t".$i.
						" ON( tp.first_rid = t".$i.".rid AND t".$i.".eid = ".$entityId." )";

				$selects->append( 'IFNULL( t'.$i.'.p, 0 )' );
				++$i;
			}

			for( $i = 0 ; $i < $depth ; ++$i ) {
				$selects->append( 'tp.p'.$i );
			}




			$finalSelectQuery = " SELECT ".$selects->s.", ".( $nextCleanupRound + $pathsLiveFor ).", 1 FROM ( ".$pathsQuery." ) AS tp".$joins.
						" WHERE tp.i = 0 AND tp.current_sid <> 0";


			$insertQuery = "INSERT INTO ".$this->db->table( 'p', $this->sub ).$finalSelectQuery." ON DUPLICATE KEY UPDATE ".
					$this->db->table( 'p', $this->sub ).".c = ".$this->db->table( 'p', $this->sub ).".c + 1";

			$this->db->query( $insertQuery );
		}

		public function install( $columnsCount, $overwrite = false ) {
			$columns = new ArdeAppender( ', ' );
			$unique = new ArdeAppender( ', ' );
			$singleIndexes = new ArdeAppender( ', ' );
			for( $i = 0; $i < $columnsCount; ++$i ) {
				$columns->append( 'p'.$i.' INT UNSIGNED NOT NULL' );
				$unique->append( 'p'.$i );
				if( $i != 0 ) {
					$singleIndexes->append( 'INDEX ( p'.$i.' )' );
				}
			}
			$this->db->createTable( $this->sub, 'p', '(  '.$columns->s.', cr INT UNSIGNED NOT NULL, c INT UNSIGNED NOT NULL, UNIQUE( '.$unique->s.', cr ), '.$singleIndexes->s.' )', $overwrite );
		}

		public function uninstall() {
			$this->db->dropTableIfExists( $this->sub, 'p' );
		}


	}

	class TwatchDbEntityVRefCounter {

		public static function getEntityVReference( $entityId ) {
			return new ArdeDbReference( '', 'evrc', 'evid', array( 'eid', ' = '.$entityId ) );
		}

		public static function add( ArdeDb $db, $entityId, $entityVId ) {
			$db->query( 'INSERT INTO', 'evrc', 'VALUES( '.$entityId.', '.ardeU32ToStr( $entityVId ).', 1 ) ON DUPLICATE KEY UPDATE refc = refc + 1' );
		}

		public static function remove( ArdeDb $db, $entityId, $entityVId ) {
			$res = $db->query( 'SELECT refc FROM', 'evrc', 'WHERE eid = '.$entityId.' AND evid = '.ardeU32ToStr( $entityVId ) );
			if( ! $r = mysql_fetch_row( $res ) ) return;
			$referenceCount = (int)$r[0];
			if( $r[0] <= 1 ) {
				$db->query( 'DELETE FROM', 'evrc', 'WHERE eid = '.$entityId.' AND evid = '.ardeU32ToStr( $entityVId ) );
			} else {
				$db->query( 'UPDATE', 'evrc', 'SET refc = '.( $referenceCount - 1 ).' WHERE eid = '.$entityId.' AND evid = '.ardeU32ToStr( $entityVId ) );
			}

		}

		public static function install( ArdeDb $db, $overwrite ) {
			$db->createTable( '', 'evrc', '( eid INT UNSIGNED NOT NULL, evid INT UNSIGNED NOT NULL, refc INT UNSIGNED NOT NULL, UNIQUE( eid, evid ) )', $overwrite );
		}

		public static function uninstall( ArdeDb $db ) {
			$db->dropTableIfExists( '', 'evrc' );
		}
	}
?>