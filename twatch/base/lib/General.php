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


	class ArdeTime {

		const DAY_SECONDS = 86400;
		const HOUR_SECONDS = 3600;

		const STRING_FULL = 1;
		const STRING_DAY = 2;
		const STRING_MONTH = 3;

		var $ts;

		public static $monthsShort = array(
			1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
		);

		public static $monthShortNums = array(
			'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12
		);

		protected static $stringIdFormats = array(
			 self::STRING_FULL => 'Y-M-d H:i:s'
			,self::STRING_DAY => 'l jS M'
			,self::STRING_MONTH => 'F Y'
		);

		public function format( $format ) {
			return $this->_format( $format, $this->ts );
		}

		public function __clone() {
			return new self( $this->ts );
		}

		public function duplicate() {
			return new self( $this->ts );
		}

		protected function _format( $format, $ts ) {
			return gmdate( $format, $ts );
		}

		public function mkTime( $year, $month, $day, $hour, $minute, $second ) {
			$res = gmmktime( $hour, $minute, $second, $month, $day, $year );
			if( $res == -1 ) return false;
			return $res;
		}

		public function offset( $offset ) {
			if( $offset == 0 ) return $this;
			return new self( $this->ts + $offset );
		}

		public function monthOffset( $offset ) {
			if( $offset == 0 ) return $this;
			$m = $this->getMonth() + $offset;

			$month = $m % 12;
			if( $month <= 0 ) $month = 12 + $month;

			if( $m <= 0 ) $yearo = -1;
			else $yearo = ( $m - ($m%12) ) / 12 - ( $m%12 ? 0 : 1 );
			if( $m < 0 ) $yearo += ( $m - ($m%12) ) / 12;

			$year = $this->getYear() + $yearo;

			return new self( $this->mkTime( $year, $month, 1, 0, 0, 0 ) );
		}



		public function dayOffset( $offset ) {
			if( $offset == 0 ) return $this;
			return new self( $this->ts + $offset * self::DAY_SECONDS );
		}

		public function hourOffset( $offset ) {
			if( $offset == 0 ) return $this;
			return new self( $this->ts + $offset * self::HOUR_SECONDS );
		}


		public function __construct( $ts = 0 ) {
			$this->ts = $ts;
		}

		public function initWithDayCode( $code ) {
			$this->ts = $this->mkTime( (int)('20'.substr($code,0,2)), (int)substr($code,2,2), (int)substr($code,4,2), 0, 0, 0 );
			return $this;
		}

		public function initWithDate( $year, $month, $day = 1, $hour = 0, $minute = 0, $second = 0 ) {
			$this->ts = $this->mkTime( $year, $month, $day, $hour, $minute, $second );
			return $this;
		}

		public function isValidDayCode( $code ) {
			if( strlen( $code ) != 6 ) return false;
			if( !$this->isValidMonthCode( substr( $code, 0, 4 ) ) ) return false;
			$d = substr($code,4,2);
			if( !preg_match( '/^[0-9][0-9]$/', $d ) ) return false;
			if( (int)$d < 1 || (int)$d > 31 ) return false;
			$t = clone $this;
			if( $t->initWithDayCode( $code )->getDay() != (int)$d ) return false;
			return true;
		}

		public function isValidDate( $year, $month, $day ) {
			if( $year < 2000 || $year > 2099 ) return false;
			if( $month < 1 || $month > 12 ) return false;
			if( $day < 1 || $day > 31 ) return false;
			$ts = $this->mkTime( $year, $month, $day, 0, 0, 0 );
			if( $ts === false ) return false;
			if( $this->getTime( $ts )->getMonth() != $month ) return false;
			return true;
		}

		public function isValidMonthCode( $code ) {
			if( strlen( $code ) != 4 ) return false;
			$y = substr($code,0,2);
			if( !preg_match( '/^[0-9][0-9]$/', $y ) ) return false;
			$m = substr($code,2,2);
			if( !preg_match( '/^[0-9][0-9]$/', $m ) ) return false;
			if( (int)$m > 12 || (int)$m == 0 ) return false;
			return true;
		}

		public function initWithMonthCode( $code ) {
			$this->ts = $this->mkTime( (int)('20'.substr($code,0,2)), (int)substr($code,2,2), 1, 0, 0, 0 );
			return $this;
		}


		public static function getTime( $ts = 0 ) {
			return new self( $ts );
		}


		function getString( $id ) {
			return $this->format( self::$stringIdFormats[ $id ] );
		}


		function getYear() {
			return (int)$this->format( 'Y' );
		}

		function getMonth() {
			return (int)$this->format( 'n' );
		}

		function getWeekday() {
			return (int)$this->format( 'w' );
		}

		function getDay() {
			return (int)$this->format( 'j' );
		}

		function getHour() {
			return (int)$this->format( 'G' );
		}

		function getPaddedHour() {
			return str_pad( $this->getHour(), 2, '0', STR_PAD_LEFT );
		}

		function getMinute() {
			return (int)$this->format( 'i' );
		}

		function getPaddedMinute() {
			return str_pad( $this->getMinute(), 2, '0', STR_PAD_LEFT );
		}

		function getSecond() {
			return (int)$this->format( 's' );
		}

		function getPaddedSecond() {
			return str_pad( $this->getSecond(), 2, '0', STR_PAD_LEFT );
		}

		function getWeekdayLong() {
			return $this->format( 'l' );
		}

		function getMonthShort() {
			return $this->format( 'M' );
		}

		function getMonthLong() {
			return $this->format( 'F' );
		}



		function getMonthStart( $arg = null ) {
			if( $arg !== null ) throw new ArdeException( 'doesnt accept arg anymore' );
			return $this->mkTime( $this->getYear(), $this->getMonth(), 1, 0, 0, 0 );
		}

		function getDayStart( $arg = null ) {
			if( $arg !== null ) throw new ArdeException( 'doesnt accept arg anymore' );
			return $this->mkTime( $this->getYear(), $this->getMonth(), $this->getDay(), 0, 0, 0 );
		}


		function getHourStart() {
			return $this->mkTime( $this->getYear(), $this->getMonth(), $this->getDay(), $this->getHour(), 0, 0 );
		}






		function getDayCode( $arg = null ) {
			if( $arg !== null ) throw new ArdeException( 'doesnt accept arg anymore' );
			return $this->format( 'ymd' );
		}



		function getMonthCode( $arg = null ) {
			if( $arg !== null ) throw new ArdeException( 'doesnt accept arg anymore' );
			return $this->format( 'ym' );
		}



		public static function secondsString( $s ) {
			$res=new ArdeAppender(' ');

			$m=floor($s/60);
			$s-=$m*60;
			$h=floor($m/60);
			$m-=$h*60;
			if($d=floor($h/24)) $res->append($d.'d');
			if($h-=$d*24) $res->append($h.'h');
			if($m) $res->append($m.'m');
			if($s) $res->append($s.'s');
			return $res->s;
		}

		public static function getMicrotime() {
			$a=microtime();
			list($usec, $sec) = explode(" ",$a);
			return ((double)$usec + (double)$sec);
		}
	}





	class ArdeAppender {
		public $i=0;
		public $start=0;
		public $s='';
		public $c=0;
		protected $d='';
		function __construct($delimiter,$start=0) {
			$this->d=$delimiter;
			$this->i=$this->start=$start;
		}
		function append($s) {
			$this->s.=($this->i==$this->start?'':$this->d).$s;
			$this->i++;
			$this->c++;
			return $this;
		}
	}

	class ArdeExtAppender extends ArdeAppender {
		public $lastDel;
		function __construct($delimiter, $lastDelimiter, $start=0) {
			parent::__construct( $delimiter, $start );
			$this->lastDel = $lastDelimiter;
		}

		function appendExt( $s, $isLast = false ) {
			if( $isLast ) {
				$this->s .= ($this->i==$this->start?'':$this->lastDel).$s;
				$this->i++;
				$this->c++;
			} else {
				$this->append( $s );
			}
			return $this;
		}
	}

	function ardeUniqueId() {
		die('error');
		return md5(uniqid(rand(), true));
	}


	function ardeGetIp( $s ) {
		if( preg_match( '/(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})/', $s, $matches ) ) {
			for( $i=1; $i<5; $i++ ) {
				if( $matches[$i] !== '0' && $matches[$i][0] === '0' ) return false;
				if( (int)$matches[$i] < 0 || (int)$matches[$i] > 255 ) return false;
			}
			return $matches[0];
		}
		return false;
	}

	function ardeIpToU32( $ip ) {
		return (double)sprintf( '%u', ip2long( $ip ) );
	}



	function ardeAreEquiv( $a, $b ) {
		if( is_object( $a ) ) {
			if( !is_object( $b ) ) return false;
			$bClassName = get_class( $b );
			if( !( $a instanceof $bClassName ) ) return false;
			if( !method_exists( $a, 'isEquivalent' ) ) return false;
			return $a->isEquivalent( $b );
		} elseif( is_array( $a ) ) {
			return ardeEquivArrays( $a, $b );
		} else {
			return $a == $b;
		}
	}

	function ardeEquivOrderedArrays( $a, $b ) {
		if( !is_array( $a ) ) return false;
		if( !is_array( $b ) ) return false;
		if( count( $a ) != count( $b ) ) return false;
		if( count( $a ) == 0 ) return true;
		reset( $a );
		reset( $b );
		do {
			if( key( $a ) != key( $b ) ) return false;
			if( !ardeAreEquiv( current( $a ), current( $b ) ) ) return false;
			each( $a );
		} while( each( $b ) !== false );
		return true;
	}

	function ardeEquivArrays( $a, $b ) {
		if( !is_array( $a ) ) return false;
		if( !is_array( $b ) ) return false;
		if( count( $a ) != count( $b ) ) return false;
		foreach( $a as $key => $value ) {
			if( !isset( $b[ $key ] ) ) return false;
			if( !ardeAreEquiv( $value, $b[ $key ] ) ) return false;
		}
		return true;
	}

	function ardeEquivSets( $a, $b ) {
		if( !is_array( $a ) ) return false;
		if( !is_array( $b ) ) return false;
		if( count( $a ) != count( $b ) ) return false;
		foreach( $a as $aValue ) {
			foreach( $b as $bValue ) {
				if( ardeAreEquiv( $aValue, $bValue ) ) continue 2;
			}
			return false;
		}
		return true;
	}

	function ardeU32ToStr( $i ) {
		return sprintf( '%u', $i );
	}
	function ardeStrToU32( $s ) {
		return (double)$s;
	}

	function ardeSoftStrToU32( $s ) {
		$f = (double)$s;
		if( $f < 0x7fffffff ) {
			return (int)$s;
		} else {
			return $f;
		}
	}

	function ardeXmlEntities( $s ) {
		return str_replace( array( '&', '"', '<', '>' ), array( '&amp;', '&quot;', '&lt;', '&gt;' ), $s );
	}

	function ardeRedirect( $url ) {
		if( preg_match( '/cgi/', php_sapi_name() ) ) {
			echo('<html><body><script type="text/javascript"><!--'."\n");
			echo('window.location="'.$url.'";'."\n");
			echo('//--></script></body></html>');

		} else {
			header("Location: $url");

		}
		die();
	}

	function ardeRotate32Right( $num, $rotateBits ) {
		$numNoSign = $num & 0x7fffffff;
		return ( ( ( $numNoSign >> $rotateBits ) | ( $numNoSign << ( 31 - $rotateBits ) ) ) & 0x7fffffff ) | ( $num & 0x80000000 );
	}

	function ardeRotate32Left( $num, $rotateBits ) {
		$numNoSign = $num & 0x7fffffff;
		return ( ( ( $numNoSign << $rotateBits ) | ( $numNoSign >> ( 31 - $rotateBits ) ) ) & 0x7fffffff ) | ( $num & 0x80000000 );
	}

	function ardeUcs4ToUtf8( $i ) {
        if($i>=0x0800)
            return chr(0xE0|($i>>12)&0xF).chr(0x80|($i>>6)&0x3F).chr(0x80|($i&0x3F));
        if($i>=0x0080)
            return chr(0xc0|(($i>>6)&0x1F)).chr(0x80|($i&0x3F));
        return chr($i);
    }

	function ardeGetCookieDomain() {
		return null;
		if( preg_match( '/(.+):([^\:]+)$/', $_SERVER['HTTP_HOST'], $matches ) ) {
			return $matches[ 1 ];
		} elseif( strpos( $_SERVER['HTTP_HOST'], '.' ) !== false ) {
			return $_SERVER[ 'HTTP_HOST' ];
		} else {
			return null;
		}
	}

	function ardeRequestUri() {
		if( !empty( $_SERVER[ 'REQUEST_URI' ] ) ) {
			return $_SERVER[ 'REQUEST_URI' ];
		} else {
			if( !empty( $_SERVER[ 'PHP_SELF' ] ) ) {
				$s = $_SERVER[ 'PHP_SELF' ];
			} elseif( !empty( $_SERVER[ 'SCRIPT_NAME' ] ) ) {
				$s = $_SERVER[ 'SCRIPT_NAME' ];
			} else {
				return null;
			}
			if( !empty( $_SERVER['QUERY_STRING'] ) ) {
				$s .= '?'.$_SERVER[ 'QUERY_STRING' ];
			} else {
				$i = 0;
				foreach( $_GET as $k => $v ) {
					$s .= ($i?'&':'?').$k.'='.urlencode( $v );
					$i++;
				}
			}
			return $s;
		}
	}

	function ardeRemoveUrlParams( $url ) {
		if( preg_match( '/^([^\?]+)\?.*$/ ', $url, $matches ) ) {
			return $matches[1];
		} else {
			return $url;
		}
	}

	function ardeUtf8Sort( &$arr ) {
		return uasort( $arr, 'ardeUtf8Cmp' );
	}

	function ardeUtf8Cmp( $str1, $str2 ) {
		$arr1 = ardeUtf8ToUcs4( $str1 );
		$arr2 = ardeUtf8ToUcs4( $str2 );
		$i = 0;
		while( true ) {
			if( !isset( $arr1[$i] ) ) {
				if( !isset( $arr2[$i] ) ) return 0;
				return -1;
			}
			if( !isset( $arr2[$i] ) ) {
				if( !isset( $arr1[$i] ) ) return 0;
				return 1;
			}
			if( $arr1[$i] < $arr2[$i] ) return -1;
			if( $arr1[$i] > $arr2[$i] ) return 1;
			++$i;
		}
	}

	function ardeUtf8CaseCmp( $str1, $str2 ) {
		$arr1 = ardeUtf8ToUcs4( $str1 );
		$arr2 = ardeUtf8ToUcs4( $str2 );
		$i = 0;
		while( true ) {
			if( !isset( $arr1[$i] ) ) {
				if( !isset( $arr2[$i] ) ) return 0;
				return -1;
			}
			if( !isset( $arr2[$i] ) ) {
				if( !isset( $arr1[$i] ) ) return 0;
				return 1;
			}
			$res = ardeUnicodeCaseCmp( $arr1[$i], $arr2[$i] );
			if( $res != 0 ) return $res;
			++$i;
		}
	}

	function ardeUnicodeCaseCmp( $char1, $char2 ) {
		$pos1 = ardeUnicodeCharPos( $char1 );
		$pos2 = ardeUnicodeCharPos( $char2 );
		if( $pos1 >= ArdeUnicode::A && $pos1 <= ArdeUnicode::Z ) $pos1 = $pos1 + ArdeUnicode::a - ArdeUnicode::A;
		if( $pos2 >= ArdeUnicode::A && $pos2 <= ArdeUnicode::Z ) $pos2 = $pos2 + ArdeUnicode::a - ArdeUnicode::A;
		if( $pos1 == $pos2 ) return 0;
		if( $pos1 < $pos2 ) return -1;
		return 1;

	}

	class ArdeUnicode {
		const A = 0x41;
		const C = 0x43;
		const D = 0x44;
		const E = 0x45;
		const G = 0x47;
		const H = 0x48;
		const I = 0x49;
		const J = 0x4a;
		const N = 0x4e;
		const O = 0x4f;
		const U = 0x55;
		const Y = 0x59;
		const Z = 0x5a;

		const a = 0x61;
		const c = 0x63;
		const d = 0x64;
		const e = 0x65;
		const g = 0x67;
		const h = 0x48;
		const i = 0x69;
		const j = 0x6a;
		const n = 0x6e;
		const o = 0x6f;
		const u = 0x75;
		const y = 0x79;
		const z = 0x7a;
	}

	$ardeNormalChars = array(
		0xC0 => ArdeUnicode::A,
		0xC1 => ArdeUnicode::A,
		0xC2 => ArdeUnicode::A,
		0xC3 => ArdeUnicode::A,
		0xC4 => ArdeUnicode::A,
		0xC5 => ArdeUnicode::A,
		0xC6 => ArdeUnicode::A,
		0xC7 => ArdeUnicode::D,
		0xC8 => ArdeUnicode::E,
		0xC9 => ArdeUnicode::E,
		0xCA => ArdeUnicode::E,
		0xCB => ArdeUnicode::E,
		0xCC => ArdeUnicode::I,
		0xCD => ArdeUnicode::I,
		0xCE => ArdeUnicode::I,
		0xCF => ArdeUnicode::I,
		0xD0 => ArdeUnicode::D,
		0xD1 => ArdeUnicode::N,
		0xD2 => ArdeUnicode::O,
		0xD3 => ArdeUnicode::O,
		0xD4 => ArdeUnicode::O,
		0xD5 => ArdeUnicode::O,
		0xD6 => ArdeUnicode::O,
		0xD8 => ArdeUnicode::O,
		0xD9 => ArdeUnicode::U,
		0xDA => ArdeUnicode::U,
		0xDB => ArdeUnicode::U,
		0xDC => ArdeUnicode::U,
		0xDD => ArdeUnicode::Y,
		0xDF => ArdeUnicode::a,
		0xE0 => ArdeUnicode::a,
		0xE1 => ArdeUnicode::a,
		0xE2 => ArdeUnicode::a,
		0xE3 => ArdeUnicode::a,
		0xE4 => ArdeUnicode::a,
		0xE5 => ArdeUnicode::a,
		0xE6 => ArdeUnicode::a,
		0xE7 => ArdeUnicode::c,
		0xE8 => ArdeUnicode::e,
		0xE9 => ArdeUnicode::e,
		0xEA => ArdeUnicode::e,
		0xEB => ArdeUnicode::e,
		0xEC => ArdeUnicode::i,
		0xED => ArdeUnicode::i,
		0xEE => ArdeUnicode::i,
		0xEF => ArdeUnicode::i,
		0xF0 => ArdeUnicode::o,
		0xF1 => ArdeUnicode::n,
		0xF2 => ArdeUnicode::o,
		0xF3 => ArdeUnicode::o,
		0xF4 => ArdeUnicode::o,
		0xF5 => ArdeUnicode::o,
		0xF6 => ArdeUnicode::o,
		0xF8 => ArdeUnicode::o,
		0xF9 => ArdeUnicode::u,
		0xFA => ArdeUnicode::u,
		0xFB => ArdeUnicode::u,
		0xFC => ArdeUnicode::u,
		0xFD => ArdeUnicode::y,
		0xFF => ArdeUnicode::y,
		0x100 => ArdeUnicode::A,
		0x101 => ArdeUnicode::a,
		0x102 => ArdeUnicode::A,
		0x103 => ArdeUnicode::a,
		0x104 => ArdeUnicode::A,
		0x105 => ArdeUnicode::a,
		0x106 => ArdeUnicode::C,
		0x107 => ArdeUnicode::c,
		0x108 => ArdeUnicode::C,
		0x109 => ArdeUnicode::c,
		0x10A => ArdeUnicode::C,
		0x10B => ArdeUnicode::c,
		0x10C => ArdeUnicode::C,
		0x10D => ArdeUnicode::c,
		0x10E => ArdeUnicode::D,
		0x10F => ArdeUnicode::d,
		0x110 => ArdeUnicode::D,
		0x111 => ArdeUnicode::d,
		0x112 => ArdeUnicode::E,
		0x113 => ArdeUnicode::e,
		0x114 => ArdeUnicode::E,
		0x115 => ArdeUnicode::e,
		0x116 => ArdeUnicode::E,
		0x117 => ArdeUnicode::e,
		0x118 => ArdeUnicode::E,
		0x119 => ArdeUnicode::e,
		0x11A => ArdeUnicode::E,
		0x11B => ArdeUnicode::e,
		0x11C => ArdeUnicode::G,
		0x11D => ArdeUnicode::g,
		0x11E => ArdeUnicode::G,
		0x11F => ArdeUnicode::g,
		0x120 => ArdeUnicode::G,
		0x121 => ArdeUnicode::g,
		0x122 => ArdeUnicode::G,
		0x123 => ArdeUnicode::g,
		0x124 => ArdeUnicode::H,
		0x125 => ArdeUnicode::h,
		0x126 => ArdeUnicode::H,
		0x127 => ArdeUnicode::h


	);



	function ardeUnicodeCharPos( $char ) {
		global $ardeNormalChars;
		if( isset( $ardeNormalChars[ $char ] ) ) return $ardeNormalChars[ $char ];

		return $char;
	}

	function ardeUtf8ToLower( $str ) {
		$o = '';
		$w = ardeUtf8ToUcs4( $str );
		foreach( $w as $wc ) {
			if( $wc >= 0x41 && $wc <= 0x5a ) {
				$o .= ardeUcs4ToUtf8( $wc + 0x20 );
			} else {
				$o .= ardeUcs4ToUtf8( $wc );
			}
		}
		return $o;
	}



	function ardeUtf8ToUcs4( &$str )
	{
	  $mState = 0;    
	  $mUcs4  = 0;    
	  $mBytes = 1;    

	  $out = array();

	  $len = strlen($str);
	  for($i = 0; $i < $len; $i++) {
	    $in = ord($str{$i});
	    if (0 == $mState) {
	      if (0 == (0x80 & ($in))) {
	        $out[] = $in;
	        $mBytes = 1;
	      } else if (0xC0 == (0xE0 & ($in))) {
	        $mUcs4 = ($in);
	        $mUcs4 = ($mUcs4 & 0x1F) << 6;
	        $mState = 1;
	        $mBytes = 2;
	      } else if (0xE0 == (0xF0 & ($in))) {
	        $mUcs4 = ($in);
	        $mUcs4 = ($mUcs4 & 0x0F) << 12;
	        $mState = 2;
	        $mBytes = 3;
	      } else if (0xF0 == (0xF8 & ($in))) {
	        $mUcs4 = ($in);
	        $mUcs4 = ($mUcs4 & 0x07) << 18;
	        $mState = 3;
	        $mBytes = 4;
	      } else if (0xF8 == (0xFC & ($in))) {
	        $mUcs4 = ($in);
	        $mUcs4 = ($mUcs4 & 0x03) << 24;
	        $mState = 4;
	        $mBytes = 5;
	      } else if (0xFC == (0xFE & ($in))) {
	        $mUcs4 = ($in);
	        $mUcs4 = ($mUcs4 & 1) << 30;
	        $mState = 5;
	        $mBytes = 6;
	      } else {
	        return false;
	      }
	    } else {
	      if (0x80 == (0xC0 & ($in))) {
	        $shift = ($mState - 1) * 6;
	        $tmp = $in;
	        $tmp = ($tmp & 0x0000003F) << $shift;
	        $mUcs4 |= $tmp;

	        if (0 == --$mState) {

	          if (((2 == $mBytes) && ($mUcs4 < 0x0080)) ||
	              ((3 == $mBytes) && ($mUcs4 < 0x0800)) ||
	              ((4 == $mBytes) && ($mUcs4 < 0x10000)) ||
	              (4 < $mBytes) ||
	              (($mUcs4 & 0xFFFFF800) == 0xD800) ||
	              ($mUcs4 > 0x10FFFF)) {
	            return false;
	          }
	          if (0xFEFF != $mUcs4) {
	            $out[] = $mUcs4;
	          }
	          $mState = 0;
	          $mUcs4  = 0;
	          $mBytes = 1;
	        }
	      } else {
	        return false;
	      }
	    }
	  }
	  return $out;
	}

?>