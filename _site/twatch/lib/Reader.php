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

	require_once dirname(__FILE__).'/../db/DbReader.php';
	require_once dirname(__FILE__).'/EntityV.php';
	require_once dirname(__FILE__).'/../data/DataStatsPage.php';

	class TwatchGraphData {
		public $period;
		public $value;
		public $span;

		public $usingAvg;

		public $note;

		public function TwatchGraphData( TwatchPeriodDay $period, $value, $span, $usingAvg, $note ) {
			$this->period = $period;
			$this->value = $value;
			$this->span = $span;
			$this->usingAvg = $usingAvg;
			$this->note = $note;
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			if( $this->value === null ) $value = '';
			else $value = ' value="'.$this->value.'"';
			if( $this->span == 1 ) $span = '';
			else $span = ' span="'.$this->span.'"';
			if( $this->note === null ) $note = '';
			else $note = ' note="'.ardeXmlEntities( $this->note ).'"';
			$p->pn( '<'.$tagName.$value.$span.$note.$extraAttrib.' >'.ardeXmlEntities( $this->period->getString( TwatchPeriodDay::STRING_COMPACT ) ).'</'.$tagName.'>' );
		}

	}

	class TwatchGraphResults {
		var $data;

		function __construct( $data ) {
			$this->data = $data;
		}

		function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.$extraAttrib.'>', 1 );
			foreach( $this->data as $data ) {
				$data->printXml( $p, 'gdata' );
				$p->nl();
			}
			$p->rel();
			$p->pn( '</'.$tagName.'>' );
		}


	}

	class TwatchGraphReader {

		const MIN_DAY_WEIGHT = .25;
		const MIN_MONTH_LENGTH = 1;

		var $sub;

		function TwatchGraphReader( $sub ) {
			$this->sub = $sub;
		}

		function get( $counterId, $groupId = 0, $entityVId = 0 ) {
			global $twatch;

			$data = array();

			$dbrGraph = new TwatchDbrGraph( $twatch->db, $this->sub );


			$avail = $twatch->state->get( TwatchState::COUNTERS_AVAIL, $counterId );
			$counter = $twatch->config->get( TwatchConfig::COUNTERS, $counterId );
			$dist = new TwatchHourlyDistribution();
			$dist->fillFlat();

			$dailyStartTs = $avail->getStart( TwatchPeriod::DAY );
			$monthlyStartTs = $avail->getStart( TwatchPeriod::MONTH );

			if( $dailyStartTs !== null ) {
				$time = new TwatchTime( $dailyStartTs );
				if( $time->getDayStart() == $time->ts ) $firstDailyStartTs = $time->ts;
				$firstDailyStartTs = $time->dayOffset(1)->getDayStart();
				$dailyDbRes = $dbrGraph->get( TwatchPeriod::DAY, $counterId, $groupId, $entityVId );
			}

			if( $monthlyStartTs !== null ) {
				$monthlyDbRes = $dbrGraph->get( TwatchPeriod::MONTH, $counterId, $groupId, $entityVId );
			}

			if( $dailyStartTs === null ) {
				if( $monthlyStartTs === null ) return new TwatchGraphResults( $data );
				$startTs = $monthlyStartTs;
			} else {
				if( $monthlyStartTs === null ) $startTs = $dailyStartTs;
				else $startTs = min( $monthlyStartTs, $dailyStartTs );
			}

			$endDayCode = TwatchTime::getTime( $startTs )->dayOffset(-1)->getDayCode();
			$lastAvgMonthCode = '';
			$lastIndexThatUsedAvg = null;
			$lastIndexThatHoldsAvg = null;
			$time = clone $twatch->now;
			$cDayCode = $time->getDayCode();
			$i = 0;

			while( $cDayCode != $endDayCode ) {
				$dayPeriod = new TwatchPeriodDay( $time->ts );
				$value = null;
				$note = null;
				if( $dailyStartTs !== null ) {
					$weightedDays = $dayPeriod->weightedDays( $avail->getPortions( TwatchPeriod::DAY ), $dist );
					if( $weightedDays >= self::MIN_DAY_WEIGHT ) {
						if( isset( $dailyDbRes[ $cDayCode ] ) ) $value = round( $dailyDbRes[ $cDayCode ] / $weightedDays );
						else $value = 0;
						if( $weightedDays < 1 ) $note = $twatch->locale->text( 'estimated' );
					}
				}
				if( $value === null && ( $dailyStartTs === null || $time->ts < $firstDailyStartTs ) && $monthlyStartTs !== null ) {
					$cMonthCode = $time->getMonthCode();
					if( $lastAvgMonthCode == $cMonthCode && $lastIndexThatUsedAvg === $i - 1 ) {
						$data[ $lastIndexThatHoldsAvg ]->span++;
						$lastIndexThatUsedAvg = $i;
					} else {
						$monthPeriod = new TwatchPeriodMonth( $time->ts );
						$days = $monthPeriod->length( $avail->getPortions( TwatchPeriod::MONTH ) ) / 86400;
						if( $days > self::MIN_MONTH_LENGTH ) {
							if( isset( $monthlyDbRes[ $cMonthCode ] ) ) $value = round( $monthlyDbRes[ $cMonthCode ] / $days );
							else $value = 0;
							$note = $twatch->locale->text( 'month average' );
							$lastIndexThatUsedAvg = $i;
							$lastIndexThatHoldsAvg = $i;
							$lastAvgMonthCode = $cMonthCode;
						}
					}
				}

				$data[ $i ] = new TwatchGraphData( $dayPeriod, $value, 1, $lastIndexThatUsedAvg === $i, $note );
				++$i;
				$time = $time->dayOffset(-1);
				$cDayCode = $time->getDayCode();
			}


			return new TwatchGraphResults( $data );
		}

	}


	class TwatchHistoryRequestInfo {
		public $counterId;
		public $periodType;
		public $periodCode;
		public $group;
		public $entityId;
		public $limit;
		public $set;
		public $total;
		public $entityVId;

		function TwatchHistoryRequestInfo( $counterId, $periodType, $periodCode, $group, $entityId, $limit, $set, $total, $entityVId ) {
			$this->counterId = $counterId;
			$this->periodType = $periodType;
			$this->periodCode = $periodCode;
			$this->group = $group;
			$this->entityId = $entityId;
			$this->limit = $limit;
			$this->set = $set;
			$this->total = $total;
			$this->entityVId = $entityVId;
		}
	}

	class TwatchCounterRow {
		var $entityV;
		var $count;
		var $percent;



		function printXml( ArdePrinter $p, $tagName, TwatchEntityView $entityView, $extraAttrib = '' ) {
			global $twatch;
			return $this->entityV->printXml( $p, 'row', $entityView, ' count="'.( is_null( $this->count ) ? 'null' : $twatch->locale->number( $this->count ) ).'" percent="'.$twatch->locale->number( $this->percent ).'" ' );
		}

		function jsObject( GraphView $graphView = null, TwatchEntityView $entityView ) {
			global $twatch;
			if( $graphView !== null ) {
				return $this->count === null ? 'null' : $this->count;
			} else {
				return 'new CounterRow( '.$this->entityV->jsObject( $entityView, EntityV::JS_MODE_INLINE ).', '.( is_null( $this->count ) ? 'null' : "'".$twatch->locale->number( $this->count )."'" ).', '."'".$twatch->locale->number( $this->percent )."' )";
			}
		}
	}

	class TwatchCounterRes {

		var $more = false;

		var $total = 0;

		var $count = 0;

		var $rows = array();


		function printXml( ArdePrinter $p, $tagName, TwatchEntityView $entityView, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' more="'.( $this->more ? 'true' : 'false' ).'" total="'.$this->total.'"'.$extraAttrib.'>' );
			$p->pl( '	<rows>', 1 );
			foreach( $this->rows as $r ) {
				$r->printXml( $p, 'row', $entityView );
				$p->nl();
			}
			$p->rel();
			$p->pl( '	</rows>' );
			$p->pl( '</'.$tagName.'>' );
		}

		function jsObject( GraphView $graphView = null, TwatchEntityView $entityView ) {
			if( $graphView !== null ) $s='new CounterResGraph( ';
			else $s='new CounterRes( ';
			$s .= ( $this->more ? 'true' : 'false' ).', [ ';
			$i = 0;
			foreach( $this->rows as $k => $v ) {
				$s .= ($i?', ':'').$this->rows[$k]->jsObject( $graphView, $entityView );
				$i++;
			}
			return $s.' ] )';
		}
	}

	class TwatchHistoryReader {
		private $dbrHistory;
		private $sub;
		private $requests = array();
		private $results = array();


		function TwatchHistoryReader( $sub ) {
			global $twatch;

			$this->sub = $sub;
			$this->dbrHistory = new TwatchDbrHistory( $twatch->db, $sub );
		}


		function get( $counterId, $periodType, $periodCode, $group = 0, $entityId = 0, $limit = 0, $total = true, $entityVId = null ) {
			global $twatch;

			$k = $this->dbrHistory->makeKey( $counterId, $periodType, $periodCode, $group, $entityVId );

			if( $entityId ) {
				$o = &$twatch->config->get( TwatchConfig::ENTITIES, $entityId );
				$set = $o->gene->getSet();

			} else {
				$set = false;
			}
			$this->requests[$k] = new TwatchHistoryRequestInfo( $counterId, $periodType, $periodCode, $group, $entityId, $limit, $set, $total, $entityVId );
			$this->results[$k] = new TwatchCounterRes();
			$this->dbrHistory->get( $counterId, $periodType, $periodCode, $group, (bool)( !$entityId || $entityVId !== null ), ($limit ? $limit + 1 : 0 ), $total, $entityVId );
		}

		function rollGet() {
			$res = $this->dbrHistory->rollGet();

			$entVGen = new TwatchEntityVGen();

			foreach( $this->requests as $key => $request ) {
				$res = $this->dbrHistory->getResultByKey( $key );

				if( !$request->entityId || $request->entityVId !== null ) {
					$this->results[ $key ]->count = $res;
				} else {

					if( $request->total ) {
						$this->results[ $key ]->total = $this->dbrHistory->getTotalResultByKey( $key );
					}

					$count = count( $res );
					if( $request->limit &&  $count > $request->limit ) {
						$this->results[ $key ]->more = true;
						unset( $res[ $count - 1 ] );
					}

					if( $request->set ) {
						$tempRes = array();
					}

					foreach( $res as $dbRow ) {
						$row = new TwatchCounterRow();
						$row->entityV = &$entVGen->make( $request->entityId, $dbRow->entityVId );
						$row->count = $dbRow->count;
						if( $request->total ) {
							if( $this->results[ $key ]->total ) {
								$row->percent = ( $dbRow->count / $this->results[ $key ]->total ) * 100;
							} else {
								$row->percent = 0;
							}
						}
						if( $request->set ) {
							$tempRes[ $dbRow->entityVId ] = $row;
						} else {
							$this->results[ $key ]->rows[] = $row;
						}
					}


					if( $request->set ) {
						for( $i = 1; $i <= $request->set; ++$i ) {
							if( !isset( $tempRes[$i] ) ) {
								$tempRes[$i] = new TwatchCounterRow();
								$tempRes[$i]->entityV = &$entVGen->make( $request->entityId, $i );
								$tempRes[$i]->count = 0;
								if( $request->total ) {
									$tempRes[$i]->percent = 0;
								}
							}
						}
						sort( $tempRes );
						foreach( $tempRes as $row ) {
							$this->results[ $key ]->rows[] = $row;
						}
					}

				}
			}

			$entVGen->finalizeEntityVs();

			return true;
		}



		function getResult( $counterId, $periodType, $periodCode, $group = 0, $entityVId = null ) {
			return $this->results[ $this->dbrHistory->makeKey( $counterId, $periodType, $periodCode, $group, $entityVId ) ];
		}
	}


	class TwatchPathResSet {
		var $left;
		var $right;
		var $total;


		function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.$extraAttrib.'>', 1 );
			$this->left->printXml( $p, 'left' );
			$p->nl();
			$this->right->printXml( $p, 'right' );
			$p->relnl();
			$p->pl( '</'.$tagName.'>' );
		}
	}

	class TwatchPathRow {
		var $entityV;
		var $percent;

		function __sv( $l, $level, $name ) {
			__o( $l, $name.': row' );
			__pv( $l, 'entity', $this->entityV, $level );
			__p( $l, 'percent', $this->percent );
			__c( $l );
		}
	}

	class TwatchPathRes {

		var $data = array();
		var $total = 0;

		function fillWithDbResult( TwatchDbrResultPathCol $dbRes, $entVGen, $entityId ) {

			foreach( $dbRes->data as $entityVId => $count ) {
				$data = new TwatchPathRow();
				if( $entityVId < 0 ) {
					$data->entityV = &$entVGen->make( TwatchEntity::PATH, -$entityVId );
				} elseif( $entityVId == 0 ) {
					if( $entityId != TwatchEntity::PAGE ) {
						$data->entityV = &$entVGen->make( TwatchEntity::PATH, TwatchPathAnalyzer::PATH_UNKNOWN_DATA );
					} else {
						$data->entityV = &$entVGen->make( TwatchEntity::PATH, TwatchPathAnalyzer::PATH_END );
					}
				} else {
					$data->entityV = &$entVGen->make( $entityId, $entityVId );
				}
				$data->percent = ( $count / $dbRes->sum ) * 100;
				$this->data[] = $data;
			}
			$this->total = $dbRes->sum;

		}




		function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' total="'.$this->total.'"'.$extraAttrib.'>', 1 );
			foreach( $this->data as $k => $v ) {
				$this->data[$k]->entityV->printXml( $p, 'row', new TwatchEntityView( true, false, false, EntityV::STRING_PATH_ANALYSIS ), ' percent="'.$this->data[$k]->percent.'" ' );
				$p->nl();
			}
			$p->rel();
			$p->pl( '</'.$tagName.'>' );
		}

	}

	class TwatchPathReader {

		private $sub;

		function TwatchPathReader( $sub ) {

			$this->sub = $sub;

		}

		function readColumn( $dataColumnsOrder, $columnIndex ) {
			global $twatch;

			$pathAnalyzer = $twatch->config->get( TwatchConfig::PATH_ANALYZER );

			$dataColumnsCount = count( $pathAnalyzer->dataColumns );


			if( count( $dataColumnsOrder ) != $dataColumnsCount ) return __e( 'read path column', "data_order count doesn't match" );
			if( $columnIndex >= $pathAnalyzer->depth + $dataColumnsCount ) return __e( 'read path column', "invalid column ".$id );

			$entVGen = new TwatchEntityVGen();

			$dbrPath = new TwatchDbrPath( $twatch->db, $this->sub );

			if( $columnIndex < $dataColumnsCount ) {
				$originalIndex = $dataColumnsOrder[ $columnIndex ];
			} else {
				$originalIndex = $columnIndex;
			}
			$res = $dbrPath->getColumn( $originalIndex );
			$out = new twatchPathRes();

			if( $originalIndex < $dataColumnsCount ) {
				$entityId = $pathAnalyzer->dataColumns[ $originalIndex ];
			} else {
				$entityId = TwatchEntity::PAGE;
			}
			$out->fillWithDbResult( $res, $entVGen, $entityId );

			$entVGen->finalizeEntityVs();

			return $out;
		}

		function read( $dataColumnsOrder, $selection ) {
			global $twatch;
			$pathAnalyzer = $twatch->config->get( TwatchConfig::PATH_ANALYZER );

			$dataColumnsCount = count( $pathAnalyzer->dataColumns );
			$columnsCount = $dataColumnsCount + $pathAnalyzer->depth;

			if( count( $dataColumnsOrder ) != $dataColumnsCount ) throw new TwatchException( "data columns order count doesn't match" );
			if( count( $selection ) != $columnsCount ) throw new TwatchException( "selection count doesn't match" );

			$found = false;
			foreach($selection as $columnStatus ) {
				if( $columnStatus != 0 ) {
					$found = true;
					break;
				}
			}
			if( !$found ) throw new TwatchException( 'there should be at least one selected column' );

			$fixedColumnsCount = 0;
			for( $i = $dataColumnsCount; $i < $columnsCount; ++$i ) {
				if( $selection[$i] == 0 ) break;
				++$fixedColumnsCount;
			}


			$movableParts = array();
			$part = array();
			$inPart = false;
			for( $i = $dataColumnsCount + $fixedColumnsCount; $i < $columnsCount; ++$i ) {
				if( $inPart ) {
					if( $selection[$i] == 0 ) {
						$inPart = false;
						$movableParts[] = $part;
					} else {
						$part[] = $selection[ $i ];
					}
				} else {
					if( $selection[$i] != 0 ) {
						$inPart = true;
						$part = array( $selection[$i] );
					}
				}
			}
			if( $inPart ) $movableParts[] = $part;

			foreach( $movableParts as $part ) {
			}

			$availableSpace = $pathAnalyzer->depth - $fixedColumnsCount;
			$movablePartsVariations = self::getVariations( $movableParts, $pathAnalyzer->depth - $fixedColumnsCount );


			$variations = array();
			foreach( $movablePartsVariations as $movablePartsVariation ) {
				$variations[] = array_merge( array_slice( $selection, 0, $dataColumnsCount + $fixedColumnsCount ), $movablePartsVariation );
			}

			$criterias = array();

			$rightEntityId = $leftEntityId = TwatchEntity::PAGE;
			foreach( $variations as $variation ) {

				$criteria = new TwatchDbrPathCriteria();

				for( $leftmost = 0; $leftmost < $columnsCount; ++$leftmost ) if( $selection[ $leftmost ] != 0 ) break;
				if( $leftmost == 0 ) {
					$criteria->leftColumnIndex = -TwatchPathAnalyzer::PATH_UNKNOWN_PAST;
				} elseif( $leftmost <= $dataColumnsCount ) {
					$criteria->leftColumnIndex = $dataColumnsOrder[ $leftmost - 1 ];
					$leftEntityId = $pathAnalyzer->dataColumns[ $criteria->leftColumnIndex ];
				} else {
					for( $varLeftmost = 0; $varLeftmost < $columnsCount; ++$varLeftmost ) if( $variation[ $varLeftmost ] != 0 ) break;
					$criteria->leftColumnIndex = $varLeftmost - 1;
					if( $criteria->leftColumnIndex < $dataColumnsCount ) {
						$criteria->leftColumnIndex = -TwatchPathAnalyzer::PATH_START;
					}
				}

				for( $varRightmost = $columnsCount - 1; $varRightmost >= 0; --$varRightmost ) if( $variation[ $varRightmost ] != 0 ) break;
				if( $varRightmost < $dataColumnsCount - 1 ) {
					$rightEntityId = $pathAnalyzer->dataColumns[ $dataColumnsOrder[ $varRightmost + 1 ] ];
				} else {
					$criteria->rightColumnIndex = $varRightmost + 1;
					if( $criteria->rightColumnIndex >= $columnsCount ) $criteria->rightColumnIndex = -TwatchPathAnalyzer::PATH_UNKNOWN_FATE;
				}

				$criteria->criteria = array();
				for( $i = 0; $i < $dataColumnsCount; ++$i ) $criteria->criteria[ $dataColumnsOrder[$i] ] = $variation[ $i ];
				for( $i = $dataColumnsCount; $i < $columnsCount; ++$i ) $criteria->criteria[ $i ] = $variation[ $i ];

				$criterias[] = $criteria;
			}


			$dbrPath = new TwatchDbrPath( $twatch->db, $this->sub );

			$res = $dbrPath->get( $criterias );



			$entVGen = new TwatchEntityVGen();

			$out = new TwatchPathResSet();
			$out->total = $res->left->sum;
			$out->left = new TwatchPathRes();
			$out->right = new TwatchPathRes();
			$out->left->fillWithDbResult( $res->left, $entVGen, $leftEntityId );
			$out->right->fillWithDbResult( $res->right, $entVGen, $rightEntityId );

			$entVGen->finalizeEntityVs();

			return $out;
		}

		private static function getVariations( $movableParts, $availableSpace ) {
			if( $availableSpace == 0 ) return array( array() );
			if( !count( $movableParts ) ) return array( array_fill( 0, $availableSpace, 0 ) );

			$firstPart = $movableParts[0];
			$remainingParts = array_slice( $movableParts, 1 );
			$firstPartLength = count( $firstPart );
			$remainingPartsLength = 0;
			foreach( $remainingParts as $part ) {
				$remainingPartsLength += count( $part );
			}

			$variations = array();

			for( $i = 0; $i < $availableSpace - $remainingPartsLength - $firstPartLength + 1; ++$i ) {
				$leftSide = array();
				for( $j = 0; $j < $i; $j++ ) $leftSide[ $j ] = 0;
				$leftSide = array_merge( $leftSide, $firstPart );
				$rightSides = self::getVariations( $remainingParts, $availableSpace - $i - $firstPartLength );
				foreach( $rightSides as $rightSide ) {
					$variations[] = array_merge( $leftSide, $rightSide );
				}
			}
			return $variations;
		}
	}

	class TwatchSession {
		var $id;
		var $time;
		var $ip;
		var $pCookie;
		var $sCookie;
		var $visitorTypeId;
		var $requests = array();

		var $priItems = array();
		var $secItems = array();
		
		public $more;
		public $offset;
		
		public function jsObject( $latestPage ) {			
			global $ardeUser;
			
			$pItems = new ArdeAppender( ', ' );
			$item = reset( $latestPage->priItems );
			foreach( $this->priItems as $itemValue ) {
				
				if( $itemValue === false ) {
					$pItems->append( 'null' );
				} else {
					$pItems->append( $itemValue->jsObject( $item->entityView, EntityV::JS_MODE_INLINE_BLOCK ) );
				}
				$item = next( $latestPage->priItems );
			}
			
			$sItems = new ArdeAppender( ', ' );
			$item = reset( $latestPage->secItems );
			foreach( $this->secItems as $itemValue ) {
				
				if( $itemValue === false ) {
					$sItems->append( 'null' );
				} else {
					$sItems->append( $itemValue->jsObject( $item->entityView, EntityV::JS_MODE_INLINE_BLOCK ) );
				}
				$item = next( $latestPage->secItems );
			}
			
			$s = 'new Session( ';
			$ev = new TwatchEntityView( true, false, false );
			if( $this->sCookie === null || $this->sCookie->id == 0 ) $sCookie = 'null';
			else $sCookie = $this->sCookie->jsObject( $ev, EntityV::JS_MODE_INLINE_BLOCK );
			if( $this->pCookie === null || $this->pCookie->id == 0 ) $pCookie = 'null';
			else $pCookie = $this->pCookie->jsObject( $ev, EntityV::JS_MODE_INLINE_BLOCK );

			$s .= $this->id.', '.$this->time->jsObject( $ev, EntityV::JS_MODE_INLINE_BLOCK ).', '.($this->ip===null?'null':$this->ip->jsObject( $ev, EntityV::JS_MODE_INLINE_BLOCK ));
			$s .= ', '.$sCookie.', '.$pCookie;
			$s .= ', '.$this->visitorTypeId;
			$s .= ', [ '.$pItems->s.' ]';
			$s .= ', [ '.$sItems->s.' ]';
			$sr = new ArdeAppender( ',' );
			foreach( $this->requests as $k => $v ) {
				$sr->append( $this->requests[$k]->jsObject() );
			}
			$s .= ', new Array('.$sr->s.'), '.$this->offset.', '.$this->more.' )';
			return $s;
		}

		
	}

	class TwatchSessionRequest {
		var $id;
		var $time;
		var $page;
		var $data = array();

		function jsObject() {
			$ev = new TwatchEntityView( true, false, false );
			$s = 'new Request( '.$this->id.', '.$this->time->jsObject( $ev, EntityV::JS_MODE_INLINE ).', '.($this->page===null?'null':$this->page->jsObject( new TwatchEntityView( true, false, true ), EntityV::JS_MODE_INLINE_BLOCK )).', ';
			$sd = new ArdeAppender(',');
			foreach( $this->data as $k => $v ) {
				$sd->append( $this->data[$k]->jsObject( new TwatchEntityView( true, true, false ), EntityV::JS_MODE_INLINE_BLOCK ) );
			}
			$s.=' new Array('.$sd->s.') )';
			return $s;
		}

	}

	class TwatchSessionReader {
		private $dbrSession;
		private $sub;
		private $dataEntities = array();

		public $sessions = array();
		public $entitiesUsed = array();
		public $more;
		public $online;
		
		private $entVGen;

		function TwatchSessionReader( $sub ) {
			global $twatch;

			$this->dbrSession = new TwatchDbrSession( $twatch->db, $sub );
			$this->sub = $sub;

			$this->entVGen = new TwatchEntityVGen();
			
			foreach( $twatch->config->getList( TwatchConfig::RDATA_WRITERS ) as $dataWriter ) {
				$this->dataEntities[ $dataWriter->entityId ] = true;
			}
		}

		function getLatestSessions( $start, $limit, $entityFilters = array(), $visitorTypeFilter = array(), $forceExVt = null ) {
			global $twatch;
			$pageId = false;
			$pCookie = false;
			foreach( $entityFilters as $entityId => $entityVId ) {
				if( $entityId == TwatchEntity::PAGE ) {
					unset( $entityFilters[ $entityId ] );
					$pageId = $entityVId;
				} elseif( $entityId == TwatchEntity::PCOOKIE ) {
					unset( $entityFilters[ $entityId ] );
					$pCookie = $entityVId;
				}
			}

			if( ardeEquivSets( $visitorTypeFilter, array_keys( $twatch->config->getList( TwatchConfig::VISITOR_TYPES ) ) ) ) {
				$visitorTypeFilter = array();
			}

			$res = $this->dbrSession->getLatestSessions( $start, $limit, $entityFilters, $visitorTypeFilter, $pageId, $pCookie, $forceExVt, 300, $twatch->now->ts );

			$this->entitiesUsed = array( TwatchEntity::IP => true, TwatchEntity::SCOOKIE => true, TwatchEntity::PCOOKIE => true, TwatchEntity::PAGE => true );

			foreach( $this->dbrSession->sessions as $dbSession ) {
				$session = $this->sessionFromDbSession( $dbSession );
				$this->sessions[ $session->id ] = $session;
			}
			$this->more = $this->dbrSession->more;
			$this->online = $this->dbrSession->online;
		}
		
		public function sessionFromDbSession( TwatchDbrResultSession $dbSession ) {
			global $ardeUser;
			$session = new TwatchSession();
			$session->id = $dbSession->id;
			$session->visitorTypeId = $dbSession->visitorTypeId;
			if( $ardeUser->user->data->get( TwatchUserData::VIEW_ENTITY, TwatchEntity::IP ) != TwatchEntity::VIS_HIDDEN ) {
				$session->ip = &$this->entVGen->make( TwatchEntity::IP, $dbSession->ip );
			} else {
				$session->ip = null;
			}
			
			if( $ardeUser->user->data->get( TwatchUserData::VIEW_ENTITY, TwatchEntity::PCOOKIE ) != TwatchEntity::VIS_HIDDEN ) {
				$session->pCookie = &$this->entVGen->make( TwatchEntity::PCOOKIE, $dbSession->pCookie );
			} else {
				$session->pCookie = null;
			}
			if( $ardeUser->user->data->get( TwatchUserData::VIEW_ENTITY, TwatchEntity::SCOOKIE ) != TwatchEntity::VIS_HIDDEN ) {
				$session->sCookie = &$this->entVGen->make( TwatchEntity::SCOOKIE, $dbSession->sCookie );
			} else {
				$session->sCookie = null;
			}
			$session->time = &$this->entVGen->make( TwatchEntity::TIME, $dbSession->firstTs );
			return $session;
		}
		
		function getSession( $sessionId, $forceExVt = null ) {
			$res = $this->dbrSession->getSession( $sessionId, $forceExVt );
			
			if( isset( $this->dbrSession->sessions[ $sessionId ] ) ) {
				$this->sessions[ $sessionId ] = $this->sessionFromDbSession( $this->dbrSession->sessions[ $sessionId ] );
			}
		}
		
		function finalizeSession( $sessionId = null, TwatchLatestPage $latestPage, $requestsOffset, $requestsCount ) {
			global $ardeUser;
			if( $sessionId === null ) {
				reset( $this->sessions );
				$sessionId = key( $this->sessions );
			}
			$session = &$this->sessions[ $sessionId ];
			$session->offset = $requestsOffset;
			$eids = array();
			foreach( $latestPage->priItems as $item ) {
				if( !$item->isViewable() ) continue;
				$eids[ $item->entityId ] = ( $item->lookup == TwatchLatestItem::LOOKUP_FIRST ? true : false );
			}
			foreach( $latestPage->secItems as $item ) {
				if( !$item->isViewable() ) continue;
				$eids[ $item->entityId ] = ( $item->lookup == TwatchLatestItem::LOOKUP_FIRST ? true : false );
			}
			$itemData = $this->dbrSession->lookupData( $sessionId, $eids );
			
			foreach( $latestPage->priItems as $id => $item ) {
				if( !$item->isViewable() ) continue;
				if( !isset( $itemData[ $item->entityId ] ) ) {
					$session->priItems[] = false;
				} else {
					$session->priItems[] = &$this->entVGen->make( $item->entityId, $itemData[ $item->entityId ] );
				}
			}
			foreach( $latestPage->secItems as $id => $item ) {
				if( !$item->isViewable() ) continue;
				if( !isset( $itemData[ $item->entityId ] ) ) {
					$session->secItems[] = false;
				} else {
					$session->secItems[] = &$this->entVGen->make( $item->entityId, $itemData[ $item->entityId ] );
				}
			}
			
			$this->dbrSession->getRequests( $sessionId, $requestsOffset, $requestsCount );
			
			$dbSession = $this->dbrSession->sessions[ $sessionId ];
			
			$session->more = $dbSession->more;
			
			foreach( $dbSession->requests as $dbRequest ) {
				$request = new TwatchSessionRequest();
				$request->id = $dbRequest->id;
				if( $ardeUser->user->data->get( TwatchUserData::VIEW_ENTITY, TwatchEntity::PAGE ) != TwatchEntity::VIS_HIDDEN ) {
					$request->page = &$this->entVGen->make( TwatchEntity::PAGE, $dbRequest->pageId );
				} else {
					$request->page = null;
				}
				$request->time = &$this->entVGen->make( TwatchEntity::TIME, $dbRequest->timestamp );

				foreach( $dbRequest->data as $entityId => $entityVId ) {
					if( $ardeUser->user->data->get( TwatchUserData::VIEW_ENTITY, $entityId ) == TwatchEntity::VIS_HIDDEN ) continue;
					$request->data[ $entityId ] = &$this->entVGen->make( $entityId, $entityVId );
					$this->entitiesUsed[ $entityId ] = true;
				}
				$session->requests[] = $request;
			}
			
			$this->entVGen->finalizeEntityVs();
		}

		function onlineVisitorsCount( $tolerance, $visitorTypeId ) {
			global $twatch;
			return $this->dbrSession->onlineVisitorsCount( $visitorTypeId, $twatch->now->ts - $tolerance );
		}

		function isValidFilterEntity( $entityId ) {
			if( $entityId == TwatchEntity::PAGE ) return true;
			if( $entityId == TwatchEntity::PCOOKIE ) return true;
			return isset( $dataEntities[ $entityId ] );
		}

	}
?>