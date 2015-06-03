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

	class TwatchLatestPage implements ArdeSerializable {
		public $perPage;
		public $requestPerSession;
		public $requestPerSingleSession;
		
		public static $defaults;

		const ITEM_REF = 0;
		const ITEM_BRO = 1;
		const ITEM_AGT = 2;
		const ITEM_IP = 1;
		const ITEM_PIP = 2;

		const EV_EXTRAS_EXISTS = 1;
		const EV_EXTRAS_DOESNT_EXIST = 2;

		const DEFAULT_REQ_PER_SES = 50;
		const DEFAULT_REQ_PER_SINGLE_SES = 100;
		
		public $priItems = array();
		public $secItems = array();

		private $visitorTypeFilter;
		private $visitorTypes;
		private $entityFilters;

		public $start;
		public $more;
		public $onlineVisitors;

		public $defaultVtSelection;
		
		public $singleSessionId;

		function TwatchLatestPage( $perPage, $requestPerSession, $requestPerSingleSession, $defaultVtSelection ) {
			$this->perPage = $perPage;
			$this->defaultVtSelection = $defaultVtSelection;
			$this->start = 0;
			$this->more = false;
			$this->onlineVisitors = 0;
			$this->entityFilters = array();
			$this->singleSessionId = null;
			$this->requestPerSession = $requestPerSession;
			$this->requestPerSingleSession = $requestPerSingleSession;
		}

		public function getSerialData() {
			return new ArdeSerialData( 3, array( $this->perPage, $this->priItems, $this->secItems, $this->defaultVtSelection, $this->requestPerSession, $this->requestPerSingleSession ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			if( $d->version < 3 ) {
				$requestPerSession = self::DEFAULT_REQ_PER_SES;
				$requestPerSingleSession = self::DEFAULT_REQ_PER_SINGLE_SES;
			} else {
				$requestPerSession = $d->data[4];
				$requestPerSingleSession = $d->data[5];
			}
			if( $d->version < 2 ) {
				$vt = array( TwatchVisitorType::NORMAL, TwatchVisitorType::ROBOT, TwatchVisitorType::ADMIN, TwatchVisitorType::SPAMMER );
			} else {
				$vt = $d->data[3];
			}
			$o = new self( $d->data[0], $requestPerSession, $requestPerSingleSession, $vt );
			$o->priItems = $d->data[1];
			$o->secItems = $d->data[2];

			return $o;
		}

		public function isEquivalent( self $latestPage ) {
			if( $latestPage->perPage != $this->perPage ) return false;
			if( !ardeEquivOrderedArrays( $latestPage->priItems, $this->priItems ) ) return false;
			if( !ardeEquivOrderedArrays( $latestPage->secItems, $this->secItems ) ) return false;
			if( !ardeEquivSets( $latestPage->defaultVtSelection, $this->defaultVtSelection ) ) return false;
			return true;
		}

		function init() {
			global $twatch, $ardeUser;
			$this->visitorTypes = array();
			foreach( $twatch->config->getList( TwatchConfig::VISITOR_TYPES ) as $id => $vt ) {
				if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_ADMIN_IN_LATEST ) || $id != TwatchVisitorType::ADMIN ) {
					$this->visitorTypes[ $id ] = $vt;
				}
			}
			
			$this->visitorTypeFilter = array();
			foreach( $this->visitorTypes as $id => $visitorType ) {
				$this->visitorTypeFilter[] = $id;
			}
		}
		
		
		function initSingle( $sessionId ) {
			$this->singleSessionId = $sessionId;
		}
		
		function initLatest( $entityFilters, $visitorTypeFilter, $start, $more, $onlineVisitors ) {
			global $twatch, $ardeUser;

			
			if( $visitorTypeFilter !== false ) {
				$this->visitorTypeFilter = $visitorTypeFilter;
			}

			$this->start = $start;
			$this->more = $more;
			$this->onlineVisitors = $onlineVisitors;
			
			$entVGen = new TwatchEntityVGen();
			foreach( $entityFilters as $entityId => $entityVId ) {
				if( $entityVId === 'e' ) {
					$entityV = self::EV_EXTRAS_EXISTS;
				} elseif( $entityVId === 'de' ) {
					$entityV = self::EV_EXTRAS_DOESNT_EXIST;
				} else {
					$entityV = &$entVGen->make( $entityId, $entityVId );
				}
				$this->entityFilters[ $entityId ] = $entityV;
			}
			$entVGen->finalizeEntityVs();
		}

		function adminJsObject( $viewPermission ) {
			global $twatch;
			$pri = new ArdeAppender( ', ' );
			foreach( $this->priItems as $item ) $pri->append( $item->adminJsObject() );
			$sec = new ArdeAppender( ', ' );
			foreach( $this->secItems as $item ) $sec->append( $item->adminJsObject() );

			$vtSel = new ArdeAppender( ', ' );
			foreach( $this->defaultVtSelection as $vtId ) $vtSel->append( $vtId.':true' );
			return 'new AdminLatestPage( '.$this->perPage.', [ '.$pri->s.' ], [ '.$sec->s.' ], { '.$vtSel->s.' }, '.$viewPermission.' )';
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' per_page="'.$this->perPage.'"'.$extraAttrib.'>' );
			$p->pl( '	<pri_items>', 1 );
			foreach( $this->priItems as $item ) {
				$item->printXml( $p, 'item' );
				$p->nl();
			}
			$p->rel();
			$p->pl( '	</pri_items>' );
			$p->pl( '	<sec_items>', 1 );
			foreach( $this->secItems as $item ) {
				$item->printXml( $p, 'item' );
				$p->nl();
			}
			$p->rel();
			$p->pl( '	</sec_items>' );
			$p->pl( '	<vt_selection>', 1 );
			foreach( $this->defaultVtSelection as $vtId ) {
				$p->pn( '<vt id="'.$vtId.'" />' );
			}
			$p->relnl();
			$p->pl( '	</vt_selection>' );
			$p->pn( '</'.$tagName.'>' );
		}

		public static function fromParams( $a ) {
			global $twatch;

			$perPage = ArdeParam::int( $a, 'pp', 1 );

			$vtSel = ArdeParam::intArr( $a, 'vts', '_' );
			if( !count( $vtSel ) ) throw new TwatchUserError( 'Choose at least one visitor type' );
			foreach( $vtSel as $vtId ) {
				if( !$twatch->config->propertyExists( TwatchConfig::VISITOR_TYPES, $vtId ) ) {
					throw new TwatchUserError( 'Visitor type '.$vtId.' does not exist.' );
				}
			}

			$o = new self( $perPage, self::DEFAULT_REQ_PER_SES, self::DEFAULT_REQ_PER_SINGLE_SES, $vtSel );

			$pItemsCount = ArdeParam::int( $a, 'pic', 0 );
			$sItemsCount = ArdeParam::int( $a, 'sic', 0 );

			for( $i = 0; $i < $pItemsCount; ++$i ) {
				$o->priItems[] = TwatchLatestItem::fromParams( $a, 'pi'.$i.'_' );
			}

			for( $i = 0; $i < $sItemsCount; ++$i ) {
				$o->secItems[] = TwatchLatestItem::fromParams( $a, 'si'.$i.'_' );
			}

			return $o;
		}

		public function usesEntity( $entityId ) {
			foreach( $this->priItems as $item ) {
				if( $item->entityId == $entityId ) return 'primary items of Latest Visitors Page';
			}
			foreach( $this->secItems as $item ) {
				if( $item->entityId == $entityId ) return 'secondary items of Latest Visitors Page';
			}
			return false;
		}

		function jsObject() {
			global $twatch, $ardeUser;

			$pri = new ArdeAppender( ', ' );
			foreach( $this->priItems as $item ) {
				if( !$item->isViewable() ) continue;
				$pri->append( $item->jsObject() );
			}
			$sec = new ArdeAppender( ', ' );
			foreach( $this->secItems as $item ) {
				if( !$item->isViewable() ) continue;
				$sec->append( $item->jsObject() );
			}

			$s = 'new LatestPage( ';
			$s .= $this->perPage;
			$s .= ', '.$this->requestPerSession;
			$s .= ', '.$this->requestPerSingleSession;
			$s .= ', [ '.$pri->s.' ]';
			$s .= ', [ '.$sec->s.' ]';
			$s .= ', [';
			$csa = new ArdeAppender( ', ' );
			$entities = &$twatch->config->getList( TwatchConfig::ENTITIES );
			
			foreach( $this->entityFilters as $entityId => &$entityV ) {
				if( is_int( $entityV ) ) {
					$csa->append( 'new Filter( '.$entityId.', '.$entityV.', "'.$twatch->locale->text( ($entityV == self::EV_EXTRAS_EXISTS?'with':'without')." {something}", array( 'something' => $twatch->locale->text( $entities[ $entityId ]->name ) ) ).'" )' );
				} else {
					$csa->append( 'new Filter( '.$entityId.', '.$entityV->jsObject( new TwatchEntityView( true, true, false, EntityV::STRING_VISITOR_TITLE ), EntityV::JS_MODE_INLINE ).', "'.$twatch->locale->text( $entities[ $entityId ]->visitorTitle ).'" )' );
				}
			}
			
			$s .= $csa->s;
			$s .= ']';
			$s .= ', new Array(';
			$vtA = new ArdeAppender( ', ' );
			foreach( $this->visitorTypes as $visitorType ) {
				$vtA->append( $visitorType->jsObject() );
			}
			$s .= $vtA->s;
			$s .= ' )';
			$s .= ', {';
			$viscs = new ArdeAppender( ', ' );
			foreach( $this->visitorTypeFilter as $visitorTypeId ) {
				$viscs->append( $visitorTypeId.':true' );
			}
			$s .= $viscs->s;
			$s .= '}';
			$s .= ', '.$this->start.', '.($this->more?'true':'false');
			$s .= ', '.$this->onlineVisitors;
			$vtSel = new ArdeAppender( ', ' );
			foreach( $this->defaultVtSelection as $vtId ) {
				if( !$ardeUser->user->hasPermission( TwatchUserData::VIEW_ADMIN_IN_LATEST ) && $vtId == TwatchVisitorType::ADMIN ) continue;
				$vtSel->append( $vtId.':true' );
			}
			$s .= ', { '.$vtSel->s.' }';
			
			$s .= ', '.($this->singleSessionId===null?'null':$this->singleSessionId);
			
			$s .= ' )';

			return $s;
		}
	}


	class TwatchLatestItem implements ArdeSerializable {
		var $title;
		var $entityId;
		var $entityView;
		var $lookup;
		var $notFound;

		const LOOKUP_FIRST = 0;
		const LOOKUP_LAST = 1;

		public static $lookupStrings = array(
			 self::LOOKUP_FIRST => 'first'
			,self::LOOKUP_LAST => 'last'
		);

		function __construct( $entityId, $entityView, $lookup, $title, $notFound ) {
			$this->entityId = $entityId;
			$this->entityView = $entityView;
			$this->lookup = $lookup;
			$this->title = $title;
			$this->notFound = $notFound;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->entityId, $this->entityView, $this->lookup, $this->title, $this->notFound ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3], $d->data[4] );
		}

		public function isEquivalent( self $item ) {
			if( $item->title != $this->title ) return false;
			if( $item->entityId != $this->entityId ) return false;
			if( !$item->entityView->isEquivalent( $this->entityView ) ) return false;
			if( $item->lookup != $this->lookup ) return false;
			if( $item->notFound != $this->notFound ) return false;
			return true;
		}

		public function isViewable() {
			global $twatch, $ardeUser;
			if( !$twatch->config->propertyExists( TwatchConfig::ENTITIES, $this->entityId ) ) return false;
			if( !$twatch->config->get( TwatchConfig::ENTITIES, $this->entityId )->isViewable( $ardeUser->user ) ) return false;
			return true;
		}
		
		public static function fromParams( $a, $prefix = '' ) {
			global $twatch;

			$entityId = ArdeParam::int( $a, $prefix.'ei' );
			if( !$twatch->config->propertyExists( TwatchConfig::ENTITIES, $entityId ) ) throw new TwatchException( 'entity '.$entityId.' doesn\'t exist' );
			$entityView = TwatchEntityView::fromParams( $a, $prefix.'ev_' );
			$lookup = ArdeParam::int( $a, $prefix.'l' );
			if( !isset( self::$lookupStrings[ $lookup ] ) ) throw new TwatchException( 'lookup '.$lookup.' is invalid' );
			$title = ArdeParam::str( $a, $prefix.'t' );
			if( empty( $title ) ) $title = null;
			$notFound = ArdeParam::str( $a, $prefix.'nf' );
			if( empty( $notFound ) ) $notFound = null;
			return new self( $entityId, $entityView, $lookup, $title, $notFound );
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' entity_id="'.$this->entityId.'" lookup="'.$this->lookup.'"'.$extraAttrib.'>' );
			$p->hold( 1 );
			if( $this->title != null ) {
				$p->pl( '<title>'.htmlentities( $this->title ).'</title>' );
			}
			if( $this->notFound != null ) {
				$p->pl( '<not_found>'.htmlentities( $this->notFound ).'</not_found>' );
			}
			$this->entityView->printXml( $p, 'entity_view' );
			$p->nl();
			$p->rel();
			$p->pl( '</'.$tagName.'>' );
		}

		function jsObject() {
			global $twatch;
			$s = 'new LatestPageItem( ';
			$s .= ( is_null( $this->title ) ? 'null' : "'".ArdeJs::escape( $twatch->locale->text( $this->title ) )."'" );
			$s .= ', '.( is_null( $this->notFound ) ? 'null' : "'".ArdeJs::escape( $twatch->locale->text( $this->notFound ) )."'" );
			$s .= ' )';
			return $s;
		}

		function adminJsObject() {
			$s = 'new LatestPageItem( ';
			$s .= $this->entityId;
			$s .= ', '.$this->entityView->jsObject();
			$s .= ', '.$this->lookup;
			$s .= ', '.( is_null( $this->title ) ? 'null' : "'".ArdeJs::escape( $this->title )."'" );
			$s .= ', '.( is_null( $this->notFound ) ? 'null' : "'".ArdeJs::escape( $this->notFound )."'" );
			$s .= ' )';
			return $s;
		}
	}
?>