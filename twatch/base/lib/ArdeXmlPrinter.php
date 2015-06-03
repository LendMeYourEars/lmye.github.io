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
    
	require_once dirname(__FILE__).'/ArdePrinter.php';
	
	

	class ArdeXmlPrinter extends ArdePrinter implements ArdeErrorReporter, ArdeUncaughtExceptionHandler {
		
		const IN_TEXT = 0;
		const IN_TAG = 1;
		const IN_TAG_NAME = 2;
		const IN_CDATA = 3; 
		
		public $xmlLangCode = 'en-US';
		
		private static $crossSafeXhtmlTags = array( 'body' => true, 'div' => true, 'td' => true );
		
		private $in = array();
		
		private $isXhtml;
		private $isStrict;
		
		private $active = true;

		private $state = ArdeXmlPrinter::IN_TEXT;
		
		
		private $tag = '';
		private $tagName = '';
		private $tagStartOper = '';
		private $tagEndOper = '';		
		private $tagPrevChar = '';
		
		
		public $crossPrinter;
		
		public $crossPrinting = false;
		
		private $parseCrossPrints;
		
		public $printErrorStyleAndScript = true;
		
		
		public $footer;
		
		
		private $crossSafe = false;	
		
		private $bodyTagName = 'body';
		
		private $doctypeTagPrinted = false;
		private $htmlTagPrinted = false;
		private $htmlTagClosed = false;
		private $bodyTagPrinted = false;
		private $bodyTagClosed = false;
		
		

		function __construct( $isXhtml = false, $isStrict = true, $xmlRootTagName = 'body', $parseCrossPrints = false ) {
			$this->isXhtml = $isXhtml;
			$this->isStrict = $isStrict;
			if( $this->isXhtml ) {
				$this->bodyTagName = 'body';
			} else {
				$this->bodyTagName = $xmlRootTagName;
			}
			$this->crossPrinter = new ArdeXmlCrossPrinter( $this );
			$this->footer = new ArdeJobPrinter();
			$this->parseCrossPrints = $parseCrossPrints;
		}
	
		public function isCrossSafe() {
			return $this->crossSafe;
		}
		
		protected function o( $s ) {
			if( !$this->active || ( !$this->parseCrossPrints && $this->crossPrinting ) ) return parent::o( $s );
			$i = 0;
			
			$out = '';
			
			while( isset( $s[$i] ) ) {
				if( $this->state == ArdeXmlPrinter::IN_TEXT ) {
					if( $s[$i] == '<' ) {
						if( substr( $s, $i, 9 ) == '<![CDATA[' ) {
							$out .= '<![CDATA[';
							$i += 8;
							$this->state = ArdeXmlPrinter::IN_CDATA;
						} else {				
							$this->tag = '<';
							$this->tagName = '';
							$this->tagStartOper = '';
							$this->tagEndOper = '';		
							$this->tagPrevChar = '';
							
							++$i;
							if( isset( $s[$i] ) && ( $s[$i]=='?' || $s[$i]=='/' || $s[$i]=='!' ) ) {
								$this->tag .= $this->tagStartOper = $s[ $i ];
							} else {
								--$i;
							}
							$this->state = ArdeXmlPrinter::IN_TAG_NAME;
							
						}
					} elseif( $this->isStrict && $s[$i] == '>' ) {
							return $this->error( 'unexpected > character', $i, $out );
					} else {
						$out .= $s[$i];
					}
				} elseif( $this->state == ArdeXmlPrinter::IN_CDATA ) {
					if( $s[$i] == ']' && isset( $s[$i+1] ) && $s[$i+1] == ']' && isset( $s[$i+2] ) && $s[$i+2] == '>' ) {
						$out .= ']]>';
						$i += 2;
						$this->state = ArdeXmlPrinter::IN_TEXT;
					} else {
						$out .= $s[$i];
					}
				} elseif( $this->state == ArdeXmlPrinter::IN_TAG_NAME ) {
					if( self::isWord( $s[$i] ) ) {
						$this->tagName .= $s[ $i ];
						$this->tag .= $this->prevChar = $s[ $i ];
					} else {
						if( empty( $this->tagName ) && $this->isStrict ) return $this->error( 'tag name expected', $i, $out );
						$this->state = ArdeXmlPrinter::IN_TAG;
						continue;
					}
				} elseif( $this->state == ArdeXmlPrinter::IN_TAG ) {
					if( $s[$i] == '>' ) {		
						$this->tag .= '>';				
						if( $this->prevChar == '?' || $this->prevChar == '/' || $this->prevChar == '!' ) {
							$this->tagEndOper = $this->prevChar;
						}
						
						if( $this->tagStartOper == '?' ) {
							if( $this->isStrict && $this->tagEndOper != '?' ) return $this->error( '?> expected', $i, $out );
							if( $this->tagName == 'xml' && !$this->isXhtml ) {
								if( $this->isStrict && $this->doctypeTagPrinted ) return $this->error( 'we have already printed a <?xml...', $i, $out );
								$this->doctypeTagPrinted = true;
							}
						} elseif( $this->tagStartOper == '!' ) {
							if( $this->tagName == 'DOCTYPE' && $this->isXhtml ) {
								if( $this->isStrict && $this->doctypeTagPrinted ) return $this->error( 'we have already printed a <!DOCTYPE...', $i, $out );
								$this->doctypeTagPrinted = true;
							}
						} elseif( $this->tagStartOper == '/' ) {
							if( end( $this->in ) != $this->tagName ) {
								if( $this->isStrict ) return $this->error( "<".end( $this->in )."> tag was not closed, </".$this->tagName."> received", $i, $out );
							} else {
								if( $this->tagName == $this->bodyTagName ) $this->bodyTagClosed = true;
								elseif( $this->isXhtml && $this->htmlTagClosed ) $this->htmlTagClosed = true;
								array_pop( $this->in );
							}
						} elseif( $this->tagEndOper != '/' ) {
							if( $this->isXhtml ) {
								if( $this->tagName == 'html' ) {
									if( $this->isStrict ) {
										if( count( $this->in ) ) return $this->error( '<html> can only be the root element', $i, $out );
										if( $this->htmlTagPrinted ) return $this->error( 'we have already printed a <html>', $i, $out );
									} 
									$this->htmlTagPrinted = true;
								} elseif( $this->tagName == 'body' ) {
									if( $this->isStrict ) {
										if( end( $this->in ) != 'html' ) return $this->error( '<body> is allowed only within <html>', $i, $out );
										if( $this->bodyTagPrinted ) return $this->error( 'we have already printed a <body>', $i, $out );
									}
									$this->bodyTagPrinted = true;
								}
							} else {
								if( $this->tagName == 'period_types' ) {
									$rrrrr= 234;
								}
								if( $this->tagName == $this->bodyTagName ) {
									if ($this->isStrict ) {
										if( $this->bodyTagPrinted ) return $this->error( 'we have already printed a <'.$this->bodyTagName.'>', $i, $out );
									} 
									$this->bodyTagPrinted = true;
								}
							}
							array_push( $this->in, $this->tagName );
						}
						$this->state = ArdeXmlPrinter::IN_TEXT;
						$out .= $this->tag;
					} elseif( $this->isStrict && $s[$i] == '<' ) {
						return $this->error( 'unexpected < character', $i, $out );
					} else {
						$this->tag .= $this->prevChar = $s[ $i ];
					}
				}
				++$i;
			}
			
			parent::o( $out );
		}
		
		private function error( $message, $i, $out ) {
			$this->active = false;
			parent::o( $out );
			$e = new ArdeException( 'printXml: '.$message, 0, null );
			ArdeException::reportError( $e );
			$this->unexpectedEnd();
			die();
		}
		
		public function pl( $s, $holdTabs = null ) {
			try {
				parent::pl( $s, $holdTabs );
				if( !$this->active || $this->crossPrinting ) return;
				$this->afterUnitPrint();
			} catch( Exception $e ) {
				die( $e->__toString() );
			}
		}
		
		public function pn( $s ) {
			try {
				parent::pn( $s );
				if( !$this->active || $this->crossPrinting ) return;
				$this->afterUnitPrint();
			} catch( Exception $e ) {
				die( $e->__toString() );
			}
		}
		
		public function pm( $s ) {
			try {
				parent::pm( $s );
				if( !$this->active || $this->crossPrinting ) return;
				$this->afterUnitPrint();
			} catch( Exception $e ) {
				die( $e->__toString() );
			}
		}
		
		private function afterUnitPrint() {
			if( $this->state == ArdeXmlPrinter::IN_TEXT ) {
				if( ( $this->isXhtml && isset( self::$crossSafeXhtmlTags[ end( $this->in ) ] ) ) || 
					( !$this->isXhtml && end( $this->in ) == $this->bodyTagName ) ) {		
					$this->crossPrinter->flush( $this );		
					$this->crossSafe = true;
				} else {
					$this->crossSafe = false;
				}
			} else {
				$this->crossSafe = false;
			}
		}
		
		

		
		

		public function printDoctypeTag() {
			if( $this->isXhtml ) {
				$this->pl( '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' );
			} else {
				$this->pl( '<?xml version="1.0" encoding="UTF-8" ?>' );
			}
		}
		
		public function end() {
			if( $this->state == ArdeXmlPrinter::IN_CDATA ) {			
				if( $this->isStrict ) {
					return $this->error( 'end() called ]]> expected', 0, '' );
				}
			} elseif( $this->state == ArdeXmlPrinter::IN_TAG || $this->state == ArdeXmlPrinter::IN_TAG_NAME ) {
				if( $this->isStrict ) {
					return $this->error( 'end() called in the middle of a tag', 0, '' );
				} else {
					parent::o( $this->tag );
					$this->state = ArdeXmlPrinter::IN_TEXT;
				}				
			}
			

			$this->crossPrinter->forceFlush( $this );

			
			
			if( $this->isXhtml ) {
				$this->pl( '</html>' );
			} else {
				$this->rel();
				$this->pl( '</'.$this->bodyTagName.'>' );
			}

		}
		
		private function unexpectedEnd() {
			try {
				$this->active = false;
				
				if( $this->state == ArdeXmlPrinter::IN_CDATA ) {
					$this->pl( '/*]]>*/' );
				} elseif( $this->state == ArdeXmlPrinter::IN_TAG || $this->state == ArdeXmlPrinter::IN_TAG_NAME ) {
					if( !$this->isStrict ) {
						parent::o( $this->tag );
					}				
				}
				
				if( !$this->doctypeTagPrinted && !count( $this->in ) ) {
					$this->printDoctypeTag();
					$this->doctypeTagPrinted = true;
				}
				
				$wroteBodyTagRightNow = false;
				
				if( $this->isXhtml ) {		
					$wroteHtmlTagRightNow = false;		
					if( !$this->htmlTagPrinted && !count( $this->in ) ) {
						$this->pl( '<html>' );
						$this->htmlTagPrinted = true;
						$wroteHtmlTagRightNow = true;
					}
					if( !$this->bodyTagPrinted && ( end( $this->in ) == 'html' || $wroteHtmlTagRightNow ) ) {
						$this->pl( '<body>', 1 );
						$this->bodyTagPrinted = true;
						$wroteBodyTagRightNow = true;
					}
				} else {
					if( !$this->bodyTagPrinted && !count( $this->in ) ) {
						$this->pl( '<'.$this->bodyTagName.'>', 1 );
						$this->bodyTagPrinted = true;
						$wroteBodyTagRightNow = true;
					}
				}
				
				if( !$wroteBodyTagRightNow && $this->bodyTagPrinted && !$this->bodyTagClosed ) $this->closeToBody();
				
				$this->crossPrinter->forceFlush( $this );
				$this->footer->flush( $this );
				
				if( $this->isXhtml ) {
					if( $this->bodyTagPrinted && !$this->bodyTagClosed ) $this->pl( '</body>' );
					if( $this->htmlTagPrinted && !$this->htmlTagClosed ) $this->pl( '</html>' );
				} else {
					if( $this->bodyTagPrinted && !$this->bodyTagClosed ) $this->pl( '</'.$this->bodyTagName.'>' );
				}
				
			} catch( Exception $e ) {
				echo $e->__toString();
			}
		}
		
		private function closeToBody() {
			while( count( $this->in ) && end( $this->in ) != $this->bodyTagName ) {
				$tagName = array_pop( $this->in );
				$this->pl( '</'.$tagName.'>' );
			}
		}
		
		public function xDie( ArdeException $e = null ) {
			try {
				if( $e != null ) {
					ArdeException::reportError( $e );
				}
				$this->unexpectedEnd();
				die();
			} catch( Exception $e ) {
				echo $e->__toString();
			}
		}
		
		public function start( $sendXmlHeader = true ) {
			if( !$this->isXhtml && $sendXmlHeader ) {
				if( $sendXmlHeader === true ) $sendXmlHeader = 'text';
				header( 'Content-Type: '.$sendXmlHeader.'/xml' );
			}
			$this->printDoctypeTag();
			if( $this->isXhtml ) {	
				if( $this->xmlLangCode == '' ) $this->xmlLangCode = null;
				$this->pl( '<html xmlns="http://www.w3.org/1999/xhtml"'.($this->xmlLangCode===null?'':' xml:lang="'.$this->xmlLangCode.'" lang="'.$this->xmlLangCode.'"').'>' );
			} else {
				
				$this->pl( '<'.$this->bodyTagName.'>', 1 );	
			}
			
		}

		private static function isWord( $s ) {
			$o = ord( $s );
			return ( $o > 64 && $o < 91 ) || ( $o > 96 && $o < 123 ) || ( $o > 47 && $o < 58 ) || ( $o == 95 );
		}
		
		
		public function reportError( ArdeException $exception ) {
			if( $this->hideErrors && !( $exception instanceof ArdeException && $exception->getType() == ArdeException::USER_ERROR ) ) return;
			
			try {
				$this->crossPrinter->nl();
				if( $this->printErrorStyleAndScript && $this->isXhtml ) {
					
					$this->printErrorStyleAndScript = false;
					$this->crossPrinter->pl( '<style>' );
					$this->crossPrinter->pl( "	/* automatically written by ArdeXml's reportError() */", 0 );
					ArdeException::printStyle( $this->crossPrinter );
					$this->crossPrinter->relnl();
					$this->crossPrinter->pl( '</style>' );
					$this->crossPrinter->pl( '<script type="text/javascript">/*<![CDATA[*/' );
					$this->crossPrinter->pl( "	/* automatically written by ArdeXml's reportError() */", 0 );
					ArdeException::printScript( $this->crossPrinter );
					$this->crossPrinter->relnl();
					$this->crossPrinter->pl( '/*]]>*/</script>' );
				}
				
				if( $this->isXhtml ) {
					
					$exception->printXhtml( $this->crossPrinter, $this->showMutedErrors );
				} else {
					$exception->printXml( $this->crossPrinter, 'error', $this->showMutedErrors );
				}
				$this->crossPrinter->nl();
			} catch( Exception $e ) {
				
				echo $e->__toString();
			}
		}
		
		public function uncaughtExceptionHandler( $exception ) {
			
			try {
				$this->active = false;
				if( $exception instanceof ArdeException ) {
					ArdeException::reportError( $exception );
				} else {
					echo $exception->__toString();
				}
				$this->unexpectedEnd();
			} catch( Exception $e ) {
				echo $e->__toString();
			}
		}
		
	}
	
	class ArdeXmlCrossPrinter extends ArdeJobPrinter {
		private $parent;
		
		private $forcedToHold;
		
		function __construct( ArdeXmlPrinter $parent ) {
			parent::__construct();
			$this->parent = $parent;
		}
		
		public function pl( $s, $holdTabs = null ) {
			try {
				if( !$this->forcedToHold && $this->parent->isCrossSafe() ) {
					return $this->parent->pl( $s, $holdTabs );
				}
				return parent::pl( $s, $holdTabs );
			} catch( Exception $e ) {
				echo $e->__toString();
			}
		}
		
		public function pn( $s ) {
			try {
				if( !$this->forcedToHold && $this->parent->isCrossSafe() ) {
					$this->parent->crossPrinting = true;
					return $this->parent->pn( $s );
					$this->parent->crossPrinting = false;
				}
				return parent::pn( $s );
			} catch( Exception $e ) {
				echo $e->__toString();
			}
		}
		
		public function pm( $s ) {
			try {
				if( !$this->forcedToHold && $this->parent->isCrossSafe() ) {
					$this->parent->crossPrinting = true;
					return $this->parent->pm( $s );
					$this->parent->crossPrinting = false;
				}
				return parent::pm( $s );
			} catch( Exception $e ) {
				echo $e->__toString();
			}
		}
		
		public function nl() {
			try {
				if( !$this->forcedToHold && $this->parent->isCrossSafe() ) {
					$this->parent->crossPrinting = true;
					return $this->parent->nl();
					$this->parent->crossPrinting = false;
				}
				return parent::nl();
			} catch( Exception $e ) {
				echo $e->__toString();
			}
		}
		
		public function rel() {
			try {
				if( !$this->forcedToHold && $this->parent->isCrossSafe() ) {
					$this->parent->crossPrinting = true;
					return $this->parent->rel();
					$this->parent->crossPrinting = false;
				}
				return parent::rel();
			} catch( Exception $e ) {
				echo $e->__toString();
			}
		}
		
		public function hold( $n ) {
			try {
				if( !$this->forcedToHold && $this->parent->isCrossSafe() ) {
					$this->parent->crossPrinting = true;
					return $this->parent->hold( $n );
					$this->parent->crossPrinting = false;
				}
				return parent::hold( $n );
			} catch( Exception $e ) {
				echo $e->__toString();
			}
		}
		
		public function cancelNl() {
			try {
				if( !$this->forcedToHold && $this->parent->isCrossSafe() ) {
					$this->parent->crossPrinting = true;
					return $this->parent->cancelNl();
					$this->parent->crossPrinting = false;
				}
				return parent::cancelNl();
			} catch( Exception $e ) {
				echo $e->__toString();
			}
		}
		
		public function isForcedToHold() {
			return $this->forcedToHold;
		}
		
		public function forceHold() {
			$this->forcedToHold = true;
		}
		
		public function flush( ArdePrinter $p ) {
			try {
				$this->parent->crossPrinting = true;
				if( !$this->forcedToHold ) parent::flush( $p );
				$this->parent->crossPrinting = false;
			} catch( Exception $e ) {
				echo $e->__toString();
			}
		} 
		
		public function forceFlush() {
			try {
				$this->forcedToHold = false;
				$this->flush( $this->parent );
			} catch( Exception $e ) {
				echo $e->__toString();
			}
		}

	}
?>