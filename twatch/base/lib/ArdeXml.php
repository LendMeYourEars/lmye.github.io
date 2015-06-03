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



	class ArdeXmlError extends ArdeException {
		protected $class = "XML Error";
	}


	class ArdeXmlElementWriter {

		public function writeChildren( ArdePrinter $p, DOMElement $e, $trim = true, $unindent = 0, $indent = 0 ) {

			$n = $e->firstChild;


			if( $n && $n->nodeType == XML_TEXT_NODE && preg_match( '/^([^\n]*)\n(\t*)(.*?\n\t*|)$/Ds', $n->nodeValue, $matches ) ) {
				$indent = strlen($matches[2]) - $unindent;
				if( $trim ) {
					$unindent = strlen($matches[2]);
					$indent = 0;
					if( $matches[1] != '' ) {
						$p->pm( ardeXmlEntities( $this->prepareText( ArdeXml::reindentNewLine( $matches[1]."\n".$matches[2], $unindent ) ) ) );
					}
					$p->pm( ardeXmlEntities( $this->prepareText( ArdeXml::reindentNewLine( $matches[3], $unindent ) ) ) );
					$n = $n->nextSibling;
				}
			}



			while( $n ) {

				if( $trim && $n->nextSibling == null && $n->nodeType == XML_TEXT_NODE && preg_match( '/^(.*)\n\t*$/Ds', $n->nodeValue, $matches ) ) {
					$p->pm( ardeXmlEntities( $this->prepareText( ArdeXml::reindentNewLine( $matches[1], $unindent ) ) ) );
				} else {

					if( $n->nodeType == XML_TEXT_NODE ) {
						$p->pm( ardeXmlEntities( $this->prepareText( ArdeXml::reindentNewLine( $n->nodeValue, $unindent ) ) ) );
					} elseif( $n->nodeType == XML_ELEMENT_NODE ) {
						$res = $this->writeElement( $p, $n, $unindent, $indent );
						if( $res !== false ) {
							$n = $res;
							continue;
						}
					} elseif( $n->nodeType == XML_COMMENT_NODE ) {
						if( $n->nodeValue[0] != '~' ) {
							$p->pm( '<!--'.ArdeXml::reindentNewLine( $n->nodeValue, $unindent ).'-->' );
						}
					}
				}
				$n = $n->nextSibling;
			}

		}

		protected function prepareText ( $s ) {
			return $s;
		}

		protected function prepareTagForWrite( ArdeXmlTagWriter $t ) {}

		protected function writeNormalElement( ArdePrinter $p, DOMElement $e, $unindent = 0, $indent = 0 ) {
			$tw = new ArdeXmlTagWriter();
			$tw->readTag( $e );
			$this->prepareTagForWrite( $tw );
			$p->pn( $tw->getBeginTag( true ));
			if( $e->firstChild == null ) {
				$p->pn( '/>' );
			} else {
				$p->pn( '>' );
				$this->writeChildren( $p, $e, false, $unindent, $indent );
				$p->pn( $tw->getEndTag() );
			}
			return false;
		}

		public function writeElement( ArdePrinter $p, DOMElement $e, $unindent = 0, $indent = 0 ) {
			return $this->writeNormalElement( $p, $e, $unindent, $indent );
		}

		public function strC( DOMElement $e ) {
			$o = '';
			$p = new ArdeStringPrinter( $o );
			$this->writeChildren( $p, $e, true );
			return $o;
		}

		public function strElem( DOMElement $e, $tagName, $default = false ) {
			$res = ArdeXml::element( $e, $tagName, $default );
			if( $res === $default ) return $default;

			return $this->strC( $res );
		}
	}


	class ArdeXmlFork extends ArdeXmlElementWriter {
		public $forks = array();
		public $forkSelects = array();

		public function addFork( $id, &$tagNames, $select ) {

			$this->forks[ $id ] = &$tagNames;
			$this->forkSelects[ $id ] = $select;


		}

		public function isInForks( DOMElement $e ) {
			foreach( $this->forks as $id => &$fork ) {
				if( isset( $fork[ $e->tagName ] )) {
					return $id;
				}
			}
			return null;
		}

		public function forkElement( DOMElement $e, &$next ) {
			if( $id = $this->isInForks( $e ) ) {
				$es = array();
				$pe = $ne = $e;

				while( $ne && isset( $this->forks[ $id ][ $ne->tagName ] ) && !isset( $es[ $ne->tagName ] ) ) {
                    $es[ $ne->tagName ] = $ne;
					$pe = $ne;
                    $ne = ArdeXml::nextElement( $ne );

				}

                $next = $pe->nextSibling;



				if( isset( $es[ $this->forkSelects[ $id ] ] )) {
					$res = $es[ $this->forkSelects[ $id ] ];
				} elseif( isset( $es[ 'else'] )) {
					$res = $es[ 'else' ];
				} else {
					$res = null;
				}
				return $res;
			} else {
				return false;
			}
		}



		public function writeElement( ArdePrinter $p, DOMElement $e, $unindent = 0, $indent = 0 ) {
            $next = false;
			$res = $this->forkElement( $e, $next );
			if( $res !== false ) {
				if( $res !== null ) {
					$p->hold( $indent );
					$this->writeChildren( $p, $res, true, $unindent, 0 );
					$p->rel();
					return $next;
				}
			} else {
				return $this->writeNormalElement( $p, $e, $unindent, $indent );
			}
			return false;
		}


	}



	class ArdeXmlId {
        public $id;
        public $autoId;
        public function ArdeXmlId( $autoId ) {
            $this->autoId = $autoId;
            if( $this->autoId !== false ) {
                $id = 0;
            } else {
                $id = $this->autoId;
            }
        }
        public function getId( $e ) {
            if( $this->autoId !== false ) {
                if( $e->hasAttribute( 'id' ) ) {
                    $id = $e->getAttribute( 'id' );
                }
                $cId = $id;
                ++$id;
                return $cId;
            } else {
                return ArdeXml::attr( $e , 'id' );
            }
        }

    }

    class ArdeXmlElemIter implements Iterator {
		var $current = null;
    	var $tagName = null;

        public function __construct( DOMElement $topNode , $tagName ) {
        	$this->tagName = $tagName;
            $this->current = ArdeXml::element( $topNode , $tagName, null );
        }

        public function next() {
            do {
                $this->current = $this->current->nextSibling;
                if( !$this->current ) {
                	$this->current = null;
                	return null;
				}
                if( $this->current->nodeType == XML_ELEMENT_NODE ) {
                    if( $this->current->tagName == $this->tagName ) {
                        return $this->current;
                    }
                }
            } while( true );

     	}

		public function rewind() {}
		public function key() { return 0; }
		public function valid() { return $this->current !== null; }
        public function current() { return $this->current; }
	}

	class ArdeXmlIdElemIter extends ArdeXmlElemIter {
		var $xId;
    	var $currentId = null;
    	function ArdeXmlIdElemIter( $topNode, $tagName, $autoId = false ) {
    		parent::ArdeXmlElemIter( $topNode, $tagName );
    		$this->xId = new ArdeXmlId( $autoId );
            if( $this->current !== null ) {
                $this->currentId = $this->xId->getId( $this->current );
            } else {
            	$this->currentId = null;
			}
		}
		function next() {
			parent::next();
			if( $this->current !== null ) {
				$this->currentId = $this->xId->getId( $this->current );
			} else {
				$this->currentId = null;
			}
		}
	}

	class ArdeXmlMultiElemIter implements Iterator {
		var $current = null;
    	var $tagNames = null;

        public function __construct( DOMElement $topNode , $tagNames ) {
        	$this->tagNames = $tagNames;
            $this->current = ArdeXml::multiElem( $topNode , $tagNames, null );
        }

        public function next() {
            do {
                $this->current = $this->current->nextSibling;
                if( !$this->current ) {
                	$this->current = null;
                	return null;
				}
                if( $this->current->nodeType == XML_ELEMENT_NODE ) {
                    if( isset( $this->tagNames[ $this->current->tagName ] )) {
                        return $this->current;
                    }
                }
            } while( true );

        }

		public function rewind() {}
		public function key() { return 0; }
		public function valid() { return $this->current !== null; }
        public function current() { return $this->current; }
	}

    class ArdeXmlForkIter {
        var $current = null;
        var $fork = null;
        var $next = array();

        function ArdeXmlForkIter( DOMElement $topNode, ArdeXmlFork $fork ) {
        	$this->fork = $fork;

        	$this->current = $topNode->firstChild;
        	$this->move();
        }
        function next() {
        	$this->current = $this->current->nextSibling;
        	$this->move();
		}
        function move() {
        	do {
        		if( $this->current == null ) {
        			if( count( $this->next ) != 0 ) {
						$this->current = array_pop( $this->next );
						continue;
					} else {
						$this->current = null;
						return;
					}
				}
        		if( $this->current->nodeType == XML_ELEMENT_NODE ) {
        			$res = $this->fork->forkElement( $this->current, $next );
        			if( $res === false ) {
        				return;
					} else {
						if( $res === null ) {
							$this->current = $next;
						} else {
        					array_push( $this->next, $next );
        					$this->current = $res->firstChild;
						}
        				continue;
					}
				}
        		$this->current = $this->current->nextSibling;
			} while( true );
		}
    }

    class ArdeXmlForkElemIter extends ArdeXmlForkIter {
    	var $tagName;

    	function ArdeXmlForkElemIter( DOMElement $topNode, ArdeXmlFork $fork, $tagName ) {
    		parent::ArdeXmlForkIter( $topNode, $fork );
    		$this->tagName = $tagName;
    		while( $this->current != null ) {
    			if( $this->current->tagName == $this->tagName ) return;
    			parent::next();
			}
		}

		function next() {
			parent::next();
			while( $this->current != null ) {
				if( $this->current->tagName == $this->tagName ) return;
				parent::next();
			}
		}
	}

    class ArdeXmlForkObjectIter extends ArdeXmlForkElemIter {
    	var $currentObject = null;
    	var $className;
    	var $param;
    	function ArdeXmlForkObjectIter( $topNode, $tagName, $fork, $className, $param = null ) {
    		parent::ArdeXmlForkElemIter( $topNode, $fork, $tagName );
    		$this->className = $className;
    		$this->param = $param;
			$this->setObject();
		}
		private function setObject() {
			if( $this->current !== null ) {
    			$this->currentObject = new $this->className();
    			$this->currentObject->fromXml( $this->current, $this->param );
			} else {
				$this->currentObject = null;
			}
		}
		function next() {
			parent::next();
			$this->setObject();
		}
	}



	class ArdeXmlMultiIdElemIter extends ArdeXmlMultiElemIter {
		var $xId;
    	var $currentId = null;
    	function ArdeXmlMultiIdElemIter( $topNode, $tagNames, $autoId = false ) {
    		parent::ArdeXmlMultiElemIter( $topNode, $tagNames );
    		$this->xId = new ArdeXmlId( $autoId );
            if( $this->current !== null ) {
                $this->currentId = $this->xId->getId( $this->current );
            } else {
            	$this->currentId = null;
			}
		}
		function next() {
			parent::next();
			if( $this->current !== null ) {
				$this->currentId = $this->xId->getId( $this->current );
			} else {
				$this->currentId = null;
			}
		}
	}

	class ArdeXmlMultiIdObjectIter extends ArdeXmlMultiIdElemIter {
		var $currentObject = null;
    	var $param;
    	function ArdeXmlMultiIdObjectIter( $topNode, $tagNames, $param = null, $autoId = false ) {
    		parent::ArdeXmlMultiIdElemIter( $topNode, $tagNames, $autoId );
    		$this->param = $param;
			$this->setObject();
		}
		private function setObject() {
			if( $this->current !== null ) {
    			$this->currentObject = new $this->tagNames[ $this->current->tagName ]();
    			$this->currentObject->fromXml( $this->current, $this->param );
			} else {
				$this->currentObject = null;
			}
		}
		function next() {
			parent::next();
			$this->setObject();
		}
	}

	class ArdeXmlMultiObjectIter extends ArdeXmlMultiElemIter {
		var $currentObject = null;
    	var $param;
    	function ArdeXmlMultiObjectIter( $topNode, $tagNames, $param = null ) {
    		parent::ArdeXmlMultiElemIter( $topNode, $tagNames );
    		$this->param = $param;
			$this->setObject();
		}
		private function setObject() {
			if( $this->current !== null ) {
    			$this->currentObject = new $this->tagNames[ $this->current->tagName ]();
    			$this->currentObject->fromXml( $this->current, $this->param );
			} else {
				$this->currentObject = null;
			}
		}
		function next() {
			parent::next();
			$this->setObject();
		}
	}

	class ArdeXmlIdObjectIter extends ArdeXmlIdElemIter {
    	var $currentObject = null;
    	var $className;
    	var $param;
    	function ArdeXmlIdObjectIter( $topNode, $tagName, $className, $param = null, $autoId = false ) {
    		parent::ArdeXmlIdElemIter( $topNode, $tagName, $autoId );
    		$this->className = $className;
    		$this->param = $param;
			$this->setObject();
		}
		private function setObject() {
			if( $this->current !== null ) {
    			$this->currentObject = new $this->className();
    			$this->currentObject->fromXml( $this->current, $this->param );
			} else {
				$this->currentObject = null;
			}
		}
		function next() {
			parent::next();
			$this->setObject();
		}
	}

	class ArdeXmlObjectIter extends ArdeXmlElemIter {
    	var $currentObject = null;
    	var $className;
    	var $param;
    	function ArdeXmlObjectIter( $topNode, $tagName, $className, $param = null ) {
    		parent::ArdeXmlElemIter( $topNode, $tagName );
    		$this->className = $className;
    		$this->param = $param;
			$this->setObject();
		}
		private function setObject() {
			if( $this->current !== null ) {
    			$this->currentObject = new $this->className();
    			$this->currentObject->fromXml( $this->current, $this->param );
			} else {
				$this->currentObject = null;
			}
		}
		function next() {
			parent::next();
			$this->setObject();
		}
	}

	class ArdeXmlTagWriter {

    	public $styles = array();
    	public $attribs = array();
    	public $tagName;

    	public function readTag( DomElement $element ) {
    		$this->tagName = $element->tagName;
    		if( $element->hasAttributes() ) {
    			foreach( $element->attributes as $attrib ) {
    				if( $attrib->name == 'style' ) {
    					$a = explode( ';', $attrib->value );
    					foreach( $a as &$pair ) {
    						$b = explode( ':', $pair );
    						if( count( $b ) == 2 ) {
    							$this->styles[ $b[0] ] = $b[1];
							}
						}
					} else{
    					$this->attribs[ $attrib->name ] = $attrib->value;
					}
				}
			}
		}

		public function getBeginTag( $short = false, $empty = false ) {
			$s = '<'.$this->tagName;

			if( count( $this->styles ) ) {
				$ss = '';
				foreach( $this->styles as $name => $value ) {
					$ss.=$name.':'.$value.';';
				}
				$this->attribs['style'] = $ss;
			}
			foreach( $this->attribs as $name => $value ) {
				$s .= ' '.$name.'="'.$value.'"';
			}

			if( !$short ) {
				if( $empty ) $s .= ' />';
				else $s.='>';
			}

			return $s;
		}


		public function getEndTag() {
			return '</'.$this->tagName.'>';
		}
	}

	class ArdeXmlNoDefault {};

	class ArdeXml {



		public static function fileRoot( $filename ) {
			$doc = new DOMDocument();
			$doc->load( $filename );
			return $doc->documentElement;
		}
		
		public static function bool( $value ) {
			return $value ? 'true' : 'false';
		}

		public static function cleanContents( DOMElement $e ) {
			$firstChild = $n = $e->firstChild;

			$unIndent = 0;
			if( $n && $n->nodeType == XML_TEXT_NODE && preg_match( '^.*\n(\t*)$', $n->nodeValue, $matches ) ) {
				$unIndent = strlen( $matches[1] );
				$firstChild = $n = $n->nextSibling();
			}
			while( $n ) {
				if( $unIndent ) {
					if( $n->nodeType == XML_TEXT_NODE ) {
						$n->nodeValue = ArdeXml::unIndent( $n->nodeValue, $unIndent );
					} elseif( $n->nodeTyle == XML_ELEMENT_NODE ) {
						ArdeXml::_cleanContents( $n, $unIndent );
					}
				}
				if( $n->nextSibling == null && $n->nodeType == XML_TEXT_NODE && preg_match( "^([^\n]*)\n\t*$", $n->nodeValue, $matches ) ) {
					$n->nodeValue = $matches[1];
				}
				$n = $n->nextSibling;
			}
			return $firstChild;
		}


		private static function _cleanContents( DOMElement $e, $unIndent ) {
			$n = $e->firstChild;
			while( $n ) {
				if( $n->nodeType == XML_TEXT_NODE ) {
					$n->nodeValue = ArdeXml::unIndent( $n->nodeValue, $unIndent );
				} elseif( $n->NodeType == XML_ELEMENT_NODE ) {
					ArdeXml::_cleanContents( $n, $unIndent );
				}
				$n = $n->nextSibling;
			}
		}

		public static function reindentNewLine( $s, $unindent, $indent = 0 ) {
			$o = '';
			$start = 0;
			for( $i = 0 ; $i < strlen($s) ; ++$i ) {
				if( $s{$i} == "\n" ) {
					++$i;
					$o .= substr( $s, $start, $i - $start );
					$start = $i;
					while( isset( $s{$i} ) && $s{$i} == "\t" && $i - $start < $unindent ) ++$i;
					$start = $i;
					for( $j = 0 ; $j < $indent ; ++$j ) $o .= "\t";
				}
			}
			if( $start < strlen($s) ) {
				$o .= substr( $s, $start, strlen($s) - $start );
			}
			return $o;
		}

		public static function strContent( DOMElement $e ) {
			return self::strC( $e );
		}

		public static function strC( DOMElement $e ) {
  			if( $e->firstChild === null ) return '';
  			if( $e->firstChild->nodeType !== XML_TEXT_NODE ) return '';
  			return $e->firstChild->nodeValue;
		}

		public static function intContent( DOMElement $e ) {
			return self::intC( $e );
		}

	  	public static function intC( DOMElement $e ) {
			$s = ArdeXml::strC( $e );
			if( !preg_match( '/^\d+$/', $s )) throw new ArdeXmlError( "Element <".$e->tagName."> may only contain integer value '".$s."' is not valid." );
			return (int)$s;
		}

		public static function boolC( DOMElement $e ) {
			$s = ArdeXml::boolC( $e );
			if( $s == 'true' ) return true;
			if( $s == 'false' ) return false;
			throw new ArdeXmlError( "Element <".$e->tagName."> may only contain 'true' or 'false', value '".$s."' is not valid." );
		}

		public static function strAttribute( DOMElement $e, $name ) {
			if( !$e->hasAttribute( $name ) ) {
				if( func_num_args() <= 2 ) throw new ArdeXmlError( "Element <".$e->tagName."> must contain attribute '".$name."'." );
				return func_get_arg(2);
			}
			return $e->getAttribute( $name );
		}

		public static function intAttribute( DOMElement $e, $name, $default = false ) {
			if( !$e->hasAttribute( $name ) ) {
				if( $default === false ) throw new ArdeXmlError( "Element <".$e->tagName."> must contain attribute '".$name."'." );
				return $default;
			}
			$s = $e->getAttribute( $name );
			if( !preg_match( '/^\d*$/', $s )) throw new ArdeXmlError( "Attribute '".$name."' in element <".$e->tagName."> may only contain integer value, '".$s."' is not valid." );
			return (int)$s;
		}

		public static function boolAttribute( DOMElement $e, $name, $default = 0 ) {
			if( !$e->hasAttribute( $name ) ) {
				if( $default === 0 ) throw new ArdeXmlError( "Element <".$e->tagName."> must contain attribute '".$name."'." );
				return $default;
			}
			$s = $e->getAttribute( $name );
			if( $s == 'true' ) return true;
			if( $s == 'false' ) return false;
			throw new ArdeXmlError( "Attribute '".$name."' element <".$e->tagName."> may only contain 'true' or 'false', value '".$s."' is not valid." );
		}

		public static function element( DOMElement $e, $tagName ) {
			$n = $e->firstChild;
			while( $n ) {
				if( $n->nodeType == XML_ELEMENT_NODE && $n->tagName == $tagName ) return $n;
				$n = $n->nextSibling;
			}
			if( func_num_args() <= 2 ) throw new ArdeXmlError( "Element <".$e->tagName."> must contain element <".$tagName.">" );
			return func_get_arg(2);
		}



		public static function multiElem( DOMElement $e, $tagNames, $default = false ) {
			$n = $e->firstChild;
			while( $n ) {
				if( $n->nodeType == XML_ELEMENT_NODE && isset( $tagNames[ $n->tagName ] ) ) return $n;
				$n = $n->nextSibling;
			}
			if( $default === false ) {
				$s = '';
				foreach( $tagNames as $tagName => $v ) {
					$s .= " <".$tagName.">";
				}
				throw new ArdeXmlError( "Element <".$e->tagName."> must contain one of these elements:".$s );

			}
			return $default;
		}



		public static function strElement( DOMElement $e, $tagName ) {
			$se = ArdeXml::element( $e, $tagName, null );
			if( $se === null ) {
				if( func_num_args() <= 2 ) throw new ArdeXmlError( "Element <".$e->tagName."> must contain element <".$tagName.">" );
				return func_get_arg(2);
			}
			return ArdeXml::strContent( $se );
		}

		public static function intElement( DOMElement $e, $tagName, $default = false ) {
			$se = ArdeXml::element( $e, $tagName, $default );
			if( $se === $default ) return $default;
			return ArdeXml::intC( $se );
		}

		public static function boolElement( DOMElement $e, $tagName, $default = 0 ) {
			$se = ArdeXml::element( $e, $tagName, $default===0?false:$default );
			if( $se === $default ) return $default;
			return ArdeXml::boolC( $se );
		}

		public static function elems( DOMElement $topNode, $tagName ) {
			$a = array();
			$i = new ArdeXmlElemIter( $topNode, $tagName );
			while( $i->current ) {
				$a[] = $i->current;
				$i->next();
			}
			return $a;
		}

		public static function idElems( DOMElement $topNode, $tagName, $autoId = false ) {
			$a = array();
			$i = new ArdeXmlIdElemIter( $topNode, $tagName, $autoId );
			while( $i->current ) {
				$a[ $i->currentId ] = $i->current;
				$i->next();
			}
			return $a;
		}

		public static function multiElems( DOMElement $topNode, $tagNames ) {
			$a = array();
			$i = new ArdeXmlMultiElemIter( $topNode, $tagNames );
			while( $i->current ) {
				$a[] = $i->current;
				$i->next();
			}
			return $a;
		}



		public static function multiIdElems( DOMElement $topNode, $tagNames, $autoId = false ) {
			$a = array();
			$i = new ArdeXmlMultiIdElemIter( $topNode, $tagNames, $autoId );
			while( $i->current ) {
				$a[ $i->currentId ] = $i->current;
				$i->next();
			}
			return $a;
		}

		public static function objects( DOMElement $topNode, $tagName, $className, $param = null ) {
			$a = array();
			$i = new ArdeXmlObjectIter( $topNode, $tagName, $className, $param );
			while( $i->current ) {
				$a[] = $i->currentObject;
				$i->next();
			}
			return $a;
		}

		public static function idElem( DOMElement $topNode, $tagName, $id, $default = false ) {
			$i = new ArdeXmlIdElemIter( $topNode, $tagName, false );
			while( $i->current ) {
				if( $i->currentId == $id ) {
					return $i->current;
				}
				$i->next();
			}
			if( $default === false ) throw new ArdeXmlError( 'tag <'.$tagName.'> width id="'.$id.'" not found in <'.$topNode->tagName.'>' );
			return $default;
		}

		public static function idObject( DOMElement $topNode, $tagName, $id, $className, $param = null, $default = false ) {

			$res = ArdeXml::idElem( $topNode, $tagName, $id, $default );
			if( $res === $default ) return $default;

			$o = new $className();
			$o->fromXml( $res, $param );
			return $o;
		}

		public static function idObjects( DOMElement $topNode, $tagName, $className, $param = null, $autoId = false ) {
			$a = array();
			$i = new ArdeXmlIdObjectIter( $topNode, $tagName, $className, $param, $autoId );
			while( $i->current ) {
				$a[ $i->currentId ] = $i->currentObject;
				$i->next();
			}
			return $a;
		}

		public static function multiObjects( DOMElement $topNode, $tagNames, $param = null ) {
			$a = array();
			$i = new ArdeXmlMultiObjectIter( $topNode, $tagNames, $param );
			while( $i->current ) {
				$a[] = $i->currentObject;
				$i->next();
			}
			return $a;
		}

		public static function multiIdObjects( DOMElement $topNode, $tagNames, $param = null, $autoId = false ) {
			$a = array();
			$i = new ArdeXmlMultiIdObjectIter( $topNode, $tagNames, $param, $autoId );
			while( $i->current ) {
				$a[ $i->currentId ] = $i->currentObject;
				$i->next();
			}
			return $a;
		}

		public static function beginningElement( DOMElement $topElement ) {
			return ArdeXml::stripToNext( $topElement->firstChild );
		}

		public static function nextElement( DOMElement $e ) {
			return ArdeXml::stripToNext( $e->nextSibling );

		}

		private static function stripToNext( $n ) {
			if( !$n ) return null;
			if( $n->nodeType == XML_TEXT_NODE ) {
				if( trim( $n->nodeValue ) == '' ) {
					$n = $n->nextSibling;
					if( !$n ) return null;
				} else {
					return null;
				}
			}
			if( $n->nodeType == XML_ELEMENT_NODE ) return $n;
			return null;
		}
	}
?>
