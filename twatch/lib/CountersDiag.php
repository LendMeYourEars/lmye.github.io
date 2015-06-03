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
    
	class TwatchCountersDiagInfo {
		public $sizes;
		public $totalSize;
		
		public function load() {
			global $twatch;
			
			$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
			$counters = $twatch->config->getList( TwatchConfig::COUNTERS );
			$this->sizes = array();
			foreach( $counters as $counter ) {
				$this->sizes[ $counter->name ] = 0;
			}
			
			$this->totalSize = 0;
			foreach( $websites as $website ) {
				if( $website->parent ) continue;
				$dbCounters = new TwatchDbHistory( $twatch->db, $website->getSub() );
				$wSizes = $dbCounters->getSizes();
				foreach( $wSizes as $counterId => $rows ) {
					if( !isset( $counters[ $counterId ] ) ) {
						if( !isset( $this->sizes[ 'unknown '.$counterId ] ) ) $this->sizes[ 'unknown '.$counterId ] = $rows; 
						$this->sizes[ 'unknown '.$counterId ] += $rows;
					} else {
						$this->sizes[ $counters[ $counterId ]->name ] += $rows;
					}
				}
				$this->totalSize += $dbCounters->getTotalSize();
				
			}
		}
		
		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' total_size="'.$this->totalSize.'"'.$extraAttrib.'>' );
			$p->pl( '	<sizes>', 1 );
			foreach( $this->sizes as $counterName => $rows ) {
				$p->pl( '<counter name="'.htmlentities( $counterName ).'" rows="'.$rows.'" />' );
			}
			$p->rel();
			$p->pl( '	</sizes>' );
			$p->pn( '</'.$tagName.'>' );
		}
	}
?>