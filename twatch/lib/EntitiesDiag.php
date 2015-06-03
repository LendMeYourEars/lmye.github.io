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
    
	class TwatchEntitiesDiagInfo {
		public $sizes;
		public $totalSize;
		
		public function load() {
			global $twatch;
			
			$dicts = &$twatch->config->getList( TwatchConfig::DICTS );
			
			$this->sizes = array();
			foreach( $dicts as $dict ) {
				$this->sizes[ $dict->pluralName ] = 0;
			}
			
			$this->totalSize = TwatchDbDict::getTotalSize( $twatch->db );
			
			$sizes = TwatchDbDict::getSizes( $twatch->db );
			foreach( $sizes as $dictId => $rows ) {
				if( !isset( $dicts[ $dictId ] ) ) {
					if( !isset( $this->sizes[ 'unknown' ] ) ) $this->sizes[ 'unknown' ] = $rows; 
					$this->sizes[ 'unknown' ] += $rows;
				} else {
					$this->sizes[ $dicts[ $dictId ]->pluralName ] += $rows;
				}
			}

		}
		
		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' total_size="'.$this->totalSize.'"'.$extraAttrib.'>' );
			$p->pl( '	<sizes>', 1 );
			foreach( $this->sizes as $counterName => $rows ) {
				$p->pl( '<dict name="'.htmlentities( $counterName ).'" rows="'.$rows.'" />' );
			}
			$p->rel();
			$p->pl( '	</sizes>' );
			$p->pn( '</'.$tagName.'>' );
		}
	}
?>