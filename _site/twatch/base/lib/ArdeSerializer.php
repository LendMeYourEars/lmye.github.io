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

	class ArdeSerialData {
		public $version;

		public $data;

		function __construct( $version = 1, $data = array() ) {
			$this->version = $version;
			$this->data = $data;
		}
	}

	interface ArdeSerializable {

		public function getSerialData();

		public static function makeSerialObject( ArdeSerialData $data );

	}


	abstract class ArdeSerializer {

		public static function dataToString( $d ) {
			if( is_array( $d ) || is_object( $d ) ) {
				return self::objectToString( $d );
			} elseif( is_bool( $d ) ) {
				return 'b'.(int)$d;
			} elseif( is_int( $d ) ) {
				return 'i'.$d;
			} elseif( is_float( $d ) ) {
				return 'f'.$d;
			} elseif( is_string( $d ) ) {
				return 's'.self::escape( $d );
			} elseif( is_null( $d ) ) {
				return 'n';
			} else {
				throw new ArdeException( "don't know how to handle variable of type: ".gettype( $d ) );
			}
		}


		public static function stringToData( $s ) {
			self::_stringToData( $s, 0, $data );
			return $data;


		}

		private static function _stringToData( $s, $i, &$data ) {
			if( !
				preg_match( '/ \G (.) (?: (?<=\[) (.+?) \, (.+?) ([\,\]]) |(?<=[fi]) (.+?) ((?=[\]\,]|$)) |(?<=b) ([01]) |(?<=n) |(?<=s) ( (?:\\\\.|.)*? ) ((?=[\]\,]|$))) /sx', $s, $matches, PREG_OFFSET_CAPTURE, $i )
			) {
				throw new ArdeException( 'invalid data' );
			}

			switch( $matches[1][0] ) {
				case "[": {

					$identifier = $matches[2][0];

					$version = (int) $matches[3][0];

					$ds = array();
					$j = $matches[4][1];
					if( $matches[4][0] != ']' ) {
						do {
							$j = self::_stringToData( $s, $j+1, $d );
							$ds[] = $d;
							if( !isset( $s[$j] ) ) throw new ArdeException( 'invalid data' );
						} while( $s[$j] == ',' );
						if( $s[$j] != ']' ) throw new ArdeException( '] expected' );
					}
					$data = self::serialDataToObject( $identifier, $version, $ds );
					return $j + 1;

				} case "i": {
					$data = (int)$matches[5][0];
					return $matches[6][1];

				} case "s": {
					$data = self::unescape( $matches[8][0] );
					return $matches[9][1];

				} case "b": {
					$data = (bool)$matches[7][0];
					return $i+2;

				} case "f": {
					$data = (double)$matches[5][0];
					return $matches[6][1];

				} case "n": {
					$data = null;
					return $i+1;

				} default: {
					throw new ArdeException( "unkonwn type identifier '".$matches[1]."'" );

				}

			}


		}

		private static $escapeChars = array( '\\' => true, '[' => true, ']' => true, ',' => true );

		private static function escape( $s ) {
			$o = '';
			$i = 0;
			while( isset( $s[ $i ] ) ) {
				if( isset( self::$escapeChars[ $s[ $i ] ] ) ) {
					$o .= "\\";
				}
				$o .= $s[ $i ];
				++$i;
			}
			return $o;
		}

		private static function unescape( $s ) {
			$o = '';
			$i = 0;
			while( isset( $s[ $i ] ) ) {
				if( $s[ $i ] == '\\' ) {
					++$i;
					if( !isset( $s[ $i ] ) ) throw ArdeException( "single \\ found" );
				}
				$o .= $s[ $i ];
				++$i;
			}
			return $o;
		}

		private static function isAssociativeArray( $a ) {
			$i = 0;
			foreach( $a as $key => $value ) {
				if( $key !== $i ) return true;
				++$i;
			}
			return false;
		}

		private static function objectToString( $o ) {
			if( is_array( $o ) ) {
				if( self::isAssociativeArray( $o ) ) {
					$classIdentifier = 'aa';

					$o = new ArdeSerialAssocArray( $o );
				} else {
					$classIdentifier = 'a';
					$o = new ArdeSerialArray( $o );
				}
			} else {
				if( !$o instanceof ArdeSerializable ) throw new ArdeException( get_class( $o ).' is not ArdeSerializable' );
				$classIdentifier = get_class( $o );
				if( $classIdentifier == 'a' || $classIdentifier == 'aa' ) throw new ArdeException( 'a and aa classes are reserved classes with these names cannot be ArdeSerializable' );
			}
			$data = $o->getSerialData();
			if( !$data instanceof ArdeSerialData ) throw new ArdeException( 'getSerialData() method of the class '.get_class( $o ).' must return an ArdeSerialData' );
			$os = '['.$classIdentifier.','.$data->version;
			foreach( $data->data as $d ) {
				$os .= ','.self::dataToString( $d );
			}
			$os.=']';
			return $os;
		}

		private static function serialDataToObject( $identifier, $version, $data ) {

			$d = new ArdeSerialData();
			$d->version = $version;
			$d->data = $data;

			if( $identifier == 'a' ) {
				$o = ArdeSerialArray::makeSerialObject( $d );
				return $o->a;
			} elseif( $identifier == 'aa' ) {
				$o = ArdeSerialAssocArray::makeSerialObject( $d );
				return $o->a;
			} elseif( class_exists( $identifier ) ) {

				return call_user_func( array( $identifier, 'makeSerialObject' ), $d );


			} else {
				throw new ArdeException( 'unknown class: '.$identifier );
			}
		}

	}

	class ArdeSerialArray implements ArdeSerializable {
		public $a = array();

		function __construct( $a ) {
			$this->a = $a;
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data );
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, $this->a );
		}
	}

	class ArdeSerialAssocArray implements ArdeSerializable {
		public $a;

		function __construct( $a ) {
			$this->a = $a;
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			$o = new self( array() );
			for( $i = 0; $i < count( $d->data ); $i += 2 ) {
				if( !isset( $d->data[ $i + 1 ] ) ) throw new ArdeException( 'array key with no value' );
				$o->a[ $d->data[ $i ] ] = $d->data[ $i + 1 ];
			}
			return $o;
		}

		public function getSerialData() {
			$data = array();
			foreach( $this->a as $key => $value ) {
				$data[] = $key;
				$data[] = $value;
			}
			return new ArdeSerialData( 1, $data );
		}
	}

?>