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
	
	require_once dirname(__FILE__).'/General.php';
	require_once dirname(__FILE__).'/ArdePrinter.php';
	require_once dirname(__FILE__).'/ArdeSerializer.php';;
	
	class ArdeException extends Exception implements ArdeSerializable {
		
		const ERROR = 1;
		const WARNING = 2;
		const USER_ERROR = 3;
		
		private static $typeStrings = array( 
			ArdeException::ERROR => 'error',
			ArdeException::WARNING => 'warning',
			ArdeException::USER_ERROR => 'user_error'
		);
		
		protected $class = 'Error';
		protected $type = ArdeException::ERROR;
		
		public $safeExtras = array();
		public $extras = array();		
		
		protected $child;
		
		private static $reporter = null;
		
		public function getType() {
			return $this->type;
		}
		
		public static function startErrorSystem( ArdeUncaughtExceptionHandler $handler = null, ArdeErrorReporter $reporter ) {
			set_error_handler( 'ArdeErrorException::errorToException', E_ALL & ~E_STRICT );
			if( $handler == null ) $handler = new ArdeSimpleUncaughtExceptionHandler();
			set_exception_handler( array( $handler, 'uncaughtExceptionHandler' ) );	
			self::setGlobalReporter( $reporter );
		}
		
		public static function restoreErrorSystem() {
			restore_error_handler();
			restore_exception_handler();
		}

		public static function reportError( ArdeException $e ) {
			if( !self::$reporter instanceof ArdeErrorReporter ) {
				die( "Global errorReporter is not set or is invalid, don't know what to do with this error => ".$e->__toString() );
			}
			
			self::$reporter->reportError( $e );
		}
		
		public static function setGlobalReporter( ArdeErrorReporter $reporter ) {
			self::$reporter = $reporter;
		}
		
		public static function getGlobalReporter() {
			return self::$reporter;
		}
		
		public function __construct( $message, $code = 0, ArdeException $child = null, $extra = '' ) {
			parent::__construct( $message, $code );
			$this->child = $child;
			if( !empty( $extra ) ) $this->extras[] = $extra;
		}
		
		protected function _getTrace() {
			return $this->getTrace();
		}
		
		protected function _getLine() {
			return $this->getLine();
		}
		
		protected function _getFile() {
			return $this->getFile();
		}
		
		private function getTypeString() {
			return self::$typeStrings[ $this->type ];
		}
		
		public static function printStyle( ArdePrinter $p ) {
			$p->pl( '.arde_exception {' );
			$p->pl( '	text-align:left;' );
			$p->pl( '	font-family:arial;' );
			$p->pl( '	border:1px solid #000;' );
			$p->pl( '	padding-bottom:10px;' );
			$p->pl( '	margin:10px;' );
			$p->pn( '}' );
			$p->pl( '.arde_exception.error {' );
			$p->pl( '	color:#fff;' );
			$p->pl( '	background:#900;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception.user_error {' );
			$p->pl( '	color:#fff;' );
			$p->pl( '	background:#907;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception.warning {' );
			$p->pl( '	color:#000;' );
			$p->pl( '	background:#fe3;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception .head {' );
			$p->pl( '	padding:4px 10px;' );
			$p->pl( '	font-weight:bold;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception .head a {' );
			$p->pl( '	color:#000;' );
			$p->pl( '	text-decoration:none;' );
			$p->pl( '	font-family:courier;' );
			$p->pl( '	font-weight:bold;' );
			$p->pl(	'	font-size:1.2em;' );
			$p->pl( '	padding-left:5px;' );
			$p->pl( '	padding-right:5px;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception.error .head {' );
			$p->pl( '	color:#fff;' );
			$p->pl( '	background:#500;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception.error .head a {' );
			$p->pl( '	background:#c00;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception.warning .head {' );
			$p->pl( '	color:#fff;' );
			$p->pl( '	background:#a85;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception.warning .head a {' );
			$p->pl( '	background:#fff;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception.user_error .head {' );
			$p->pl( '	color:#fff;' );
			$p->pl( '	background:#505;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception.user_error .head a {' );
			$p->pl( '	background:#c8c;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception .message {' );
			$p->pl( '	padding:2px 10px;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception .under_message {' );
			$p->pl( '	padding:2px 10px;' );
			$p->pl( '	font-size:.8em;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception .under_message em {' );
			$p->pl( '	color:#fff;' );
			$p->pl( '	font-weight:bold;' );
			$p->pl( '	font-style:normal;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception.warning .under_message em {' );
			$p->pl( '	color:#000;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception.error .under_message {' );
			$p->pl( '	color:#eee;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception.warning .under_message {' );
			$p->pl( '	color:#444;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception .extra {' );
			$p->pl( '	padding:2px 10px;' );
			$p->pl( '	margin:2px 10px;' );
			$p->pl( '	background:#e8dddd;' );
			$p->pl( '	color:#000;' );
			$p->pl( '	font-family:courier new;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception .extended {' );
			$p->pl( '	display:none;' );
			$p->pl( '	padding:2px 10px;' );
			$p->pl( '	margin:10px;' );
			$p->pl( '	background:#eee;' );
			$p->pl( '	color:#000;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception .trace {' );
			$p->pl( '	margin: 5px 1px;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception .function {' );
			$p->pl( '	font-family:courier new;' );
			$p->pl( '	background: #fff;' );
			$p->pl( '	padding:2px 10px;' );
			$p->pl( '	font-size: 1.1em;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception .arg {' );
			$p->pl( '	color:#33a;' );
			$p->pl( '	font-weight:normal;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception .file {' );
			$p->pl( '	font-size:.8em;' );
			$p->pl( '	color:#555;' );
			$p->pl( '	padding:2px 10px;' );
			$p->pl( '	padding-bottom: 5px;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception .file em {' );
			$p->pl( '	font-weight: bold;' );
			$p->pl( '	color:#000;' );
			$p->pl( '	font-style: normal;' );
			$p->pl( '}' );
			$p->pl( '.arde_exception .cls {' );
			$p->pl( '	color:#040;' );
			$p->pl( '}' );
		}
		public static function printScript( ArdePrinter $p ) {
			$p->pl( 'function ardeExceptionExpand( id ) {' );
			$p->pl( "	var pane = document.getElementById( 'arde_exception_p'+id );" );
			$p->pl( '	var display = pane.style.display;' );
			$p->pl( "	if( !display ) display = 'none';" );
			$p->pl( "	pane.style.display = ( display == 'none' ? 'block' : 'none' );" );
			$p->pl( "	var button = document.getElementById( 'arde_exception_b'+id );" );
			$p->pl( "	button.firstChild.nodeValue = ( display == 'none' ? '-' : '+' );" );
			$p->pl( '}' );
		}
		
		public function printXml( ArdePrinter $p, $tagName, $muted = false, $extraAttrib = '' ) {

			if( $muted ) {
				if( $this->type == self::USER_ERROR ) {
					$class = "There is a problem";
				} else {
					$class = 'Some Error Occurred';
				}
			} else {
				$class = $this->class;
			}
			$p->pl( '<'.$tagName.' type="'.$this->getTypeString().'" muted="'.($muted?'true':'false').'" class="'.$class.'" '.($muted?'':'line="'.$this->_getLine().'" code="'.$this->getCode().'"').$extraAttrib.'>', 1 );

			if( $muted && $this->type != self::USER_ERROR ) {
				$message = 'Some Error Occured';
			} else {
				$message = $this->getMessage();
			}
			if( !$muted || $this->type == self::USER_ERROR ) {
				$p->pl( '<message>'.ardeXmlEntities( $message ).'</message>' );
			}
			
			if( !$muted ) {
				$p->pl( '<file>'.ardeXmlEntities( $this->_getFile() ).'</file>' );

				$traces = $this->_getTrace();
				foreach( $traces as $trace ) {
					$s = '<trace';
					if( isset( $trace['function'] ) ) $s .= ' function="'.$trace['function'].'"';
					if( isset( $trace['class'] ) ) $s .= ' class="'.$trace['class'].'"';
					if( isset( $trace['type'] ) ) $s .= ' type="'.$trace['type'].'"';
					$s .= '>';
					$p->pl( $s, 1 );
					if( isset( $trace['file'] ) ) {
						$line = isset( $trace['line'] )?'line="'.$trace['line'].'" ':'';
						$p->pl( '<file '.$line.'>'.ardeXmlEntities( $trace['file'] ).'</file>' );
					}
					if( isset( $trace['args'] ) ) {
						foreach( $trace['args'] as $arg ) {
							$p->pl( '<arg>'.ardeXmlEntities( self::argToString( $arg ) ).'</arg>' );
						}
					}
					$p->rel();
					$p->pl( '</trace>' );				
				}
			}
			
			
			$p->pl( '<extras>', 1 );
			foreach( $this->safeExtras as $extra ) {
				$p->pl( '<extra>'.ardeXmlEntities( $extra ).'</extra>' );
			}
			if( !$muted ) {
				foreach( $this->extras as $extra ) {
					$p->pl( '<extra>'.ardeXmlEntities( $extra ).'</extra>' );
				}
			}
			$p->rel();
			$p->pl( '</extras>' );
			
			if( !$muted && $this->child !== null ) {
				$this->child->printXml( $p, 'child' );
				$p->nl();	
			}
			$p->rel();
			$p->pl( '</'.$tagName.'>' );
		}
		
		public function printXhtml( ArdePrinter $p, $muted = false ) {
			try {
				$p->pl( '<div class="arde_exception '.$this->getTypeString().'">' );
				$p->hold( 1 );
				
				$rnd = rand( 999, 99999999 );
				$trace = $this->_getTrace();
				
				$p->pn( '<div class="head">' );
				if( !$muted && count( $trace ) ) {
					$p->pn( '<a class="button" id="arde_exception_b'.$rnd.'" href="#" onclick="ardeExceptionExpand(\''.$rnd.'\');return false;">+</a> ' );
				}
				
				if( $muted ) {
					if( $this->type == self::USER_ERROR ) {
						$class = "There is a problem";
					} else {
						$class = 'Some Error Occurred';
					}
				} else {
					$class = $this->class;
				}
				
				$p->pl( $class.'</div>' );
				
				if( !$muted || $this->type == self::USER_ERROR ) {
					$p->pn( '<div class="message">' );
					$p->pm( ardeXmlEntities( $this->getMessage() ) );
					if( $this->getCode() != 0 ) {
						$p->pn( ' ['.$this->getCode().']' );
					}
					$p->pl( '</div>' );
				}
				
				if( !$muted ) {
					$filename = basename( $this->_getFile() );
					$path = substr( $this->_getFile(), 0, strlen( $this->_getFile() ) - strlen( $filename ) );
					$p->pl( '<div class="under_message"> File: '.$path.'<em>'.$filename.'</em> Line: <em>'.$this->_getLine().'</em>'.'</div>' );
				}
				
				foreach( $this->safeExtras as $safeExtra ) {
					$p->pn(	'<div class="extra">' );
					$p->pm( ardeXmlEntities( $safeExtra ) );
					$p->pl( '</div>' );
				}		
				if( !$muted ) {
					foreach( $this->extras as $extra ) {
						$p->pn(	'<div class="extra">' );
						$p->pm( ardeXmlEntities( $extra ) );
						$p->pl( '</div>' );
					}
				}
				
				if( !$muted && count( $trace ) ) {
	
					$p->pl( '<div class="extended" id="arde_exception_p'.$rnd.'">', 1 );
					foreach( $trace as $t ) {
						$ts = '';
						if( isset( $t['function'] ) ) {
							$ts .= '<div class="function">';
							if( isset( $t['class'] ) ) {
								$ts .= '<span class="cls">'.$t['class'].'</span>';
								$ts .= $t['type'];
							}
							$ts .= $t['function'].'( ';
							if( isset( $t['args'] ) ) {
								$i = 0;
								foreach( $t['args'] as $arg ) {
									$ts .= ($i?', ':'').'<span class="arg">'.self::argToString( $arg ).'</span>';
									++$i;
								}
							}
							$ts .= ' )</div>';
							if( isset( $t['file'] ) ) { 
								$ts .= '<div class="file">';
								$filename = basename( $t['file'] );
								$path = substr( $t['file'], 0, strlen( $t['file'] ) - strlen( $filename ) );
								$ts .= ' called in File: '.$path.'<em>'.$filename.'</em> Line: <em>'.$t['line'].'</em>';
								$ts .= '</div>';
							}
						}
						$p->pl( '<div class="trace">'.$ts.'</div>' );
						
					}
					
					$p->rel();
					$p->pl( '</div>' );
				}
				
				if( !$muted && $this->child !== null ) {
					$this->child->printXhtml( $p );
					$p->nl();
				}
				$p->rel();
				$p->pn( '</div>' );
			} catch( Exception $e ) {
				
				echo $e->__toString();
			}
		}
		
		const MAX_ARG_LENGTH = 511;
		
		private static function argToString( $arg ) {
			if( is_string( $arg ) ) {
				if( strlen( $arg ) > self::MAX_ARG_LENGTH ) {
					$argStr = "'".substr( $arg, 0, self::MAX_ARG_LENGTH )."'...";
				} else {
					$argStr = "'".$arg."'";
				} 
			}
			elseif( is_bool( $arg ) ) $argStr = $arg?'true':'false';
			elseif( is_int( $arg ) || is_float( $arg ) ) $argStr = (string)$arg;
			elseif( is_object( $arg ) ) {
				if( $arg instanceof ArdeSerializedArg ) {
					$argStr = $arg->str;
				} else {
					$argStr = 'object ['.get_class( $arg ).']';
				}
			}
			elseif( is_null( $arg ) ) $argStr = 'null';
			else $argStr = '[unknown]';
			return $argStr;
		}
		
			
		protected function _p_toString( $muted ) {
			try {
				if( $muted ) {
					if( $this->type == self::USER_ERROR ) {
						$s = 'There is a problem: '.$this->getMessage();
					} else {
						$s = 'Some Error Occurred';
					}
				} else {
					$s = "\nERROR: [".$this->getMessage()."]";
					$s .= " Line: ".$this->_getLine();
					$s .= " File: ".$this->_getFile();
					
					$trace = $this->_getTrace();
					foreach( $trace as $t ) {
						$s .= "\n".' - ';
						if( isset( $t['class'] ) ) {
							$s .= $t['class'];
							if( isset( $t['type'] ) ) $s .= $t['type']; 
						}
						if( isset( $t['function'] ) ) {
							$s .= $t['function'];
							$s .= '( ';
							if( isset( $t['args'] )  ) {
								$i = 0;
								foreach( $t[ 'args' ] as $arg ) {
									$s .= ($i?', ':'').self::argToString( $arg );
									++$i;
								}
							}
							$s .= ' )';
						}
						
						$s .= ' ';
						if( isset( $t['line'] ) ) $s .= 'line: '.$t['line'].' ';
						if( isset( $t['file'] ) ) $s .= 'file: '.$t['file'];
					}
					foreach( $this->extras as $extra ) {
						$s .= "\n".' - '.$extra;
					}
				}
				
				foreach( $this->safeExtras as $safeExtra ) {
					$s .= "\n".' - '.$safeExtra;
				}		

				return $s."\n";
			} catch( Exception $e ) {
				echo $e->getMessage();
			}	
		}

		public function __toString() {
			return $this->_p_toString( false );
		}
		
		public function __mutedToString() {
			return $this->_p_toString( true );
		}
		
		public static function makeSerialObject( ArdeSerialData $d ) {
			return new ArdeSerializedException( $d->data[0], $d->data[1], $d->data[2], $d->data[3], $d->data[4], $d->data[5], $d->data[6], $d->data[7], $d->data[8], $d->data[9] );
		}
		
		public function getSerializableTrace() {
			$traces = $this->_getTrace();
			foreach( $traces as &$trace ) {
				if( isset( $trace['args'] ) ) {
					foreach( $trace['args'] as $k => $v ) {
						$trace['args'][$k] = self::argToString( $trace['args'][$k] );
					}
				}
			}
			return $traces;
		}
		
		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->class, $this->type, $this->getMessage(), $this->_getLine(), $this->_getFile(), $this->getCode(), $this->child, $this->extras, $this->safeExtras, $this->getSerializableTrace() ) );
		}
	}
	
	class ArdeSimpleUncaughtExceptionHandler implements ArdeUncaughtExceptionHandler {

		public function uncaughtExceptionHandler( $exception ) {	
			try {
				if( $exception instanceof ArdeException ) {
					ArdeException::reportError( $exception );
				} else {
					echo $exception->__toString();
				}
			} catch( Exception $e ) {
				echo $e->__toString();
			}
		}
	}
	
	class ArdeSerializedArg {
		public $str;
		function __construct( $str ) {
			$this->str = $str;
		}
	}
	
	class ArdeSerializedException extends ArdeException {
		private $backTrace;
		
		function __construct( $class, $type, $message, $line, $file, $code, $child, $extras, $safeExtras, $backTrace ) {
			$this->class = $class;
			$this->type = $type;
			$this->line = $line;
			$this->file = $file;
			$this->extras = $extras;
			$this->safeExtras = $safeExtras;
			parent::__construct( $message, $code, $child, '' );
			$this->backTrace = $backTrace;
			foreach( $this->backTrace as &$trace ) {
				if( isset( $trace['args'] ) ) {
					foreach( $trace['args'] as $k => $v ) {
						$trace['args'][$k] = new ArdeSerializedArg( $trace['args'][$k] );
					}
				}
			}
		}
		
		protected function _getTrace() {
			return $this->backTrace;
		}
		
		protected  function _getLine() {
			return $this->line;
		}
		
		protected function _getFile() {
			return $this->file;
		}
	}
	
	class ArdeErrorException extends ArdeException {
		
		protected $class = 'PHP Error';
		
		private $errorLine;
		private $errorFile;
		
		function __construct( $message, $code, $errorLine, $errorFile ) {
			parent::__construct( $message, $code );
			$this->errorLine = $errorLine;
			$this->errorFile = $errorFile;
		}
		
		public static function errorToException( $errno, $errstr, $errfile, $errline, $errcontext ) {
			if( !(error_reporting() & $errno) ) return;
			throw new ArdeErrorException( $errstr, $errno, $errline, $errfile );
		}
		
		protected  function _getLine() {
			return $this->errorLine;
		}
		
		protected function _getFile() {
			return $this->errorFile;
		}
		
		protected function _getTrace() {
			$traces = $this->getTrace();
			unset( $traces[0] );
			return $traces;
		}
	}
	
	interface ArdeErrorReporter {
		public function reportError( ArdeException $exception );
	}
	
	class ArdeDummyErrorReporter implements ArdeErrorReporter {
		public function reportError( ArdeException $exception ) {}
	}
	
	interface ArdeUncaughtExceptionHandler {
		public function uncaughtExceptionHandler( $e );
	}
	
	class ArdeWarning extends ArdeException {
		protected $type = ArdeException::WARNING;
		protected $class = 'Warning';
	}
	
	class ArdeUserError extends ArdeException {
		protected $class = "User Error"; 
		protected $type = ArdeException::USER_ERROR;
	}
	
	

?>