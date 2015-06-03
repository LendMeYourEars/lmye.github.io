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
	
	class ArdeExpressionElement {
		var $name;
		var $items;
		
		public function __construct( $name, $items ) {
			$this->name = $name;
			$this->items = $items;
		}
		
		public function jsObject() {
			$items = new ArdeAppender( ', ' );
			foreach( $this->items as $item ) {
				if( is_int( $item ) || is_float( $item ) ) {
					$items->append( ardeU32ToStr( $item ) );
				} elseif( is_bool( $item ) ) {
					$items->append( ArdeJs::bool( $item ) );
				} else {
					$items->append( "'".ArdeJs::escape( $item )."'" );
				}
			}
			return "new ArdeExpressionElem( '".ArdeJs::escape( $this->name )."', [ ".$items->s." ] )";
		}
		
		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' name="'.ardeXmlEntities($this->name).'"'.$extraAttrib.'>', 1 );
			foreach( $this->items as $item ) {
				if( is_int( $item ) || is_float( $item ) ) {
					$p->pl( '<item>'.ardeU32ToStr( $item ).'</item>' );
				} elseif( is_bool( $item ) ) {
					$p->pl( '<item type="'.($item?'true':'false').'" />' );
				} else {
					$p->pl( '<item type="str">'.ardeXmlEntities( $item ).'</item>' );
				}
			}
			$p->rel();
			$p->pn( '</'.$tagName.'>' );
		}
	}
	
	class ArdeExpression {

		var $a;
		
		function __construct( $a ) {
			$this->a = $a;
		}
		
		public $emptyValue = false;
		
		public static $operators = array( '!' => 13, '*' => 12, '/' => 11, '+' => 10, '-' => 9, '<' => 8, '>' => 7, '<=' => 6, '>=' => 5, '!=' => 4, '=' => 3, '&' => 2, '|' => 1   );
		public static $singleOperators = array( '!' => true );
		
		public static $operatorNames = array(
			 '+' => '+'
			,'|' => 'or'
			,'&' => 'and'
			,'-' => '-'
			,'*' => '*'
			,'/' => '/'
			,'!' => '!'
			,'=' => 'is'
			,'!=' => 'is not'
			,'<' => '<'
			,'>' => '>'
			,'<=' => '<='
			,'>=' => '>='
		);
		
		const NONE = 0;
		const OPERAND = 1;
		const BINARY_OPERATOR = 2;
		const SINGLE_OPERATOR = 3;
		
		
		
		
		protected function isValidValue( $id, $subId ) {
			return false;
		}
		
		
		
		
		protected $jsClassName = 'ArdeExpression';
		
		protected function varElement( $i, &$varElement ) {
			global $twatch;
			$name = '!NS';
			$as = array( $this->a[$i] );
			$varElement = new ArdeExpressionElement( $name, $as );
			return $i;
		}
		
		public function getElements() {
			$o = array();
			for( $i = 0; $i < count( $this->a ); ++$i ) {
				if( is_int( $this->a[$i] ) ) {
					if( $this->a[$i] == ArdeExpression::INT ) {
						++$i;
						if( isset( $this->a[$i] ) ) {
							$o[] = new ArdeExpressionElement( (string)$this->a[$i], array( ArdeExpression::INT, $this->a[$i] ) );
						}
					} else {
						$i = $this->varElement( $i, $varElement );
						if( $varElement !== null ) {
							$o[] = $varElement;
						}
					}
				} elseif( is_bool( $this->a[$i] ) ) {
					$o[] = new ArdeExpressionElement( $this->a[$i]?'always':'never', array( $this->a[$i] ) );
				} elseif( isset( self::$operators[ $this->a[$i] ] ) ) {
					$o[] = new ArdeExpressionElement( self::$operatorNames[ $this->a[$i] ], array( $this->a[$i] ) );
				} elseif( $this->a[$i] == '(' ) {
					$o[] = new ArdeExpressionElement( '(', array( '(' ) );
				} elseif( $this->a[$i] == ')' ) {
					$o[] = new ArdeExpressionElement( ')', array( ')' ) );
				}
			}
			return $o;
		}
		
		public function jsObject() {
			$elems = new ArdeAppender( ', ' );
			foreach( $this->getElements() as $elem ) {
				$elems->append( $elem->jsObject() );
			}
			return 'new '.$this->jsClassName.'( [ '.$elems->s.' ] )';
		}
		
		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.$extraAttrib.'>', 1 );
			foreach( $this->getElements() as $elem ) {
				$elem->printXml( $p, 'elem' );
				$p->nl();
			}
			$p->rel();
			$p->pn( '</'.$tagName.'>' );
		}
		
		
		
		public static function arrayFromParam( $param ) {
			$o = array();
			if( $param == '' ) return $o;
			$a = explode( '.', $param );
			foreach( $a as $ae ) {
				if( $ae[0] == 'i' ) {
					$o[] = ardeSoftStrToU32( substr( $ae, 1 ) );
				} elseif( $ae[0] == 's' ) {
					$o[] = substr( $ae, 1 );
				} elseif( $ae == 't' ) {
					$o[] = true;
				} elseif( $ae == 'f' ) {
					$o[] = false;
				} else {
					throw new ArdeException( 'bad expression param' );
				}
			}
			return $o;
		}
		
		
	
		protected $variables;
		
		const INT = 0;
		const TRUE = 1;
		const FALSE = 2;
		
		protected function getVar( $i, &$value ) {
			throw new ArdeException( 'invalid variable at offset '.$i );
		}

		protected function isValidVar( $i ) {
			return -1;
		}
		
		public function isValid() {
			$p = 0;
			$prev = -1;
			for( $i = 0; $i < count( $this->a ); ++$i ) {
				if( $this->a[$i] === '(' ) {
					
					++$p;
				} elseif( $this->a[$i] === ')' ) {
					--$p;
					if( $p < 0 ) {
						return 'unexpected ) at offset '.$i;
					}
				} elseif( is_int( $this->a[$i] ) ) {
					
					if( $this->a[$i] == self::INT ) {
						
						++$i;
						if( !isset( $this->a[$i] ) ) return 'integer value not found';
					} else {
						$origI = $i;
						$i = $this->isValidVar( $i );
						if( $i < 0 ) {
							return "invalid variable id at offset ".$origI;
						}  
					}
					if( $prev == 1 ) {
						return 'unexpected element at offset '.$i;
					}
					$prev = 1;
				} elseif( is_bool( $this->a[$i] ) ) {
					$prev = 1;

				} elseif( is_string( $this->a[$i] ) ) {
					if( !isset( self::$operators[ $this->a[ $i ] ] ) ) {
						return "invalid operator '".$this->a[$i]."'";
					}
					if( !isset( self::$singleOperators[ $this->a[$i] ] ) ) {
						if( $prev != 1 ) {
							return "operator should have left operand at offset ".$i;
						}
					}
					if( !isset( $this->a[$i+1] ) || ( !is_int( $this->a[$i+1] ) && !is_bool( $this->a[$i+1] ) && $this->a[$i+1] != '(' ) ) {
						return "operator should have right operand at offset ".$i;				
					}
					$prev = 2;
				} else {
					return 'invalid element at offset '.$i;
				}
			}
			
			if( $p != 0 ) {
				return 'parenthesis not closed';
			}
			return true;
		}
		
		public function evaluate() {
			if( !count( $this->a ) ) return $this->emptyValue;
			$i = 0;
			$res = $this->evalF( $i, 0, false );
			return $res;
	
		}
		
		private function evalF( &$i, $min_prec, $e ) {
			$left = $this->getOperand( $i );
			$res = $this->evalP( $i, $left, $min_prec, $e );
			return $res;
		}
		
		private function getOperand( &$i ) {
			
			if( is_int( $this->a[$i] ) ) {
				if( $this->a[$i] == self::INT ) {
					$i += 2;
					return $this->a[ $i - 1 ];
				}
				$i = $this->getVar( $i, $var ) + 1;
				return $var;
			} elseif( is_bool( $this->a[$i] ) ) {
				++$i;
				return $this->a[$i-1];
			} elseif( isset( self::$singleOperators[ $this->a[ $i ] ] ) ) {
				return 0;
			} elseif( $this->a[$i] === '(' ) {
				$i++;
				$res = $this->evalF( $i, 0, ')' );
				$i++;
				return $res;
			} else {
				throw new ArdeException( 'operand expected at offset '.$i );
			}
		}
		
		
		
		private function evalE( &$i, $min_prec, $e ) {
			$p = 0;
			while( isset( $this->a[$i] ) ) {
				
				if(!$p) {
					if ( $e && $this->a[$i] === $e ) {
						return;
					}
					if( isset( self::$operators[ $this->a[$i] ] ) ) {
						if( self::$operators[ $this->a[$i] ] < $min_prec ) {
							return;
						}
					}
				}
				if( $this->a[$i] === '(' ) $p++;
				if( $this->a[$i] === ')' ) $p--;
				$i++;
			}
			if( $e ) throw new ArdeException( 'eval', $e.' expected at offset '.$i );
		}
		
		
		private function evalP( &$i, $left, $min_prec, $e ) {
			
			while(true) {
				
				if( !isset($this->a[$i]) ) {
					if($e) throw new ArdeException( $e.' expected at offset '.$i );
					return $left;
				}
				
				if( $e && $this->a[$i] === $e ) return $left;
				
				if( !isset( self::$operators[ $this->a[$i] ] ) ) new ArdeException( 'unknown operator '.$this->a[$i].' at offset '.$i );
				
				$oper=$this->a[$i];
				if( $oper === '|' && $left ) {
					$left = true;
					$i++;
					$right = $this->evalE( $i, self::$operators[$oper], $e );
					continue;
				}
				if( $oper === '&' && !$left ) {
					$left = false;
					$i++;
					$right = $this->evalE( $i, self::$operators[$oper], $e );
					continue;
				}
				if( self::$operators[$oper] <= $min_prec ) {
					return $left;
				}
	
				$i++;
				$ip=$i;
				$right = $this->getOperand( $i );
				if( isset( $this->a[$i] ) && !($e&&$this->a[$i]==$e) ) {
					if( isset(self::$operators[$this->a[$i]]) ) {
						$noper = $this->a[$i];
						if( self::$operators[$oper] < self::$operators[$noper] ) {
							$i=$ip;
							$right = $this->evalF( $i, self::$operators[$oper], $e );
						}
					} else {
						throw new ArdeException( 'unknown operator '.$this->a[$i].' at offset '.$i );
					}
				}
				$left = $this->evalOp($left,$oper,$right);
			}
		}
	
		private function evalOp( $left, $op, $right ) {

			if($op == '|' ) {
				return $left || $right;
			} elseif($op == '&' ) {
				return $left && $right;
			} elseif($op == '+' ) {
				return $left + $right;
			} elseif($op == '-' ) {
				return $left - $right;
			} elseif($op == '*' ) {
				return $left * $right;
			} elseif($op == '/' ) {
				return $left / $right;
			} elseif($op == '!' ) {
				return !$right;
			} elseif( $op == '<' ) {
				return $left < $right;
			} elseif( $op == '>' ) {
				return $left > $right;
			} elseif( $op == '<=' ) {
				return $left <= $right;
			} elseif( $op == '>=' ) {
				return $left >= $right;
			} elseif( $op == '=' ) {
				return $left == $right;
			} elseif( $op == '!=' ) {
				return $left != $right;
			}
		}
	}

?>