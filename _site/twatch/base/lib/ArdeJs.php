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
    
	class ArdeJs {
		public static function escape( $s ) {
			$s = (string)$s;
			$o = '';
			$i = 0;
			while( isset( $s[$i] ) ) {
				if( $s[$i] == '<' ) {
					$o .= '\\x3C';
				} else {
					if( $s[$i] == '\\' || $s[$i] == '\'' ) $o .= '\\';
					$o .= $s[$i];
				}
				++$i;
			}
			return $o;
		}
		
		public static function bool( $b ) {
			return $b?'true':'false';
		}
		
		public static function string( $s ) {
			return "'".self::escape( $s )."'";
		}
	}
	
	class ArdeParam {
		public static function bool( $a, $name ) {
			if( !isset( $a[ $name ] ) ) throw new ArdeException( 'parameter '.$name.' not sent' );
			$res = $a[ $name ];
			if( $res == 't' ) return true;
			elseif( $res == 'f' ) return false;
			else throw new ArdeException( 'parameter '.$name." may only contain 't'(true) or 'f'(false)" );
		}
		
		public static function int( $a, $name, $min = null, $max = null ) {
			if( !isset( $a[ $name ] ) ) throw new ArdeException( 'parameter '.$name.' not sent' );
			$res = (int)$a[ $name ];
			if( $min !== null && $res < $min ) throw new ArdeException( 'parameter '.$name." can't be less than ".$min );
			if( $max !== null && $res > $max ) throw new ArdeException( 'parameter '.$name." can't be greater than ".$max );
			return $res;
		}
		
		public static function u32( $a, $name, $min = null, $max = null ) {
			if( !isset( $a[ $name ] ) ) throw new ArdeException( 'parameter '.$name.' not sent' );
			$res = ardeStrToU32( $a[ $name ] );
			if( $min !== null && $res < $min ) throw new ArdeException( 'parameter '.$name." can't be less than ".$min );
			if( $max !== null && $res > $max ) throw new ArdeException( 'parameter '.$name." can't be greater than ".$max );
			return $res;
		}
		
		public static function str( $a, $name, $trim = -1 ) {
			if( !isset( $a[ $name ] ) ) throw new ArdeException( 'parameter '.$name.' not sent' );
			if( $trim > 0 ) return substr( $a[ $name ], 0, $trim );
			return $a[ $name ];
		}
		
		public static function arr( $a, $name, $delimiter ) {
			if( !isset( $a[ $name ] ) ) throw new ArdeException( 'parameter '.$name.' not sent' );
			if( empty( $a[ $name ] ) ) return array();
			return explode( $delimiter, $a[ $name ] );
		}
		
		public static function assocIntInt( $a, $name, $delimiter ) {
			if( !isset( $a[ $name ] ) ) throw new ArdeException( 'parameter '.$name.' not sent' );
			if( empty( $a[ $name ] ) ) return array();
			$o = array();
			$es = explode( $delimiter, $a[ $name ] );
			for( $i = 0; $i < count( $es ); ++$i ) {
				if( !isset( $es[ $i + 1 ] ) ) throw new ArdeException( 'key with no value in parameter '.$name );
				$o[ (int)$es[ $i ] ] = (int)$es[ $i + 1 ];
				++$i;
			}
			return $o;
		}
		
		public static function assocIntStr( $a, $name, $delimiter ) {
			if( !isset( $a[ $name ] ) ) throw new ArdeException( 'parameter '.$name.' not sent' );
			if( empty( $a[ $name ] ) ) return array();
			$o = array();
			$es = explode( $delimiter, $a[ $name ] );
			for( $i = 0; $i < count( $es ); ++$i ) {
				if( !isset( $es[ $i + 1 ] ) ) throw new ArdeException( 'key with no value in parameter '.$name );
				$o[ (int)$es[ $i ] ] = $es[ $i + 1 ];
				++$i;
			}
			return $o;
		}
		
		public static function intArr( $a, $name, $delimiter ) {
			if( !isset( $a[ $name ] ) ) throw new ArdeException( 'parameter '.$name.' not sent' );
			if( $a[ $name ]=='' ) return array();
			$o = explode( $delimiter, $a[ $name ] );
			foreach( $o as &$oe ) {
				$oe = (int)$oe;
			}
			return $o;
		}
	}
	
	class ArdeUrlWriter {
		public $url;
		public $params = array();
	    public $prepends = array();
		public $appends = array();
		
		public function duplicate() {
			$o = new AdUrlWriter( $this->url );
			$o->params = $this->params;
			$o->appends = $this->appends;
			return $o;
		}
		
		public function __construct( $url = '.' ) {
			$urls = explode( '?', $url );
			$this->url = $urls[0];

			if( isset($urls[1]) ) {
				$varss = explode( '&' , $urls[1] );
				foreach( $varss as $v ) {
					$vs = explode( '=' , $v );
					$this->params[ urldecode( $vs[0] ) ] = isset( $vs[1] )?urldecode( $vs[1] ):'';
				}
			}
		}

		public function setAddress( $url ) {
			$this->url = $url;
			return $this;
		}
		
		public function setParam( $name, $value, $def = null ) {
			if( isset( $this->params[ $name ] )) {
				if( $def !== null && $value == $def ) {
					unset( $this->params[ $name ] );
				} else {
					$this->params[ $name ] = $value;
				}
			} else {
				if( $def === null || $value != $def ) $this->params[ $name ] = $value;
			}
			return $this;
		}
		
		public function prependUrl( $value, $def = null ) {
			if( $def === null || $value != $def ) {
				$this->url = '/'.$value.$this->url;
			}
			return $this;
		}
		
		public function setAppend( $value, $def = null, $pos = 0 ) {

			if( $def === null || $value != $def ) {
				$this->appends[ $pos ] = $value;
			} else {
				$this->removeAppend( $pos );
			}
			return $this;
		}
		
        public function setPrepend( $value, $def = null, $pos = 0 ) {

            if( $def === null || $value != $def ) {
                $this->prepends[ $pos ] = $value;
            } else {
                $this->removePrepend( $pos );
            }
            return $this;
        }
        
		public function removeAppend( $pos = 0 ) {
			unset( $this->appends[ $pos ] );
			return $this;
		}
		
        public function removePrepend( $pos = 0 ) {
            unset( $this->prepends[ $pos ] );
            return $this;
        }
		
		public function removeParam( $name ) {
			unset( $this->params[ $name ] );
			return $this;
		}
		
		public function getAddressUrl( $address ) {
			$this->url = $address;
			return $this->getUrl();
		}
		
		public function getUrl() {
			$ps = '';
			$i = 0;
			
			foreach( $this->params as $name => $value ) {
				$ps .= ($i?'&':'').urlencode( $name ).'='.urlencode( $value );
				++$i;
			}
			
			$s = $this->url;
            foreach( $this->prepends as $prepend ) {
                $s = '/'.$prepend.$s;
            }
			foreach( $this->appends as $append ) {
				if( !$s || $s{ strlen($s) - 1 } != "/" ) $s .= '/';
				$s .= $append;
			}
			if( $ps ) $s .= '?'.$ps;
			
			return $s;
			
			
		}
		
		public function read_GET() {
			$this->params = $_GET;
		}
		
		public static function getCurrentRelative() {
			$reqUri = ardeRequestUri();
			$components = parse_url( $reqUri );
			if( preg_match( '/\/([^\/]*)$/', $reqUri, $matches ) ) {
				$o = new self( './'.$matches[1] );
			} else {
				$o = '/'.$reqUri;
			}
			return $o;
		}

		public static function replaceUrlVar( $url , $vname , $vvalue , $def ) {

			
			
			
			foreach( $vars as $name => $value ) {
				if( $name == $vname ) {
					if( $vvalue == $def ) continue;
					$s.= ($s?'&':'').urlencode( $name ).'='.urlencode( $vvalue );
					$varFound = true;
				} else {
					$s.= ($s?'&':'').urlencode( $name ).'='.urlencode( $value );
				}
			}
			if(!$varFound && $vvalue != $def ) {
				if( $s ) $s.='&';
				$s.= urlencode( $vname ).'='.urlencode( $vvalue );
			}
			if($s) return $addr.'?'.$s;
			return $addr;
		}
	}
?>