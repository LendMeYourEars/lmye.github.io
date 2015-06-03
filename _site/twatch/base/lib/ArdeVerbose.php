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

	require_once $ardeBase->path( 'lib/ArdePrinter.php' );

	class ArdeVerbose extends ArdePrinter {
		public static $global;

		private $p;
		private $isXml;
		private $level;

		private $originalLevel;

		private $nestedPaneCount = 0;



		public $printStyleAndScript = true;

		function __construct( ArdePrinter $output, $isXml = false, $level = null ) {
			$this->p = $output;
			$this->isXml = $isXml;
			if( $level === null ) {
				if( isset( $_GET[ 'verbose' ] ) ) $this->level = $_GET[ 'verbose' ];
				else $this->level = 0;
			} else {
				$this->level = $level;
			}
		}

		public static function makeGlobalObject( ArdePrinter $output, $isXml = false, $level = null ) {
			ArdeVerbose::$global = new ArdeVerbose( $output, $isXml, $level );
		}

		public function m( $message, $level = 0 ) {
			if( !is_int( $level ) ) throw new ArdeException( 'level should be integer' );
			if( $level > $this->level ) return;
			$this->printBox( $message );
		}

		public function pv( $title, &$var, $level = 0 ) {
			if( !is_int( $level ) ) throw new ArdeException( 'level should be integer' );
			if( $level > $this->level ) return;
			$this->printBox( $title.': '.self::toString( $var ) );
		}

		public function pvex( $title, &$var, $level = 0 ) {
			if( !is_int( $level ) ) throw new ArdeException( 'level should be integer' );
			if( $level > $this->level ) return;

			if( is_object( $var ) && method_exists( $var, 'ardeVerboseExtend' ) ) {
				$this->o( $title.': '.self::toString( $var ) );
				$var->ardeVerboseExtend( $this );
				$this->c();
			} elseif( is_array( $var ) ) {
				$this->o( $title.': '.self::toString( $var ) );
				$this->expandArray( $var );
				$this->c();
			} else {

				$this->m( $title.': '.self::toString( $var ) );
			}
		}

		public static function trim( $s, $maxLength = 40 ) {
			if( strlen( $s ) > $maxLength ) {
				return substr( $s, 0, $maxLength - 3 ).'...';
			} else {
				return $s;
			}
		}

		public function expandArray( $a ) {
			foreach( $a as $key => $value ) {
				$this->pvex( $key, $value );
			}
		}

		public function o( $title, $level = 0 ) {
			if( !is_int( $level ) ) throw new ArdeException( 'level should be integer' );
			if( $level > $this->level ) return;
			++$this->nestedPaneCount;
			$this->openExpandableBox( $title );
		}

		public function c( $level = 0, $message = '' ) {
			if( !is_int( $level ) ) throw new ArdeException( 'level should be integer' );
			if( $level > $this->level ) return;
			--$this->nestedPaneCount;
			$this->closeExpandableBox( $message );
		}

		public static function printStyleAndScript( ArdePrinter $p ) {
			$p->pl( '<style>' );
			$p->pl( "	/* automatically written by ArdeVerbose */", 0 );
			self::printStyle( $p );
			$p->relnl();
			$p->pl( '</style>' );
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
			$p->pl( "	/* automatically written by ArdeVerbose */", 0 );
			self::printScript( $p );
			$p->relnl();
			$p->pn( '/*]]>*/</script>' );
		}

		public static function printStyle( ArdePrinter $p ) {
			$p->pl( '.arde_verbose_box, .arde_verbose_open {' );
			$p->pl( '	border: 1px solid #000;' );
			$p->pl( '	background: #eee;' );
			$p->pl( '	color:#000;' );
			$p->pl( '	padding:2px 10px;' );
			$p->pl( '	margin: 10px;' );
			$p->pl( '	font-family: courier new;' );
			$p->pl( '}' );
			$p->pl( '.arde_verbose_box {' );
			$p->pl( '	border: 1px solid #aaa;' );
			$p->pl( '}' );
			$p->pl( '.arde_verbose_box title, .arde_verbose_open title {' );
			$p->pl( '	color: #005;' );
			$p->pl( '}' );
			$p->pl( '.arde_verbose_open {' );
			$p->pl( '	background: #eef8e0;' );
			$p->pl( '	color: #000;' );
			$p->pl( '}' );
			$p->pl( '.arde_verbose_open.even {' );
			$p->pl( '	background: #e0ead5;' );
			$p->pl( '}' );
			$p->pl( '.arde_verbose_open a {' );
			$p->pl( '	background:#676;' );
			$p->pl( '	color:#fff;' );
			$p->pl( '	text-decoration:none;' );
			$p->pl( '	font-family:courier;' );
			$p->pl( '	font-weight:bold;' );
			$p->pl(	'	font-size:1.2em;' );
			$p->pl( '	padding-left:5px;' );
			$p->pl( '	padding-right:5px;' );
			$p->pl( '}' );
			$p->pl( '.arde_verbose_close {' );
			$p->pl( '	font-family:courier new;' );
			$p->pl( '}' );
			$p->pl( '.arde_verbose_pane {' );
			$p->pl( '	display: none;' );
			$p->pl( '	padding: 5px;' );
			$p->pl( '	background: #ddeed0;' );
			$p->pl( '	color:#000;' );
			$p->pl( '	margin: 10px;' );
			$p->pl( '	margin-top: -11px;' );
			$p->pl( '	border: 1px solid #000;' );
			$p->pl( '	border-top: 0px solid #aba;' );
			$p->pl( '}' );
			$p->pl( '.arde_verbose_pane.even {' );
			$p->pl( '	background: #bbccb0;' );
			$p->pn( '}' );

		}

		public static function  printScript( ArdePrinter $p ) {
			$p->pl( 'function ardeVerboseExpand( id ) {' );
			$p->pl( "	var pane = document.getElementById( 'arde_verbose_p'+id );" );
			$p->pl( '	var display = pane.style.display;' );
			$p->pl( "	if( !display ) display = 'none';" );
			$p->pl( "	pane.style.display = ( display == 'none' ? 'block' : 'none' );" );
			$p->pl( "	var button = document.getElementById( 'arde_verbose_b'+id );" );
			$p->pl( "	button.firstChild.nodeValue = ( display == 'none' ? '-' : '+' );" );
			$p->pl( '}' );
		}

		private $verboseTagCount = 0;

		private function printBox( $str ) {

			$this->p->nl();
			if( $this->isXml ) {
				if( $this->verboseTagCount == 0  ) {
					$this->p->pl( '<verbose><![CDATA[', 1 );
				}
				++$this->verboseTagCount;
			} elseif( $this->printStyleAndScript ) {
				$this->printStyleAndScript = false;
				self::printStyleAndScript( $this->p );
				$this->p->nl();
			}

			$this->p->pl( '<div class="arde_verbose_box">', 1 );

			$this->p->pl( ardeXmlEntities( $str ) );
			$this->p->rel();
			$this->p->pn( '</div>' );

			if( $this->isXml ) {
				if( $this->verboseTagCount == 1 ) {
					$this->p->relnl();
					$this->p->pn( ']]></verbose>' );
				}
				--$this->verboseTagCount;
			}
			$this->p->nl();
		}

		private function openExpandableBox( $str ) {
			$this->p->nl();

			if( $this->isXml ) {
				if( $this->verboseTagCount == 0 ) {
					$this->p->pl( '<verbose><![CDATA[', 1 );
				}
				++$this->verboseTagCount;
			} elseif( $this->printStyleAndScript ) {
				$this->printStyleAndScript = false;
				self::printStyleAndScript( $this->p );
				$this->p->nl();
			}

			$this->p->pl( '<div class="arde_verbose_open'.( $this->nestedPaneCount % 2 == 0 ?' even':'' ).'">', 1 );
			$rnd = rand( 999, 99999999 );

			$this->p->pl( ardeXmlEntities( $str ) );
			$this->p->pl( '<a id="arde_verbose_b'.$rnd.'" href="#" onclick="ardeVerboseExpand(\''.$rnd.'\');return false;">+</a> ' );
			++$this->buttonIndex;
			$this->p->rel();
			$this->p->pl( '</div>' );
			$this->p->pl( '<div id="arde_verbose_p'.$rnd.'" class="arde_verbose_pane'.( $this->nestedPaneCount % 2 == 0 ?' even':'' ).'">', 1 );
		}

		private function closeExpandableBox( $str = '' ) {
			if( !empty( $str ) ) {
				$this->p->pl( '<div class="arde_verbose_close">', 1 );
				$this->p->pl( ardeXmlEntities( $str ) );
				$this->p->rel();
				$this->p->pl( '</div>' );
			}
			$this->p->rel();
			$this->p->pn( '</div>' );

			if( $this->isXml && $this->verboseTagCount == 1 ) {
				$this->p->relnl();
				$this->p->pn( ']]></verbose>' );
				$this->verboseTagCount = 0;
			} else {
				--$this->verboseTagCount;
			}
			$this->p->nl();
		}

		public static function toString( &$var ) {
			if( !isset( $var ) ) {
				return '[not set]';
			} elseif( is_object( $var ) ) {
				if( method_exists( $var, 'ardeVerboseString' ) ) return $var->ardeVerboseString();
				else return "[".get_class( $var )."]";
			} elseif( is_float( $var ) ) {
				return sprintf( '%.10f (float)', $var );
			} elseif( is_bool( $var ) ) {
				return ( $var?'true':'false' ).' (bool)';
			} elseif( is_null( $var ) ) {
				return '[null]';
			} elseif( is_array( $var ) ) {
				return 'array ('.count( $var ).' members)';
			} elseif( is_string( $var ) ) {
				return "'".$var."'";
			} else {
				return $var;
			}
		}

		public function temporaryLevel( $level ) {
			$this->originalLevel = $this->level;
			$this->level = $level;
		}

		public function revertLevel() {
			$this->level = $this->originalLevel;
		}
	}

	function ardeO( $title, $level = 0 ) {
		if( ArdeVerbose::$global != null ) ArdeVerbose::$global->o( $title, $level );
	}
	function ardeC( $level = 0, $message = '' ) {
		if( ArdeVerbose::$global != null ) ArdeVerbose::$global->c( $level, $message );
	}
	function ardeM( $message, $level = 0 ) {
		if( ArdeVerbose::$global != null ) ArdeVerbose::$global->m( $message, $level );
	}
	function ardePv( $title, &$var, $level = 0 ) {
		if( ArdeVerbose::$global != null ) ArdeVerbose::$global->pv( $title, $var, $level );
	}
	function ardePvex( $title, &$var, $level = 0 ) {
		if( ArdeVerbose::$global != null ) ArdeVerbose::$global->pvex( $title, $var, $level );
	}
?>