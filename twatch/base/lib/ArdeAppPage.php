<?php
	require_once $ardeBase->path( 'lib/Page.php' );
	
	class ArdeAppPage extends ArdeHtmlPage {
		var $title = '';
		
		/**
		 * @var string relative path from this page's folder to the root of the app
		 */
		
		var $app;
		

		var $main_center = false;
		
		private $leftButtons;
		
		
		

		function __construct( ArdeApp $app ) {
			$this->inHead = new ArdeJobPrinter();
			$this->inList = new ArdeJobPrinter();
			$this->app = $app;
		}

		protected function printInLangCont( ArdePrinter $p ) {}
		
		public $subTitle = 'Website Statistics';
		
		
		protected function getTitle() {
			return $this->title;
		}
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			parent::printInHtmlHead( $p );
			$p->pl( '<link rel="stylesheet" href="'.$this->app->baseUrl( $this->getToRoot(), 'css/main.css' ).'" />' );
			if( $this->isRightToLeft() ) {
				$p->pl( '<link rel="stylesheet" href="'.$this->app->baseUrl( $this->getToRoot(), 'css/main_rtl.css' ).'" />' );
			}
			$this->inHead->flush( $p );
		}
		
		protected function hasDoublePadBody() {
			return false;
		}
		
		protected function isRightToLeft() {
			return false;
		}
		
		protected function hasList() {
			return false;
		}
		
		protected function printInList( ArdePrinter $p ) {} 
		
		protected function getTopButtons() {
			return array();
		}
		
		protected function getToRoot() {
			return '.';
		}
		
		protected function getSelectedTopButton() {
			return -1;
		}
		
		protected function getLeftButtons() {
			return array(); 
		}
		
		protected function getSelectedLeftButton() {
			return -1;
		}
		
		protected function hasLangCont() {
			return false;
		}
		
		protected function printHeader( ArdePrinter $p ) {
			if( $this->hasLangCont() ) {
				$p->pl( '	<div id="lang_cont">', 1 );
				$this->printInLangCont( $p );
				$p->rel();
			}
			$p->pl( '	</div>' );
			$p->pl( '	<h1 class="main"><a href="http://www.tracewatch.com/"><img alt="TraceWatch" title="TraceWatch" src="'.$this->app->baseUrl( $this->getToRoot(), 'img/tracewatch'.($this->isRightToLeft()?'_rtl':'').'.gif' ).'" width="217" height="95" style="border:0px" /><span>'.$this->subTitle.'</span></a></h1>' );
			$p->pl( '	<div id="top_bar">' );
			$p->pl( '		<div id="top_bar_inside">' );
			$p->pl( '			<div class="clear"></div>' );
			
			$p->pl( '			<div class="list">&nbsp;', 1 );
			$this->printInList( $p );
			$p->relnl();
			$p->pl( '			</div>', 0 );
			
			$topButtons = $this->getTopButtons();
			if( count( $topButtons ) ) {
				$p->pl( '<ul id="top_nav">', 1 );
				foreach( $topButtons as $i => $button ) {
					$p->pn( '<li>' );
					if( $this->getSelectedTopButton() != $i ) $p->pn( '<a href="'.$button[1].'">' );
					$p->pn( $button[0] );
					if( $this->getSelectedTopButton() != $i ) $p->pn( '</a>' );
					$p->pl( '</li>' );
				}
				$p->rel();
				$p->pl( '</ul>' );
			}
			$p->rel();
			$p->pl( '			<div class="clear"></div>' );
			$p->pl( '		</div>' );
			$p->pl( '	</div>', 0 );

			$this->leftButtons = $this->getLeftButtons();
			if( count( $this->leftButtons ) ) {
				$p->pl( '<table id="top_cont" cellpadding="0" cellspacing="0" border="0">' );
				$p->pl( '<tr>' );
				$p->pl( '	<td id="left_col">' );
				$p->pl( '		<ul class="nav">', 1 );
				foreach( $this->leftButtons as $i => $button ) {
					$p->pn( '<li '.($this->getSelectedLeftButton()==$i?'class="selected" ':'').'>' );
					if( $this->getSelectedLeftButton() != $i ) $p->pn( '<a href="'.$button[1].'">' );
					$p->pn( $button[0] );
					if( $this->getSelectedLeftButton() != $i ) $p->pn( '</a>' );
					$p->pl( '</li>' );
				}
				$p->rel();
				$p->pl( '		</ul>' );
				$p->pl( '	</td>' );
				$p->pl( '	<td id="main" class="'.($this->hasDoublePadBody()?'pad_double':'pad_std').'"'.($this->main_center?' style="text-align:center;"':'').'>', 1 );
			} else {
				$p->pl( '<div id="main" class="'.($this->hasDoublePadBody()?'pad_double':'pad_std').'">', 1 );
			}
		}
		
		
			
			
			
			
			
		
		protected function printFooter( ArdePrinter $p ) {
			$p->relnl();
			if( count( $this->leftButtons ) ) {
				$p->pl( '	</td>' );
				$p->pl( '</tr></table>' );
			} else {
				$p->pl( '</div>' );
			}
			$p->rel();
			$p->pl( '	<div lang="en-US" xml:lang="en-US" id="footer">' );
			$p->pn( '		<div class="inside">', 1 );
			$this->printInFooter( $p );
			$p->rel();
			$p->pl( '		</div>' );
			$p->pl( '		<div class="break"></div>' );
			$p->pl( '	</div>' );
		}
		
		
		
		protected function printInFooter( ArdePrinter $p ) {}
		
		
	}
?>