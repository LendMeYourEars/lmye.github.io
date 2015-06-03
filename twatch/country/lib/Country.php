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

	require_once $ardeCountry->path( 'db/DbCountry.php' );
	require_once $ardeCountry->path( 'lib/General.php' );
	
	class ArdeCountry {

		const COUNTRY_UNKNOWN = 1;
		const UK = 223;
		const GB = 79;
		public static $codeToId = null;
		public static $idToName = null;
		public static $nameToId = null;

		public static function getNameId( $name ) {
			global $ardeCountry;
			if( self::$nameToId === null ) require_once $ardeCountry->path( 'lib/NameToId.php' );
			if( isset( self::$nameToId[ strtolower( $name ) ] ) ) {
				return self::$nameToId[ strtolower( $name ) ];
			}
			return false;
		}

		public static function getIdName( $id ) {
			global $ardeCountry;
			if( self::$idToName === null ) require_once $ardeCountry->path( 'lib/IdToName.php' );
			if( !isset( self::$idToName[ $id ] ) ) return null;
			return self::$idToName[ $id ];
		}

		public static function getCodeId( $code ) {
			global $ardeCountry;
			if( self::$codeToId === null ) require_once $ardeCountry->path( 'lib/CodeToId.php' );
			if( !isset( self::$codeToId[ $code ] ) ) return null;
			return self::$codeToId[ $code ];
		}

		public static function getIdNames() {
			global $ardeCountry;
			if( self::$idToName === null ) require_once $ardeCountry->path( 'lib/IdToName.php' );
			return self::$idToName;
		}

		public static function fetchCountry( $ip ) {
			global $ardeCountry;
			$dbCountry = new ArdeDbCountry( $ardeCountry->db );
			$res = $dbCountry->fetchCountry( $ip );
			if( $res === null ) return self::COUNTRY_UNKNOWN;
			return $res;
		}

	}
?>