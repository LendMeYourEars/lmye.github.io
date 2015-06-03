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
	
	require_once dirname(__FILE__).'/ArdeException.php';
	
	class ArdePrinter implements ArdeErrorReporter {
        
		private $tab = 0;
        private $tabs = array();
        private $newLine = false;
      	private $forceTabProcess = false;
        
      	protected $showMutedErrors = false;
      	protected $hideErrors = false;
      	
        protected function o( $s ) {
        	echo $s;
		}
		
		public function pn( $s ) {

			if( $this->newLine || $this->forceTabProcess) {
				if( $this->newLine ) {
					$this->o( "\n" );
					$this->newLine = false;
				}
				if( $this->tab > 0 ) {
            		for( $i = 0 ; $i < $this->tab ; ++$i ) {
            			$this->o( "\t" );
					}
				} else if( $this->tab < 0 ) { 
					for( $i = 0 ; $i < -$this->tab ; ++$i ) {
						if( !isset( $s{$i} ) ) break;
						if( $s{$i} != "\t" ) break;
					}

					$s = substr( $s, $i, strlen( $s ) - $i );
				}
				$this->forceTabProcess = false;
				
			}
			$this->o( $s );
		}
		
		public function pl( $s, $holdTab = null ) {
			
			$this->pn( $s );

			$this->newLine = true;

			if( $holdTab !== null && strlen( $s ) ) {
                $i = 0;
                while( isset( $s[$i] ) && $s{$i} == "\t" ) ++$i;
                
                $this->hold( $i + $holdTab );
                
            }
            
		}
		
		public function pm( $s ) {
			$prevI = 0;
        	for( $i = 0 ; $i < strlen( $s ) ; ++$i ) {
        		if( $s{$i} == "\n" ) {
        			$this->pl( substr( $s, $prevI, $i - $prevI ));
        			$prevI = $i + 1;
				}
			}
			if( $prevI < strlen( $s ) ) {
				$this->pn( substr( $s, $prevI, strlen( $s ) - $prevI ));
			}
		}
		
		public function cancelNl() {
			$this->newLine = false;
		}
        
        public function hold( $n ) {
        	array_push( $this->tabs, $n );
        	$this->tab += $n;
		}

		public function currentIndent() {
			return $this->tab;
		}
		
        public function rel() {
        	if( count( $this->tabs ) ) {
	            $i = array_pop( $this->tabs );
	            $this->tab -= $i;
        	}
        }
        
        public function nl() {
	        $this->newLine = true;
		}
        
        public function relnl() {
        	$this->nl();
        	$this->rel();        	
		}
	
		public function setHideErrors( $hideErrors ) {
			$this->hideErrors = $hideErrors;
		}
		
		public function setMutedErrors( $showMutedErrors ) {
			$this->showMutedErrors = $showMutedErrors;
		}
		
		public function reportError( ArdeException $exception ) {
			if( $this->hideErrors && !( $exception instanceof ArdeException && $exception->getType() == ArdeException::USER_ERROR ) ) return;
			if( $this->showMutedErrors ) {
				$this->o( $exception->__mutedToString() );
			} else {
				$this->o( $exception->__toString() );
			}
		}
		
		public function uncaughtExceptionHandler( $exception ) {
			try {
				if( $exception instanceof ArdeException ) {
					$this->reportError( $exception );
				} else {
					echo $exception->__toString();
				}
			} catch( Exception $e ) {
				echo $e->__toString();
			}
		}
	}
	
	class ArdePrintJob {
		const PL = 1;
		const PN = 2;
		const PM = 3;
		const NL = 4;
		const REL = 5;
		const HOLD = 6;
		const CANCEL_NL = 7;
		
		public $type;
		public $param1;
		public $param2;
		
		function __construct( $type, $param1 = null, $param2 = null ) {
			$this->type = $type;
			$this->param1 = $param1;
			$this->param2 = $param2;
		}
		
		function execute( ArdePrinter $p ) {
			if( $this->type == ArdePrintJob::PL ) return $p->pl( $this->param1, $this->param2 );
			elseif( $this->type == ArdePrintJob::PN ) return $p->pn( $this->param1 );
			elseif( $this->type == ArdePrintJob::PM ) return $p->pm( $this->param1 );
			elseif( $this->type == ArdePrintJob::REL ) return $p->rel();
			elseif( $this->type == ArdePrintJob::NL ) return $p->nl();
			elseif( $this->type == ArdePrintJob::HOLD ) return $p->hold( $this->param1 );
			elseif( $this->type == ArdePrintJob::CANCEL_NL ) return $p->cancelNl();
		}
	}
	
	class ArdeJobPrinter extends ArdePrinter {
		public $jobs;
		
		function __construct() {
			$this->jobs = array();
		}
		public function pl( $s, $holdTabs = null ) {
			$this->jobs[] = new ArdePrintJob( ArdePrintJob::PL, $s, $holdTabs );

		}
		
		public function pn( $s ) {
			$this->jobs[] = new ArdePrintJob( ArdePrintJob::PN, $s );
		}
		
		public function pm( $s ) {
			$this->jobs[] = new ArdePrintJob( ArdePrintJob::PM, $s );
		}
		
		public function nl() {
			$this->jobs[] = new ArdePrintJob( ArdePrintJob::NL );
		}
		
		public function rel() {
			$this->jobs[] = new ArdePrintJob( ArdePrintJob::REL );
		}
		
		public function hold( $n ) {
			$this->jobs[] = new ArdePrintJob( ArdePrintJob::HOLD, $n );
		}
		
		public function cancelNl() {
			$this->jobs[] = new ArdePrintJob( ArdePrintJob::CANCEL_NL );
		}
		
		public function flush( ArdePrinter $p ) {
			foreach( $this->jobs as $job ) {
				$job->execute( $p );
			}
			$this->jobs = array();
		}
	}
    
    class ArdeBufferedPrinter extends ArdePrinter {
    	public $buf  = '';
    	public $hold = false;
    	public function holdBuffer() {
    		$this->hold = true;
		}
		public function releaseBuffer() {
			parent::o( $this->buf );
			$this->buf = '';
			$this->hold = false;
		}
		
		public function o( $s ) {
			if( $this->hold ) $this->buf .= $s;
			else parent::o( $s );
		}
	}
	
    class ArdeStringPrinter extends ArdePrinter {
		public $s;
		public function __construct( &$s ) {
			$this->s = &$s;
		}
		public function o( $s ) {
			$this->s .= $s;
		}	
	}
    
    class ArdeReplaceableText {
    	public $id;
    	public $text;
    	public function ArdeReplaceableText( $id, $text ) {
    		$this->id = $id;
    		$this->text = $text;
		}
	}
	
    class ArdeReplaceableTextPrinter extends ArdePrinter {
    	public $s = '';
    	public $elements = array();
    	
    	public function o( $s ) {
    		$this->s .= $s;
		}
		
		public function pn( $s ) {
			if( $s instanceof ArdeReplaceableText ) {
				$this->elements[] = $this->s;
				$this->s = '';
				$this->elements[] = $s;
			} else {
				parent::pn( $s );
			}
		}
		
		public function getText( $reps = array() ) {
			if( $this->s != '' ) {
				$this->elements[] = $this->s;
				$this->s = '';
			}
			$o = '';
			foreach( $this->elements as &$e ) {
				if( is_object( $e )) {
					if( isset( $reps[ $e->id ])) $o .= $reps[ $e->id ];
					else $o .= $e->text;
				} else {
					$o .= $e;
				}
			}
			return $o;
		}
	}
?>