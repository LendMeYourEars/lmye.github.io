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
    
	class TwatchTimeZone {
		public $difference = 0;
		public $name = 'GMT';
		
		
		public function __construct( $difference, $name ) {
			$this->difference = $difference;
			$this->name = $name;
		}
		
		public static function fromConfig() {
			global $twatch;
			$difference = $twatch->config->get( TwatchConfig::TIME_DIFFERENCE );
			$name = $twatch->config->get( TwatchConfig::TIME_ZONE_NAME );
			return new self( $difference, $name );
		}
		
		public function applyToConfig() {
			global $twatch;
			if( $twatch->config->get( TwatchConfig::TIME_DIFFERENCE ) != $this->difference ) {
				$twatch->config->set( $this->difference, TwatchConfig::TIME_DIFFERENCE );
			}
			if( $twatch->config->get( TwatchConfig::TIME_ZONE_NAME ) != $this->name ) {
				$twatch->config->set( $this->name, TwatchConfig::TIME_ZONE_NAME );
			}
		}
		
		public static function fromParams( $a, $prefix = '' ) {
			$sign = ArdeParam::str( $a, $prefix.'sg' );
			$hs = ArdeParam::int( $a, $prefix.'hs', 0, 13 );
			$ms = ArdeParam::int( $a, $prefix.'ms', 0, 45 );
			
			
			
			$difference = $hs * 3600 + $ms * 60;
			if( $sign == 'm' ) $difference *= -1;
					
			$name = ArdeParam::str( $a, $prefix.'n' );
			
			return new self( $difference, $name );
		}
		
		public function printXml( ArdePrinter $p, $tagName, $extraConfig = '' ) {
			$p->pn( '<'.$tagName.' diff="'.$this->difference.'" name="'.ardeXmlEntities( $this->name ).'"'.$extraConfig.' />' );
		} 
		
		public function jsObject( $prefix = '', $embedded = false ) {
			return 'new TimeZone( '.time().', '.$this->difference.', '."'".ArdeJs::escape( $this->name )."'".", '".$prefix."', ".ArdeJs::bool( $embedded )." )";
		}
		
		public function isEquivalent( self $tz ) {
			if( $this->difference != $tz->difference ) return false;
			if( $this->name != $tz->name ) return false;
			return true;
		}
	}
?>