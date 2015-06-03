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

	require_once $ardeBase->path( 'lib/ArdeXmlPrinter.php' );
	
	class ArdePage {
		
		protected function init() {}
		
		protected function sendHeader() {}
		
		public function render( ArdePrinter $p ) {
			if( $this->init() !== false ) {
				if( $this->sendHeader() !== false ) {
					$this->printPage( $p );
				}
			}
		}

		const REDIRECT_PERMANENT = 301;
		const REDIRECT_SEE_OTHER = 303;
		const REDIRECT_TEMPORARY = 307;
		
		protected function redirect( $location, $code = self::REDIRECT_PERMANENT ) {
			header( 'Location: '.$location, true, $code );
			return false;
		}
		
		protected function printPage( ArdePrinter $p ) {}
	}
	
	class ArdeHtmlPage extends ArdePage {
		
		protected function getXmlLangCode() {
			return 'en';
		}
		
		protected function printPage( ArdePrinter $p ) {
            $p->pl( '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' );
            $p->pl( '<html xmlns="http://www.w3.org/1999/xhtml" lang="'.$this->getXmlLangCode().'">' );
            $p->pl( '<head>', 1 );
            $this->printInHtmlHead( $p );
            $p->rel();
            $p->pl('</head>');
            $p->pl('<body>', 1 );
            $this->printInHtmlBody( $p );
            $p->rel();
            $p->pl('</body>');
            $p->pn('</html>');
		}
		
		protected function getCharset() {
			return 'utf-8';
		}
		
		protected function getTitle() {
			return '';
		}
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			$p->pl( '<meta http-equiv="content-type" content="text/html; charset='.$this->getCharset().'" />' );
			$title = $this->getTitle();
			if( $title ) {
	        	$p->pl('<title>'.ardeXmlEntities( $title ).'</title>' );
	        }
		}
		
		protected function printInHtmlBody( ArdePrinter $p ) {
			$this->printHeader( $p );
			$this->printBody( $p );
			$this->printFooter( $p );
		}
		
		protected function printHeader( ArdePrinter $p ) {}

		protected function printBody( ArdePrinter $p ) {}
		
		protected function printFooter( ArdePrinter $p ) {}
		
	}
	
		
?>