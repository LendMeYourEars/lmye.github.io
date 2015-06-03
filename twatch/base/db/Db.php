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

	require_once dirname(__FILE__).'/../lib/ArdeException.php';

	class ArdeDbQueryError extends ArdeException {
		protected $class = "Database Query Error";
		function __construct( $query, $dbErrorNo, $dbMessage ) {
			parent::__construct( $dbMessage, $dbErrorNo, null, 'Query: '.$query );
		}
	}

	class ArdeDbAccessUnitInfo {
		public $name;

		public $tableNames;

		public $subs;

		function __construct( $name, $tableNames = array() ) {
			$this->name = $name;
			$this->tableNames = $tableNames;
			$this->subs = array();
		}

		function jsObject() {
			$tableNames = new ArdeAppender( ', ' );
			foreach( $this->tableNames as $tableName ) {
				$tableNames->append( "'".$tableName."'" );
			}
			$subs = new ArdeAppender( ', ' );
			foreach( $this->subs as $sub ) {
				$subs->append( $sub->jsObject() );
			}
			return "new DbAccessUnit( '".ArdeJs::escape( $this->name )."', [ ".$tableNames->s." ], [ ".$subs->s." ] )";
		}
	}

	class ArdeDbUnitDiagnosticInfo {
		public $dataSize;
		public $indexSize;

		function __construct( $dataSize, $indexSize ) {
			$this->dataSize = $dataSize;
			$this->indexSize = $indexSize;
		}

		function jsObject() {
			return 'new DbUnitDiagInfo( '.$this->dataSize.', '.$this->indexSize.' )';
		}
	}

	class ArdeDbDiagnosticInfo {
		private $unitInfos;
		private $db;

		private $diagInfos;

		function __construct( $unitInfos, ArdeDb $db ) {
			$this->unitInfos = $unitInfos;
			$this->db = $db;
			$this->diagInfos = array();
		}

		function load() {
			$res = $this->db->query( "SHOW TABLE STATUS FROM ".$this->db->getDatabaseName()." LIKE '".$this->db->pre."_%'" );
			while( $o = $this->db->fetchObject( $res ) ) {
				$this->diagInfos[ $o->Name ] = new ArdeDbUnitDiagnosticInfo( $o->Data_length, $o->Index_length );
			}
		}



		function jsObject() {
			$diagInfos = new ArdeAppender( ', ' );
			foreach( $this->diagInfos as $tableName => $diagInfo ) {
				$diagInfos->append( "'".$tableName."': ".$diagInfo->jsObject() );
			}
			$unitInfos = new ArdeAppender( ', ' );
			foreach( $this->unitInfos as $unitInfo ) {
				$unitInfos->append( $unitInfo->jsObject() );
			}
			return "new DbDiagInfo( { ".$diagInfos->s." }, [ ".$unitInfos->s." ] )";
		}

	}

	class ArdeDbReference {
		var $sub;
		var $table;
		var $column;
		var $conditions;

		public function __construct( $sub, $table, $column, $conditions = array() ) {
			$this->sub = $sub;
			$this->table = $table;
			$this->column = $column;
			$this->conditions = $conditions;
		}

		public function getTableName( ArdeDb $db ) {
			return $db->tableName( $this->table, $this->sub );
		}

		public function getExtraCondition( $tableAlias ) {
			$cond = new ArdeAppender( ' AND ' );
			for( $i = 0; $i < count( $this->conditions ); ++$i ) {

				if( !isset( $this->conditions[ $i + 1 ] ) ) throw new ArdeException( 'bad reference condition' );
				$cond->append( $tableAlias.'.'.$this->conditions[ $i ].$this->conditions[ $i + 1 ] );
				++$i;
			}
			return $cond->s;
		}

		public function getColumnName() {
			return $this->column;
		}
	}



	class ArdeDb {

		var $id=false;
		var $server;
		var $user;
		var $pass;
		var $db;
		var $pre;
		var $unions=array();
		var $tempno=0;
		var $version=4.1;
		var $sub='';

		public $charset = null;
		public $collation = null;
		public $bigSelects = false;


		const ERROR_COLUMN_NOT_EXIST = 1054;
		const ERROR_TABLE_NOT_EXIST = 1146;

		function getErrorCode( $code ) {
			return $code;
		}

		function __construct($server=false,$db=false,$pre=false,$user=false,$pass=false) {

			if(is_array($server)) {

				$this->server=$server['db_server'];
				$this->db=$server['db_database'];
				$this->user=$server['db_username'];
				$this->pass=$server['db_password'];
				$this->pre=$server['db_table_prefix'];
				if( isset( $server[ 'db_charset' ] ) ) $this->charset = $server[ 'db_charset' ];
				else $this->charset = 'utf8';
				if( isset( $server[ 'db_collation' ] ) ) $this->collation = $server[ 'db_collation' ];
				else $this->collation = 'utf8_general_ci';
				if( isset( $server[ 'db_mysql_big_selects' ] ) ) $this->bigSelects = $server[ 'db_mysql_big_selects' ];
			} else {

				$this->server=$server;
				$this->db=$db;
				$this->user=$user;
				$this->pass=$pass;
				$this->pre=$pre;
			}
		}

		function getCharset() {
			return $this->charset;
		}

		function getCollation() {
			return $this->collation;
		}

		function connect( $forceNewLink = false ) {

			if( !$this->id = @mysql_connect( $this->server, $this->user, $this->pass, $forceNewLink ) ) {

				throw new ArdeException( 'Error connecting to database server' );
			}

			if( $this->bigSelects ) {
				$this->query( "SET SQL_BIG_SELECTS = 1" );
			}
			return true;
		}

		function getDatabaseName( $withBackQuotes = true ) {
			if( $withBackQuotes ) {
				return "`".$this->db."`";
			} else {
				return $this->db;
			}
		}

		function table($t,$sub = null ) {
			if(is_array($t)) {
				return '('.$t[1].')';
			}
			return $this->tableName( $t, $sub );
		}

		public function tableName( $name, $sub = null, $prependDb = true ) {
			return ($prependDb?$this->getDatabaseName().".":'').$this->pre.'_'.( $sub === null ? $this->sub : $sub ).'_'.$name;
		}


		
		function make_query($q,$tables='',$qa='') {
			if(is_array($tables)&&$tables[0]!='sub_query') {
				$t='';
				$c=1;
				foreach($tables as $ts) {
					$t.=($c>1?',':'').$this->table($ts)." as t".($ts=='du'?'u':$c);
					$c++;
				}
				$tables=$t;
			} elseif($tables) {
				$tables=$this->table($tables);
			}

			if($tables) {
				$q.=' '.$tables.($qa?(' '.$qa):'');
			}
			return $q;
		}

		function makeQuery( $q, $tables='', $qa='' ) {
			return $this->make_query( $q, $tables, $qa );
		}

		function getDiagnosticInfo( $unitInfos ) {
			$o = new ArdeDbDiagnosticInfo( $unitInfos, $this );
			$o->load();
			return $o;
		}

		function getTableInfo( $name, $sub = null ) {
			$res = $this->query( "SHOW TABLE STATUS FROM ".$this->getDatabaseName()." LIKE '".$this->tableName( $name, $sub, false )."'" );
			if( !$this->numRows( $res ) ) throw new ArdeException( 'error getting information about table '.$this->tableName( $name, $sub )." perhaps it doesn't exist" );
			$o = $this->fetchObject( $res );
			return new ArdeDbUnitDiagnosticInfo( $o->Data_length, $o->Index_length );
		}

		function add_union($q,$tables='',$qa='') {
			$q=$this->make_query($q,$tables,$qa);
			$this->unions[]=$q;
		}
		function add_union_sub($sub,$q,$tables='',$qa='') {
			$q=$this->make_query_sub($sub,$q,$tables,$qa);
			$this->unions[]=$q;
		}
		function roll_unions($order='') {
			return $this->query($this->get_unions($order));
		}
		function delete($table,$alias,$q) {
			if($this->version>=4.1)
				$q="delete $alias from ".$q;
			else
				$q="delete ".$this->table($table)." from ".$q;
			return $this->query($q);
		}
		function get_unions($order='') {

			$q='';
			$i=0;
			foreach($this->unions as $union) {
				$q.=($i?' union all ':'')."($union)";
				$i++;
			}
			$this->unions=array();
			if($order)
				$order=' '.$order;
			return $q.$order;

		}
		function query_sub( $sub, $q, $tables='', $qa='' ) {
			$this->sub = $sub;
			$res = $this->query( $q, $tables, $qa );
			$this->sub = '';
			return $res;
		}

		function getMaxJoins() {
			return 30;
		}

		function make_query_sub( $sub, $q, $tables='', $qa='' ) {
			$this->sub = $sub;
			$res = $this->make_query( $q, $tables, $qa );
			$this->sub = '';
			return $res;
		}

		function query( $q, $tables = '', $qa = '' ) {
			global $twatch;

			$q = $this->make_query( $q, $tables, $qa );






			if( ! $res = mysql_query( $q, $this->id ) ) {
				throw new ArdeDbQueryError( $q, mysql_errno($this->id), mysql_error( $this->id ) );
			}

			return $res;
		}

		public function queryInt( $q, $tables = '', $qa = '' ) {
			$res = $this->query( $q, $tables, $qa );
			if( !( $r = $this->fetchRow( $res ) ) ) return null;
			if( $r[0] === null ) return null;
			return (int)$r[0];
		}

		public function querySubInt( $sub, $q, $tables = '', $qa = '' ) {
			$res = $this->query_sub( $sub, $q, $tables, $qa );
			if( !( $r = $this->fetchRow( $res ) ) ) return null;
			if( $r[0] === null ) return null;
			return (int)$r[0];
		}

		public function startProfileQueries( ArdeTimer $profileTimer ) {
			$this->profileTimer = $profileTimer;
			$this->profileQueries = true;
		}

		function insert_id() {
			return mysql_insert_id($this->id);
		}

		function lastInsertId() {
			return mysql_insert_id($this->id);
		}

		function affected_rows() {
			return mysql_affected_rows($this->id);
		}

		public function dropTableIfExists( $sub, $tableName ) {
			$this->query_sub( $sub, "DROP TABLE IF EXISTS", $tableName );
		}

		function create_table($sub,$name,$def,$type='',$overwrite=true) {
			if($overwrite) {
				$this->dropTableIfExists( $sub, $name );
			}
			$this->query_sub( $sub, "CREATE TABLE", $name, "($def) ".( $type?$type:"ENGINE=MYISAM PACK_KEYS=1" ) );
		}

		function createTable( $sub, $name, $definition, $overwrite = false ) {
			if( $overwrite ) {
				$this->dropTableIfExists( $sub, $name );
			}
			$definition .= ' ENGINE = MYISAM';
			$this->query_sub( $sub, "CREATE TABLE", $name, $definition );
		}

		function tableExists( $name, $sub = '' ) {
			try	{
				$this->query_sub( $sub, 'SELECT 1 FROM', $name );
			} catch( ArdeDbQueryError $e ) {
				if( $this->getErrorCode( $e->getCode() ) == self::ERROR_TABLE_NOT_EXIST ) return false;
				throw $e;
			}
			return true;
		}
		
		function columnExists( $colName, $tableName, $sub = '' ) {
			try {
				$this->query_sub( $sub, 'SELECT '.$colName.' FROM', $tableName, 'LIMIT 1' );
			} catch( ArdeDbQueryError $e ) {
				if( $this->getErrorCode( $e->getCode() ) == self::ERROR_COLUMN_NOT_EXIST ) return false;
				throw $e;
			}
			return true;
		}
		
		function installDummyTable( $sub, $tableName, $count, $overwrite = false ) {
			$this->createTable( $sub, $tableName, '( i INT UNSIGNED NOT NULL PRIMARY KEY )', $overwrite );

			for( $j = 0; $j < $count; ++$j ) {
				$q = new ArdeAppender( ',' );
				for( $i = 1; $i <= 200; ++$i ) {
					$q->append( '('.($j*200+$i).')' );
				}
				$this->query_sub( $sub, 'INSERT INTO', $tableName, "(i) VALUES ".$q->s );
			}
		}

		function uninstallDummyTable( $sub, $tableName ) {
			$this->dropTableIfExists( $sub, $tableName );
		}

		function lock_tables() {
			return $this->query('lock tables twatch_du as tu write,twatch_t write,twatch_t as t1 write,twatch_r write,twatch_d write,twatch_d as t2 write,twatch_h write,twatch_s write,twatch_sr write,twatch_rd write');
		}

		function unlock_tables() {
			return $this->query('unlock tables');
		}

		function show_results($res) {
			$c=mysql_num_fields($res);
			echo "<table border=1>";
			echo '<tr style="font-weight:bold">';
			for($i=0;$i<$c;$i++) {
				$f=mysql_fetch_field($res,$i);
				echo "<td>".($f->name?$f->name:'&nbsp;')."</td>";
			}
			while($r=mysql_fetch_row($res)) {
				echo "<tr>";
				foreach($r as $rs) {
					echo "<td>".($rs!=''?$rs:'&nbsp;')."</td>";
				}
				echo "</tr>";
			}
			echo "</tr>";
			echo "</table>";
			mysql_data_seek($res,0);
		}

		public function escape( $s ) {
			return mysql_real_escape_string( $s, $this->id );
		}

		public function string( $s, $charset = null ) {
			if( $charset === null ) {
				if( $this->charset !== null ) {
					$o = '_'.$this->charset.' ';
				} else {
					$o = '';
				}
			} else {
				$o = '_'.$charset.' ';
			}
			$o .= "'".$this->escape( $s )."'";


			return $o;
		}

		public function stringCol( $s, $charset = null, $collation = null ) {
			$o = $this->string( $s, $charset );
			if( $collation === null ) {
				if( $this->collation !== null ) {
					$o .= ' COLLATE '.$this->collation;
				}
			} else {
				$o .= ' COLLATE '.$collation;
			}
			return $o;
		}

		public function stringResult( $name ) {
			return 'BINARY '.$name;
		}

		public function reinterpretColumn( $sub, $tableName, $colName, $newDefinition, $isKey = false ) {
			$this->query_sub( $sub, 'ALTER IGNORE TABLE', $tableName, 'MODIFY '.$colName.( $isKey?' VARBINARY(255)':' BLOB' ) );
			$this->query_sub( $sub, 'ALTER IGNORE TABLE', $tableName, 'MODIFY '.$colName.' '.$newDefinition );
		}

		public function fetchRow( $res ) {
			return mysql_fetch_row( $res );
		}

		public function fetchInt( $res ) {
			if( $r = $this->fetchRow( $res ) ) {
				return (int)$r[0];
			} else {
				return 0;
			}
		}
		
		public function fetchField( $res ) {
			return mysql_fetch_field( $res );
		}

		public function fetchObject( $res ) {
			return mysql_fetch_object( $res );
		}

		public function numRows( $res ) {
			return mysql_num_rows( $res );
		}

		public function affectedRows() {
			return  mysql_affected_rows($this->id);
		}
	}

?>