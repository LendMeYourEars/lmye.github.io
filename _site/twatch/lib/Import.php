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

	require_once $twatch->path( 'lib/EntityPassiveGene.php' );

	class TwatchImportFileInfo {
		public $name = '!NS!';
		public $counters;
		public $starts;
		public $stops;
		public $periodTypes;
		public $availability;
		public $delete;

		public $rowCount = 0;


		public function __construct( $starts, $stops, $counters, $periodTypes, $availability, $delete ) {
			$this->starts = $starts;
			$this->stops = $stops;
			$this->counters = $counters;
			$this->periodTypes = $periodTypes;
			$this->availability = $availability;

			$this->delete = $delete;
			$this->determineStartStopTs();
			$this->determineTotalPeriodsCount();
			$this->finalizeAvailabilities();
		}

		public function determineStartStopTs() {
			global $twatch;
			foreach( $this->periodTypes as $id => &$periodType ) {

				if( isset( $this->starts[ $id ] ) ) {
					$periodType->determinedStartTs = $this->starts[ $id ]->ts;
				} elseif( isset( $this->starts[ -1 ] ) ) {
					$periodType->determinedStartTs = $this->starts[ -1 ]->ts;
				} else {
					$min = TwatchPeriod::fromCode( $id, $periodType->minCode );
					$periodType->determinedStartTs = $min->getStartTs();
				}

				if( isset( $this->stops[ $id ] ) ) {
					$periodType->determinedStopTs = $this->stops[ $id ]->ts;
				} elseif( isset( $this->stops[ -1 ] ) ) {
					$periodType->determinedStopTs = $this->stops[ -1 ]->ts;
				} else {
					$max = TwatchPeriod::fromCode( $id, $periodType->maxCode );
					$periodType->determinedStopTs = $max->getEndTs();
				}
				if( $periodType->determinedStopTs > $twatch->now->ts ) $periodType->determinedStopTs = $twatch->now->ts;

			}
		}

		public function finalizeAvailabilities() {
			foreach( $this->availability->timestamps as $periodType => $tss ) {
				foreach( $this->counters as $counter ) {
					if( !isset( $counter->availability->timestamps[ $periodType ] ) )  $counter->availability->timestamps[ $periodType ] = $tss;
				}
			}
		}


		public static function getAvailability( DOMElement $element, $delete = false ) {

			$avail = new TwatchCounterAvailability();

			$periodEs = new ArdeXmlElemIter( $element, 'period' );
			foreach( $periodEs as $periodE ) {
				$periodType = ArdeXml::strAttribute( $periodE, 'type' );
				if( isset( TwatchPeriod::$importStringTypes[ $periodType ] ) ) {
					$periodType = TwatchPeriod::$importStringTypes[ $periodType ];
				} else {
					throw new TwatchException( 'bad start period type "'.$periodType.'"' );
				}
				$avail->timestamps[ $periodType ] = self::getTss( $periodE );

				if( !$delete && count( $avail->timestamps[ $periodType ] ) < 2 ) {
					throw new TwatchException( 'there should be at least one <start> and one <stop> for a period type' );
				}
			}
			return $avail;
		}

		public static function getTss( DOMElement $element ) {
			$o = array();
			$es = new ArdeXmlMultiElemIter( $element, array( 'start' => true, 'stop' => true ) );
			foreach( $es as $e ) {

				if( $e->tagName == 'start' ) {
					if( isset( $prevWasStart ) && $prevWasStart ) {
						throw new TwatchException( 'unexpected <start>' );
					}
					$prevWasStart = true;
				} else {
					if( !isset( $prevWasStart ) || !$prevWasStart ) {
						throw new TwatchException( 'unexpected <stop>' );
					}
					$prevWasStart = false;
				}

				$o[] = TwatchImportTime::fromXml( $e )->ts;
			}
			return $o;
		}



		public function determineTotalPeriodsCount() {
			foreach( $this->counters as $counter ) {
				$this->rowCount += $counter->rowCount;
			}
		}

		public static function fromXml( DOMElement $element ) {
			global $twatch;

			$starts = array();
			foreach( new ArdeXmlElemIter( $element, 'start' ) as $startE ) {
				$periodType = ArdeXml::strAttribute( $startE, 'period_type', -1 );
				if( $periodType != -1 ) {
					if( isset( TwatchPeriod::$importStringTypes[ $periodType ] ) ) {
						$periodType = TwatchPeriod::$importStringTypes[ $periodType ];
					} else {
						throw new TwatchException( 'bad start period type "'.$periodType.'"' );
					}
				}

				$starts[ $periodType ] = TwatchImportTime::fromXml( $startE );
				$starts[ $periodType ]->periodType = $periodType;
			}

			$stops = array();
			foreach( new ArdeXmlElemIter( $element, 'stop' ) as $stopE ) {
				$periodType = ArdeXml::strAttribute( $stopE, 'period_type', -1 );
				if( $periodType != -1 ) {
					if( isset( TwatchPeriod::$importStringTypes[ $periodType ] ) ) {
						$periodType = TwatchPeriod::$importStringTypes[ $periodType ];
					} else {
						throw new TwatchException( 'bad start period type "'.$periodType.'"' );
					}
				}

				$stops[ $periodType ] = TwatchImportTime::fromXml( $stopE );
				$stops[ $periodType ]->periodType = $periodType;
			}

			$twCounters = $twatch->config->getList( TwatchConfig::COUNTERS );
			$counters = array();
			$periodTypes = array();
			$counterEs = new ArdeXmlElemIter( $element, 'counter' );

			$availability = self::getAvailability( $element, false );

			foreach( $counterEs as $counterE ) {
				$counter = TwatchImportCounterInfo::fromXml( $counterE );

				foreach( $counter->periodTypes as $id => $periodType ) {
					if( !isset( $periodTypes[ $id ] ) ) $periodTypes[ $id ] = new TwatchPeriodTypeInfo( $id, null );
					if( $periodTypes[ $id ]->minCode === null || $periodTypes[ $id ]->minCode > $periodType->minCode ) $periodTypes[ $id ]->minCode = $periodType->minCode;
					if( $periodTypes[ $id ]->maxCode === null || $periodTypes[ $id ]->maxCode < $periodType->maxCode ) $periodTypes[ $id ]->maxCode = $periodType->maxCode;
				}
				foreach( $twCounters as $twCounter ) {
					if( $twCounter->allowImport() && $twCounter->importNameMatches( $counter->name ) ) {
						$counter->mappedId = $twCounter->id;
						break;
					}
				}
				$counters[] = $counter;
			}

			$deleteE = ArdeXml::element( $element, 'delete', null );
			if( $deleteE === null ) {
				$delete = null;
			} else {
				$delete = TwatchImportDeleteInfo::fromXml( $deleteE, true );
			}

			return new self( $starts, $stops, $counters, $periodTypes, $availability, $delete );
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' name="'.ardeXmlEntities( $this->name ).'"'.$extraAttrib.'>', 1 );

			foreach( $this->periodTypes as $periodType ) {
				$periodType->printXml( $p, 'period_type' );
				$p->nl();
			}

			foreach( $this->counters as $counter ) {
				$counter->printXml( $p, 'counter' );
				$p->nl();
			}

			if( $this->delete !== null ) {
				$this->delete->printXml( $p, 'delete' );
				$p->nl();
			}

			$p->rel();
			$p->pn( '</'.$tagName.'>' );
		}
	}

	class TwatchImportCounterInfo {
		public $name;
		public $mappedId = null;
		public $periodTypes;
		public $rowCount = 0;
		public $availability = 0;
		public $delete;

		public function __construct( $name, $periodTypes, $element, $rowCount, $availability, $delete ) {
			$this->name = $name;
			$this->periodTypes = $periodTypes;
			$this->element = $element;
			$this->rowCount = $rowCount;
			$this->availability = $availability;
			$this->delete = $delete;
		}

		public static function fromXml( DOMElement $element ) {
			global $twatch;

			$name = ArdeXml::strAttribute( $element, 'name' );

			$rowCount = 0;

			$periodEs = new ArdeXmlMultiElemIter( $element, TwatchPeriod::getImportTagNames() );
			$periodTypes = array();
			foreach( $periodEs as $periodE ) {
				$period = TwatchPeriod::fromImportElement( $periodE );

				$count = 0;
				foreach( new ArdeXmlElemIter( $periodE, 'group' ) as $groupE ) {
					foreach( new ArdeXmlElemIter( $groupE, 'row' ) as $rowE ) ++$count;
				}
				foreach( new ArdeXmlElemIter( $periodE, 'row' ) as $rowE ) ++$count;
				if( $count == 0 ) ++$rowCount;
				else $rowCount += $count;

				if( !isset( $periodTypes[ $period->type ] ) ) $periodTypes[ $period->type ] = new TwatchPeriodTypeInfo( $period->type, 1 );
				else ++$periodTypes[ $period->type ]->count;
				if( $periodTypes[ $period->type ]->minCode === null || $periodTypes[ $period->type ]->minCode > $period->getCode() ) {
					$periodTypes[ $period->type ]->minCode = $period->getCode();
				}
				if( $periodTypes[ $period->type ]->maxCode === null || $periodTypes[ $period->type ]->maxCode < $period->getCode() ) {
					$periodTypes[ $period->type ]->maxCode = $period->getCode();
				}
			}

			$availability = TwatchImportFileInfo::getAvailability( $element );

			$deleteE = ArdeXml::element( $element, 'delete', null );
			if( $deleteE === null ) {
				$delete = null;
			} else {
				$delete = TwatchImportDeleteInfo::fromXml( $deleteE );
			}
			return new self( $name, $periodTypes, $element, $rowCount, $availability, $delete );
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			if( $this->mappedId === null ) {
				$mapped = '';
			} else {
				$mapped = ' mapped_id="'.$this->mappedId.'"';
			}
			$p->pl( '<'.$tagName.' name="'.ardeXmlEntities( $this->name ).'"'.$mapped.$extraAttrib.'>', 1 );
			foreach( $this->periodTypes as $periodType ) {
				$periodType->printXml( $p, 'period_type' );
				$p->nl();
			}
			$this->availability->printXml( $p, 'availability' );
			$p->nl();


			if( $this->delete !== null ) {
				$this->delete->printXml( $p, 'delete' );
				$p->nl();
			}
			$p->rel();

			$p->pn( '</'.$tagName.'>' );
		}


	}

	class TwatchImportDeleteInfo {
		public $tss;
		public $availability;
		public $rows = array();
		public $groups = array();

		public static function fromXml( DOMElement $element, $global = false ) {
			$o = new self();
			$o->tss = TwatchImportFileInfo::getTss( $element );
			$o->availability = TwatchImportFileInfo::getAvailability( $element, true );
			foreach( new ArdeXmlElemIter( $element, 'group' ) as $groupE ) {
				$groupName = ArdeXml::strAttribute( $groupE, 'name' );
				$o->groups[ $groupName ] = array();
				foreach( new ArdeXmlElemIter( $groupE, 'row' ) as $rowE ) {
					$o->groups[ $groupName ][ ArdeXml::strContent( $rowE ) ] = true;
				}
			}
			if( !count( $o->groups ) ) {
				foreach( new ArdeXmlElemIter( $element, 'row' ) as $rowE ) {
					$o->rows[ ArdeXml::strContent( $rowE ) ] = true;
				}
			}
			if( $global && ( count( $o->rows ) || count( $o->groups ) ) ) {
				throw new TwatchUserError( "You may not specify row or group for the global delete operation." );
			}
			return $o;
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$rowsCount = 0;
			$groupsCount = 0;
			foreach( $this->groups as $group ) {
				if( is_array( $group ) ) {
					$rowsCount += count( $group );
				} else {
					++$groupsCount;
				}
			}
			$rowsCount += count( $this->rows );
			$p->pl( '<'.$tagName.' row_count="'.$rowsCount.'" group_count="'.$groupsCount.'"'.$extraAttrib.'>', 1 );
			TwatchCounterAvailability::printTssXml( $this->tss, $p );
			$this->availability->printXml( $p, 'availability' );
			$p->relnl();
			$p->pn( '</'.$tagName.'>' );
		}

	}

	class TwatchPeriodTypeInfo {
		public $id;
		public $count;
		public $minCode = null;
		public $maxCode = null;
		public $determinedStartTs = null;
		public $determinedStopTs = null;

		public function __construct( $id, $count ) {
			$this->id = $id;
			$this->count = $count;
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			global $twatch;

			$min = TwatchPeriod::fromCode( $this->id, $this->minCode );
			$max = TwatchPeriod::fromCode( $this->id, $this->maxCode );

			if( $this->count === null ) {
				$count = '';
			} else {
				$count = ' count="'.$this->count.'"';
			}
			$p->pl( '<'.$tagName.' id="'.$this->id.'" name="'.TwatchPeriod::getTypeString( $this->id ).'"'.$count.$extraAttrib.'>' );
			$p->pl( '	<min code="'.$min->getCode().'" ts="'.$min->time->getDayStart().'">'.ardeXmlEntities( $min->getString( TwatchPeriod::STRING_IMPORT_NAME ) ).'</min>' );
			$p->pl( '	<max code="'.$max->getCode().'" ts="'.$max->time->dayOffset(1)->getDayStart().'">'.ardeXmlEntities( $max->getString( TwatchPeriod::STRING_IMPORT_NAME ) ).'</max>' );
			if( $this->determinedStartTs !== null ) {
				$p->pl( '	<det_start_ts>'.$this->determinedStartTs.'</det_start_ts>' );
			}
			if( $this->determinedStopTs !== null ) {
				$p->pl( '	<det_stop_ts>'.$this->determinedStopTs.'</det_stop_ts>' );
			}
			$p->pn( '</'.$tagName.'>' );
		}
	}

	class TwatchImportTime {
		public $ts;
		public $title;
		public $periodType;

		public function __construct( $ts, $title, $periodType ) {
			$this->ts = $ts;
			$this->title = $title;
			$this->periodType = $periodType;
		}

		public static function fromXml( DOMElement $element ) {
			global $twatch;
			$str = ArdeXml::strContent( $element );
			if( !preg_match( '/(\d\d\d\d)\-(\w\w\w)\-(\d\d)\s(\d\d):(\d\d):(\d\d)(\sGMT|)/', $str, $matches ) ) {
				throw new TwatchException( 'bad date string "'.$str."'" );
			}
			if( !isset( TwatchTime::$monthShortNums[ strtolower( $matches[2] ) ] ) ) throw new TwatchException( 'invalid month "'.$matches[2] );
			$month = TwatchTime::$monthShortNums[ strtolower( $matches[2] ) ];
			$ts = gmmktime( (int)$matches[4], (int)$matches[5], (int)$matches[6], $month, (int)$matches[3], (int)$matches[1] ) + ( $matches[7] ? $twatch->config->get( TwatchConfig::TIME_DIFFERENCE ) : 0 );
			$title = gmdate( 'Y-M-d H:i:s', $ts );
			return new self( $ts, $title, null );
		}
	}

?>