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

	class StatsPage implements ArdeSerializable {
		public $id;
		public $name;

		public $width;

		public $periodTypes;

		public $sCounterVs;
		public $lCounterVs;

		public $showComments = true;

		public static $defaults;

		public $periods = array();


		public $highlightedPeriod = array();
		function __construct( $id, $name, $width, $periodTypes ) {
			$this->id = $id;
			$this->name = $name;
			$this->width = $width;
			$this->periodTypes = $periodTypes;
		}



		public function newSCounterViewId() {
			$max = 0;
			foreach( $this->sCounterVs as $sCounterV ) {
				if( $sCounterV->id > $max ) $max = $sCounterV->id;
			}
			return $max + 1;
		}

		public function newLCounterViewId() {
			$max = 0;
			foreach( $this->lCounterVs as $lCounterV ) {
				if( $lCounterV->id > $max ) $max = $lCounterV->id;
			}
			return $max + 1;
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' id="'.$this->id.'" width="'.$this->width.'"'.$extraAttrib.'>' );
			$p->pl( '	<name>'.htmlentities( $this->name ).'</name>' );
			$p->pl( '	<scountervs>', 1 );
			foreach( $this->sCounterVs as $cv ) {
				$cv->printXml( $p, 'counter_view' );
				$p->nl();
			}
			$p->rel();
			$p->pl( '	</scountervs>' );
			$p->pl( '	<lcountervs>', 1 );
			foreach( $this->lCounterVs as $cv ) {
				$cv->printXml( $p, 'counter_view' );
				$p->nl();
			}
			$p->rel();
			$p->pl(	'	</lcountervs>' );
			$p->pn( '</'.$tagName.'>' );
		}

		public function isEquivalent( self $statsPage ) {
			if( $statsPage->id != $this->id ) return false;
			if( $statsPage->name != $this->name ) return false;
			if( $statsPage->width != $this->width ) return false;
			if( !ardeEquivSets( $statsPage->periodTypes, $this->periodTypes ) ) return false;
			if( !ardeEquivOrderedArrays( $statsPage->sCounterVs, $this->sCounterVs ) ) return false;
			if( !ardeEquivOrderedArrays( $statsPage->lCounterVs, $this->lCounterVs ) ) return false;
			return true;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->id, $this->name, $this->width, $this->periodTypes, $this->sCounterVs, $this->lCounterVs ) );
		}

		public static function  makeSerialObject( ArdeSerialData $d ) {
			$o = new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3] );
			$o->sCounterVs = $d->data[4];
			$o->lCounterVs = $d->data[5];
			return $o;
		}

		public function initSingle( TwatchPeriod $period, $counterViewId ) {
			$this->periods[ $period->type ] = array( $period );
			$this->lCounterVs[ $counterViewId ]->init( $this );
		}

		public function init( $year, $month, $day, $today ) {
			global $twatch;

			$periodTypes = array();

			foreach( $this->periodTypes as $periodType ) {
				$this->periods[ $periodType ] = array();
				if( $periodType != TwatchPeriod::ALL ) {
					$periodTypes[] = $periodType;
				} else {
					$this->periods[TwatchPeriod::ALL][0] = new TwatchPeriodAll();
					$this->highlightedPeriod[ TwatchPeriod::ALL ] = -1;
				}

			}

			foreach( $periodTypes as $periodType ) {
				$this->periods[ $periodType ][ 0 ] = TwatchPeriod::makePeriod( $periodType, TwatchTime::getTime()->initWithDate( $year, $month, $day )->ts );
			}

			if( !$today ) {
				$startOffset = ceil( $this->width / 2 ) - 1;
				foreach( $periodTypes as $periodType ) {
					$this->periods[ $periodType ][0] = $this->periods[ $periodType ][0]->offset( $startOffset );
				}
			} else {
				$startOffset = 0;
			}

			for( $i = 1; $i < $this->width; ++$i ) {
				foreach( $periodTypes as $periodType ) {
					$this->periods[ $periodType ][ $i ] = $this->periods[ $periodType ][ 0 ]->offset( -$i );
				}
			}
			foreach( $periodTypes as $periodType ) {
				$this->highlightedPeriod[ $periodType ] = $startOffset;
			}
			$this->initCounters();

		}

		private function initCounters() {
			global $ardeUser;
			foreach( $this->sCounterVs as &$counterV ) {
				if( !$counterV->isViewable( $ardeUser->user ) ) continue;
				$counterV->init( $this );
			}
			foreach( $this->lCounterVs as &$counterV ) {
				if( !$counterV->isViewable( $ardeUser->user ) ) continue;
				$counterV->init( $this );

			}
		}

		public function adminJsObject() {
			return 'new StatsPage( '.$this->id.", '".ArdeJs::escape( $this->name )."', ".$this->width.' )';
		}

		public function jsObject() {


			$pTypeNames = new ArdeAppender( ', ' );
			$pTypeNames->append( TwatchPeriod::DAY.": '".ArdeJs::escape( TwatchPeriod::getTypeString( TwatchPeriod::DAY ) )."'" );
			$pTypeNames->append( TwatchPeriod::MONTH.": '".ArdeJs::escape( TwatchPeriod::getTypeString( TwatchPeriod::MONTH ) )."'" );
			$pTypeNames->append( TwatchPeriod::ALL.": '".ArdeJs::escape( TwatchPeriod::getTypeString( TwatchPeriod::ALL ) )."'" );

			$periods = new ArdeAppender( ', ' );

			foreach( $this->periods as $pType => $ps ) {
				$pTypePeriods = new ArdeAppender( ', ' );
				foreach( $ps as $i => $period ) {
					$pTypePeriods->append( $period->jsObject( $i == $this->highlightedPeriod[ $pType ] ) );
				}
				$periods->append( $pType.', [ '.$pTypePeriods->s.' ]' );
			}

			return 'new StatsPage( '.$this->id.', '.$this->width.', { '.$pTypeNames->s.' }, new ArdeArray( '.$periods->s.' ) )';
		}
	}

	class SingleResult {
		public $originalValue;
		public $roundedValue;
	}

	abstract class CounterView implements ArdeSerializable {
		const DIV_NONE = 0;
		const DIV_HOUR_COUNT = 1;
		const DIV_DAY_COUNT = 2;
		const DIV_DAYS = 3;
		const DIV_HOURS = 4;
		const DIV_VALUE = 5;

		public static $divByStrings = array(
			 self::DIV_NONE => 'none'
			,self::DIV_HOUR_COUNT => 'hours count'
			,self::DIV_DAY_COUNT => 'days count'
			,self::DIV_DAYS => 'days'
			,self::DIV_HOURS => 'hours'
			,self::DIV_VALUE => 'value of'
		);



		public function isViewable( ArdeUserOrGroup $user ) {
			global $twatch;
			if( !$twatch->config->propertyExists( TwatchConfig::COUNTERS, $this->counterId ) ) return false;
			return $twatch->config->get( TwatchConfig::COUNTERS, $this->counterId )->isViewable( $user );
		}
		
		public $results = array();
	}

	class SingleCounterView extends CounterView {
		var $id;
		var $title;
		var $counterId;
		var $periodTypes;
		var $divBy;
		var $divByValueId;
		var $divLimit;
		var $round = -1;
		var $numberTitle;

		var $counter;
		var $counterAvail;
		var $entity;
		var $page;



		function __construct( $id, $title, $numberTitle, $counterId, $periodTypes, $divBy = self::DIV_NONE, $divByValueId = null, $divLimit = 0, $round = -1 ) {
			$this->id = $id;
			$this->title = $title;
			$this->numberTitle = $numberTitle;
			$this->counterId = $counterId;
			$this->periodTypes = $periodTypes;
			$this->divBy = $divBy;
			$this->divByValueId = $divByValueId;
			$this->divLimit = $divLimit;
			$this->round = $round;
		}

		function init( $page ) {
			global $twatch;

			$this->page = $page;
			$this->counter = &$twatch->config->get( TwatchConfig::COUNTERS, $this->counterId );
			$this->counterAvail = $twatch->state->get( TwatchState::COUNTERS_AVAIL, $this->counterId );
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			if( $this->divBy == CounterView::DIV_VALUE ) {
				$divValue = ' value_id="'.$this->divByValueId.'"';
			} else {
				$divValue = '';
			}
			$pTypes = '';
			foreach( $this->periodTypes as $pt ) {
				$pTypes .= '<type>'.$pt.'</type>';
			}

			$p->pl( '<'.$tagName.' id="'.$this->id.'" counter_id="'.$this->counterId.'"'.$extraAttrib.'>' );
			$p->pl(	'	<title>'.ardeXmlEntities( $this->title ).'</title>' );
			$p->pl( '	<number_title>'.ardeXmlEntities( $this->numberTitle ).'</number_title>' );
			$p->pl( '	<div by="'.$this->divBy.'"'.$divValue.' limit="'.$this->divLimit.'" round="'.$this->round.'" />' );
			$p->pl( '	<period_types>'.$pTypes.'</period_types>', 0 );
			$this->printExtraXml( $p );
			$p->relnl();
			$p->pn( '</'.$tagName.'>' );
		}

		public function printExtraXml( ArdePrinter $p ) {}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->id, $this->title, $this->numberTitle, $this->counterId, $this->periodTypes, $this->divBy, $this->divByValueId, $this->divLimit, $this->round ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3], $d->data[4], $d->data[5], $d->data[6], $d->data[7], $d->data[8] );
		}

		public function isEquivalent( self $cView ) {
			if( $this->id != $cView->id ) return false;
			if( $this->title != $cView->title ) return false;
			if( $this->numberTitle != $cView->numberTitle ) return false;
			if( $this->counterId != $cView->counterId ) return false;
			if( !ardeEquivSets( $this->periodTypes, $cView->periodTypes ) ) return false;
			if( $this->divBy != $cView->divBy ) return false;
			if( $this->divByValueId != $cView->divByValueId ) return false;
			if( $this->divLimit != $cView->divLimit ) return false;
			if( $this->round != $cView->round ) return false;
			return true;
		}

		function request( TwatchHistoryReader $historyR ) {
			foreach( $this->page->periods as $periodType => $periods ) {
				if( in_array( $periodType, $this->periodTypes ) ) {
					foreach( $periods as $period ) {
						$this->counter->request( $historyR, $periodType, $period->getCode() );
					}
				}
			}
		}

		function getDivisor( $period ) {
			if( $this->divBy == self::DIV_VALUE ) {
				if( isset( $this->page->sCounterVs[ $this->divByValueId ]->results[ $period->type.'-'.$period->getCode() ]->originalValue ))
					$divisor = $this->page->sCounterVs[ $this->divByValueId ]->results[ $period->type.'-'.$period->getCode() ]->originalValue;
				else
					$divisor = 0;
			} elseif( $this->divBy == self::DIV_DAYS ) {
				$divisor = $period->length( $this->counterAvail->getPortions( $period->type ) ) / 86400;
			} elseif( $this->divBy == self::DIV_HOURS ) {
				$divisor = $period->length( $this->counterAvail->getPortions( $period->type ) ) / 3600;
			} else {
				$divisor = 1;
			}
			return $divisor;
		}

		function getResult( TwatchHistoryReader $historyR ) {
			foreach( $this->page->periods as $periodType => $periods ) {
				if( in_array( $periodType, $this->periodTypes )) {
					foreach( $periods as $period ) {
						$r = $this->counter->getResult( $historyR, $periodType, $period->getCode() );
						$result = new SingleResult();

						if( $this->divBy == self::DIV_NONE ) {
							if( $period->length( $this->counterAvail->getPortions( $period->type ) ) == 0 ) {
								$result->originalValue = $result->roundedValue = null;
							} else {
								$result->originalValue = $result->roundedValue = $r->count;
							}
						} else {
							$divisor = $this->getDivisor( $period );
							if( $divisor < $this->divLimit || $divisor == 0 ) {
								$result->originalValue = $result->roundedValue = null;

							} else {
								$result->roundedValue = $result->originalValue = $r->count / $divisor;
								if( $this->round >= 0 ) {
									$result->roundedValue = round( $result->originalValue * pow( 10, $this->round ) ) / pow( 10, $this->round );
								}
							}
						}

						$this->results[ $periodType.'-'.$period->getCode() ] = $result;

					}

				}
			}
		}

		function adminJsObject( $statsPageVarName ) {

			$periodTypes = new ArdeAppender( ', ' );
			foreach( $this->periodTypes as $pt ) {
				$periodTypes->append( $pt );
			}
			$divByValueId = $this->divByValueId == null ? 'null' : $this->divByValueId;
			$s = 'new SingleCounterView( '.$statsPageVarName.', '.$this->id.", '".ArdeJs::escape( $this->title )."', '".ArdeJs::escape( $this->numberTitle )."', ".$this->counterId.', [ '.$periodTypes->s.' ], ';
			$s .= $this->divBy.', '.$divByValueId.', '.$this->divLimit.', '.$this->round.' )';

			return $s;
		}

		function jsObject() {
			global $twatch;
			$vs = new ArdeAppender( ', ' );

			foreach( $this->page->periods as $dtt => $dts ) {

				foreach( $dts as $period ) {
					if( in_array( $dtt, $this->periodTypes ) && isset( $this->results[ $dtt.'-'.$period->getCode() ]->roundedValue ) ) {
						$vs->append( "'".$twatch->locale->number( $this->results[ $dtt.'-'.$period->getCode() ]->roundedValue )."'" );
					} else {
						$vs->append( 'null' );
					}

				}

			}

			$pTypes = implode( ', ', $this->periodTypes );

			if( $this->divBy == self::DIV_NONE ) $graph = 'true';
			else $graph = 'false';
			return "new SingleCView( '".ArdeJs::escape( $twatch->locale->text( $this->title ) )."', '".ArdeJs::escape( $twatch->locale->text( $this->numberTitle ) )."', ".$this->counterId.", [ ".$vs->s." ], ".$graph.", [ ".$pTypes." ] )";


		}

	}

	class ListCounterView extends SingleCounterView {
		public $rows;
		public $group;
		public $percentRound;
		public $entityView;
		public $startFrom;
		public $subs = array();

		public $graphView = null;
		function __construct( $id, $title, $numberTitle, $counterId, $periodTypes, $entityView = null, $rows = 7, $group = 0, $divBy = self::DIV_NONE, $divByValueId = null, $divLimit = 0, $round = -1, $percentRound = 0, $startFrom = 1 ) {
			$this->id = $id;
			$this->title = $title;
			$this->numberTitle = $numberTitle;
			$this->counterId = $counterId;
			$this->periodTypes = $periodTypes;
			$this->rows = $rows;
			$this->group = $group;
			$this->divBy = $divBy;
			$this->divByValueId = $divByValueId;
			$this->divLimit = $divLimit;
			$this->round = $round;
			$this->percentRound = $percentRound;
			$this->entityView = $entityView;
			$this->startFrom = $startFrom;
		}

		function init( $page ) {
			global $twatch;

			$this->page = $page;
			$this->counter = &$twatch->config->get( TwatchConfig::COUNTERS, $this->counterId );
			$this->counterAvail = &$twatch->state->get( TwatchState::COUNTERS_AVAIL, $this->counterId );
			$this->entity = &$twatch->config->get( TwatchConfig::ENTITIES, $this->counter->entityId );
			if( $this->subs != null ) {
				foreach( $this->subs as $k => $v ) {
					$this->subs[$k]->init( $page );
				}
			}
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			parent::printXml( $p, $tagName, ' rows="'.$this->rows.'" percent_round="'.$this->percentRound.'"'.$extraAttrib );
		}

		public function printExtraXml( ArdePrinter $p ) {
			global $twatch;

			$counter = $twatch->config->get( TwatchConfig::COUNTERS, $this->counterId );

			if( $this->group != null ) {
				$entityId = $counter->groupEntityId;
				$entityV = TwatchEntityVGen::makeFinalized( $entityId, $this->group );
				$entityV->printXml( $p, 'group', new TwatchEntityView( true, false, false, EntityV::STRING_SELECT ) );
				$p->nl();
			}

			$entityId = $counter->entityId;

			if( $twatch->config->propertyExists( TwatchConfig::ENTITIES, $entityId ) ) {
				if( $twatch->config->get( TwatchConfig::ENTITIES, $entityId )->gene->getSet() !== false ) {
					$entityV = TwatchEntityVGen::makeFinalized( $entityId, $this->startFrom );
					$entityV->printXml( $p, 'start_from', new TwatchEntityView( true, false, false, EntityV::STRING_SELECT ) );
					$p->nl();
				}
			}

			$this->entityView->printXml( $p, 'entity_view' );
			$p->nl();
			if( $this->graphView != null ) $p->pl( '<graph_view />' );

			$p->pl( '<subs>', 1 );
			foreach( $this->subs as $sub ) {
				$sub->printXml( $p, 'sub' );
				$p->nl();
			}
			$p->rel();
			$p->pn( '</subs>' );
		}

		public function getSerialData() {
			$d = parent::getSerialData();
			$d->data[] = $this->rows;
			$d->data[] = $this->group;
			$d->data[] = $this->entityView;
			$d->data[] = $this->percentRound;
			$d->data[] = $this->subs;
			$d->data[] = $this->graphView;
			$d->data[] = $this->startFrom;
			return $d;
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			list( $id, $title, $numberTitle, $counterId, $periodTypes, $divBy, $divByValueId, $divLimit, $round, $rows, $group, $entityView, $percentRound, $subs, $graphView ) = $d->data;

			if( !isset( $d->data[15] ) ) $startFrom = 1;
			else $startFrom = $d->data[15];

			$o = new self( $id, $title, $numberTitle, $counterId, $periodTypes, $entityView, $rows, $group, $divBy, $divByValueId, $divLimit, $round, $percentRound, $startFrom );
			$o->graphView = $graphView;
			$o->subs = $subs;
			return $o;
		}

		public function isEquivalent( self $cView ) {
			if( !parent::isEquivalent( $cView ) ) return false;
			if( $this->percentRound != $cView->percentRound ) return false;
			if( $this->rows != $cView->rows ) return false;
			if( $this->group != $cView->group ) return false;
			if( $this->startFrom != $cView->startFrom ) return false;
			if( !ardeAreEquiv( $this->entityView, $cView->entityView ) ) return false;
			if( !ardeEquivOrderedArrays( $this->subs, $cView->subs ) ) return false;
			return true;
		}

		function request( TwatchHistoryReader $historyR, $limit = null, $group = null ) {
			if( !is_null( $group ) ) $this->group = $group;
			foreach( $this->page->periods as $dtt => $dts ) {
				if( in_array( $dtt, $this->periodTypes )) {
					foreach( $dts as $period ) {
						$code = $period->getCode();
						$this->counter->request( $historyR, $dtt, $period->getCode(), is_null($group)?$this->group:$group, is_null($limit)?$this->rows:$limit );
					}
				}
			}
		}

		function getResult( TwatchHistoryReader $historyR ) {

			foreach( $this->page->periods as $periodType => $periods ) {
				if( in_array( $periodType, $this->periodTypes )) {
					foreach( $periods as $period ) {

						$r = $this->counter->getResult( $historyR, $periodType, $period->getCode(), $this->group );

						if( $this->divBy == self::DIV_HOUR_COUNT ) {

							foreach( $r->rows as &$row ) {
								if( $row->entityV->id < 1 || $row->entityV->id > 24 ) {
									$divisor = 1;
								} else {
									$divisor = $period->hourCount( $row->entityV->id - 1, $this->counterAvail->getPortions( $periodType ) );
								}


								if( $divisor < $this->divLimit || $divisor == 0 ) {
									$row->count = null;
								} else {
									$row->count /= $divisor;
								}

							}
						} elseif( $this->divBy == self::DIV_DAY_COUNT ) {
							foreach( $r->rows as &$row ) {
								if( $row->entityV->id < 1 || $row->entityV->id > 7 ) {
									$divisor = 1;
								} else {
									$divisor = $period->dayCount( $row->entityV->id - 1, $this->counterAvail->getPortions( $periodType ) );
								}
								if( $divisor < $this->divLimit || $divisor == 0 ) {
									$row->count = null;
								} else {
									$row->count /= $divisor;
								}
							}
						} elseif( $this->divBy != self::DIV_NONE ) {
							$divisor = $this->getDivisor( $period );
							foreach( $r->rows as &$row ) {
								if( $divisor < $this->divLimit || $divisor == 0 ) {
									$row->count = null;
								} else {
									$row->count /= $divisor;
								}
							}
						}
						if( $this->round >= 0 ) {
							foreach( $r->rows as &$row ) {
								if( !is_null( $row->count ))
									$row->count = round( $row->count * pow( 10, $this->round ) ) / pow( 10, $this->round );
							}
						}
						if( $this->percentRound >= 0 ) {
							foreach( $r->rows as &$row ) {
								if( !is_null( $row->percent ))
									$row->percent = round( $row->percent * pow( 10, $this->percentRound ) ) / pow( 10, $this->percentRound );
							}
						}
						$this->results[ $periodType.'-'.$period->getCode() ] = $r;
						if( $this->startFrom != 1 ) {
							$r1 = array_slice( $r->rows, $this->startFrom - 1 );
							$r2 = array_slice( $r->rows, 0, $this->startFrom - 1 );
							$this->results[ $periodType.'-'.$period->getCode() ]->rows = array_merge( $r1, $r2 );
						}

					}
				}
			}
		}

		protected $jsAdminClassName = 'ListCounterView';

		public function adminJsObject( $statsPageVarName ) {
			global $twatch, $ardeUser;

			if( $this instanceof SubCounterView ) $statsPage = '';
			else $statsPage = $statsPageVarName.', ';

			$periodTypes = new ArdeAppender( ', ' );
			foreach( $this->periodTypes as $pt ) {
				$periodTypes->append( $pt );
			}
			$divByValueId = $this->divByValueId == null ? 'null' : $this->divByValueId;
			$entityView = $this->entityView == null ? 'null' : $this->entityView->adminJsObject();

			$subs = new ArdeAppender( ', ' );
			
			if( $twatch->config->propertyExists( TwatchConfig::COUNTERS, $this->counterId ) ) {
				
				$counter = $twatch->config->get( TwatchConfig::COUNTERS, $this->counterId );
				
				if( $counter->isViewable( $ardeUser->user ) ) {
					$entityId = $counter->entityId;
		
					if( $this->group != null ) {
						$groupEntityId = $counter->groupEntityId;
						$entityV = TwatchEntityVGen::makeFinalized( $groupEntityId, $this->group );
						$group = $entityV->jsObject( new TwatchEntityView( true, false, false, EntityV::STRING_SELECT ), EntityV::JS_MODE_BLOCK );
					} else {
						$group = 'null';
					}
		
					if( $twatch->config->propertyExists( TwatchConfig::ENTITIES, $entityId ) ) {
						if( $twatch->config->get( TwatchConfig::ENTITIES, $entityId )->gene->getSet() !== false ) {
							$entityV = TwatchEntityVGen::makeFinalized( $entityId, $this->startFrom );
							$startFrom = $entityV->jsObject( new TwatchEntityView( true, false, false, EntityV::STRING_SELECT ), EntityV::JS_MODE_BLOCK );
						} else {
							$startFrom = 'null';
						}
					} else {
						$startFrom = 'null';
					}
					
					foreach( $this->subs as $sub ) {
						$subs->append( $sub->adminJsObject( 'null' ) );
					}
				} else {
					$group = 'null';
					$startFrom = 'null';
				}

			} else {
				
				$group = 'null';
				$startFrom = 'null';
			}

			

			$graphView = $this->graphView == null ? 'null' : 'true';

			$s = 'new '.$this->jsAdminClassName.'( '.$statsPage.$this->id.", '".ArdeJs::escape( $this->title )."', '".ArdeJs::escape( $this->numberTitle )."', ".$this->counterId.', [ '.$periodTypes->s.' ], ';
			$s .= $this->divBy.', '.$divByValueId.', '.$this->divLimit.', '.$this->round.', '.$group;
			$s .= ', '.$graphView.', '.$this->rows.', '.$this->percentRound.', '.$entityView.', [ '.$subs->s.' ], '.$startFrom.' )';


			return $s;

		}


		function jsObject() {
			global $twatch, $ardeUser;
			if( $this->graphView === null ) {
				$graphView = 'null';
			} else {
				reset( $this->results );
				$graphView = $this->graphView->getJs( current( $this->results ), 1, $this->entity->gene->getSet() );
			}
			$subs = new ArdeAppender( ', ' );
			foreach( $this->subs as $sub ) {
				if( !$sub->isViewable( $ardeUser->user ) ) continue;
				$subs->append( $sub->jsObject() );
			}

			$pTypes = implode( ', ', $this->periodTypes );
			return "new ListCView( ".$this->id.", '".ArdeJs::escape( $twatch->locale->text( $this->title ) )."', '".ArdeJs::escape( $twatch->locale->text( $this->numberTitle ) )."', ".$this->counterId.", ".$this->group.", ".$this->rows.", ".$graphView.", [ ".$subs->s." ], [ ".$pTypes." ] )";
		}

		function completeJsObject( ArdePrinter $p, $varName ) {
			foreach( $this->page->periods as $pType => $periods ) {
				if( in_array( $pType, $this->periodTypes ) ) {
				$p->pl( 'cRGroup = new CounterResGroup( '.$pType.' );' );
					$p->pl( $varName.'.addItem( cRGroup );' );
					foreach( $periods as $i => $period ) {
						$p->pl( 'cRGroup.addItem( '.$this->results[ $pType.'-'.$period->getCode() ]->jsObject( $this->graphView, $this->entityView ).' );' );
					}
				}
			}
		}

	}

	class SubCounterView extends ListCounterView {

		protected $jsAdminClassName = 'SubCounterView';

		public static function makeSerialObject( ArdeSerialData $d ) {
			list( $id, $title, $numberTitle, $counterId, $periodTypes, $divBy, $divByValueId, $divLimit, $round, $rows, $group, $entityView, $percentRound, $subs, $graphView ) = $d->data;
			if( !isset( $data[15] ) ) $startFrom = 1;
			else $startFrom = $data[15];
			$o = new self( $id, $title, $numberTitle, $counterId, $periodTypes, $entityView, $rows, $group, $divBy, $divByValueId, $divLimit, $round, $percentRound, $startFrom );
			$o->graphView = $graphView;
			$o->subs = $subs;
			return $o;
		}

	}

	class GraphView implements ArdeSerializable {

		var $width;
		var $height;
		var $barWidth;

		var $labelStringId;
		var $tooltipStringId;

		var $labels;

		function __construct( $width, $height, $barWidth, $labelStringId, $tooltipStringId, $labels ) {
			$this->width = $width;
			$this->height = $height;
			$this->barWidth = $barWidth;
			$this->labelStringId = $labelStringId;
			$this->tooltipStringId = $tooltipStringId;
			$this->labels = $labels;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->width, $this->height, $this->barWidth, $this->labelStringId, $this->tooltipStringId, $this->labels ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3], $d->data[4], $d->data[5] );
		}

		function getJs( TwatchCounterRes $counterRes, $evIdStart, $evIdEnd ) {
			global $twatch;
			$labels = new ArdeAppender( ', ' );
			$names = new ArdeAppender( ', ' );
			$i = 1;
			foreach( $counterRes->rows as $row ) {
				if( in_array( $row->entityV->id, $this->labels ) ) {
					$labels->append( $i.": '".ArdeJs::escape( $twatch->locale->text( $row->entityV->getString( $this->labelStringId ) ) )."'" );
				}
				$names->append( $i.": '".ArdeJs::escape( $twatch->locale->text( $row->entityV->getString( $this->tooltipStringId ) ) )."'" );
				++$i;
			}
			return 'new GraphView( '.$this->width.', '.$this->height.', '.$this->barWidth.", ".$evIdStart.", ".$evIdEnd.", { ".$labels->s." }, { ".$names->s." } )";
		}
	}
?>