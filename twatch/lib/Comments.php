<?php
	require_once $twatch->path( 'db/DbComments.php' );
	
	class TwatchComment {
		const VIS_PUBLIC = 1;
		const VIS_PRIVATE = 2;
		
		public $id;
		public $txt;
		/**
		 * @var TwatchTime
		 */
		public $time;
		public $visibility;
		
		public function __construct( $id, $txt, $time, $visibility ) {
			$this->id = $id;
			$this->txt = $txt;
			$this->time = $time;
			$this->visibility = $visibility;
		}
		
		public function adminJsObject() {
			return "new Comment( ".$this->id.", '".ArdeJs::escape( $this->time->format( 'Y M d' ) )."', '".ArdeJs::escape( $this->txt )."', ".ArdeJs::bool( $this->visibility == self::VIS_PRIVATE )." )";
		}
		
		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' id="'.$this->id.'" date="'.ardeXmlEntities( $this->time->format( 'Y M d' ) ).'" private="'.($this->visibility == self::VIS_PRIVATE?'true':'false').'"'.$extraAttrib.'>' );
			$p->pl( '	'.$this->txt );
			$p->pn( '</'.$tagName.'>' );
		}
	}
	
	class TwatchComments {
		
		var $dbComments;
		
		public function __construct() {
			global $twatch;
			$this->dbComments = new TwatchDbComments( $twatch->db );
		}
		
		public function add( TwatchTime $time, $txt, $visibility ) {
			return $this->dbComments->add( $time->getDayCode(), $txt, $visibility );
		}
		
		public function remove( $id ) {
			$this->dbComments->remove( $id );
		}
		
		public function getAll() {
			$dbComments = $this->dbComments->getAll();
			return $this->returnComments( $dbComments );
		}
		
		protected function returnComments( $dbComments ) {
			$comments = array();
			foreach( $dbComments as $dbComment ) {
				$time = new TwatchTime();
				$time->initWithDayCode( $dbComment[ 'dt' ] );
				$comments[] = new TwatchComment( $dbComment[ 'id' ], $dbComment[ 'txt' ], $time, $dbComment[ 'visibility' ] );
			}
			return $comments;
		} 
		
		public function get( $minCode, $maxCode, $maxVisibility ) {
			$dbComments = $this->dbComments->get( $minCode, $maxCode, $maxVisibility );
			$comments = array();
			foreach( $dbComments as $dbComment ) {
				$time = new TwatchTime();
				$time->initWithDayCode( $dbComment[ 'dt' ] );
				$comments[ $dbComment[ 'dt' ] ][] = new TwatchComment( $dbComment[ 'id' ], $dbComment[ 'txt' ], $time, $dbComment[ 'visibility' ] );
			}
			return $comments;
		}
		
		public function install( $overwrite ) {
			$this->dbComments->install( $overwrite );
		}
		
		public function uninstall() {
			$this->dbComments->uninstall();
		}
	}
?>