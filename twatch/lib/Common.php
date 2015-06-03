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

	require_once $ardeBase->path( 'lib/ArdeExpression.php' );
	require_once $ardeBase->path( 'lib/ArdeDisTaskManager.php' );
	require_once $twatch->path( 'db/DbLogger.php' );
	require_once $twatch->path( 'lib/Logger.php' );

	require_once $twatch->path( 'lib/Entity.php' );


	class TwatchTimePortion {
		public $startTs;
		public $endTs;
		public function TwatchTimePortion( $startTs, $endTs ) {
			$this->startTs = $startTs;
			$this->endTs = $endTs;
		}
		public function contains( $ts ) {
			return $ts >= $this->startTs && $ts < $this->endTs;
		}

		public function length() {
			return $this->endTs - $this->startTs;
		}

		function __sv( $l, $level, $name ) {
			__o( $l, $name.': TimePortion ' );
			__p( $l, 'startTs', $this->startTs );
			__p( $l, 'endTs', $this->endTs );
			__p( $l, 'length' , $this->length() );
			__c($l);
		}
	}

	class TwatchHourlyDistribution implements ArdeSerializable {
		public $hourWeights;

		function integral( $startTs, $endTs ) {
			$fullDays = (int)floor( ( $endTs - $startTs ) / 86400 );
			$startTs += $fullDays * 86400;
			if( $startTs == $endTs ) return $fullDays;

			$startTime = new TwatchTime( $startTs );
			$endTime = new TwatchTime( $startTime->hourOffset(1)->getHourStart() );
			$res = $fullDays;
			while( true ) {
				if( $endTs < $endTime->ts ) {
					return ( $res + ( ( $endTs - $startTime->ts ) / 3600 )
						* $this->hourWeights[ $startTime->getHour() ] );
				}
				$res += (( $endTime->ts - $startTime->ts ) / 3600 )
					* $this->hourWeights[ $startTime->getHour() ];
				$startTime->ts = $endTime->ts;
				$endTime->ts = $endTime->hourOffset(1)->getHourStart();
			}
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->hourWeights ) );
		}
		public static function makeSerialObject( ArdeSerialData $d ) {
			$o = new self();
			$o->hourWeights = $d->data[0];
			return $o;
		}

		function fillFlat() {
			$this->hourWeights = array();
			for( $i = 0; $i < 24; $i++ ) {
				$this->hourWeights[$i] = 1/24;
			}
		}
	}

	abstract class TwatchPeriod {
		public $type;

		const STRING_DEFAULT = 0;
		const STRING_IMPORT_NAME = 1;

		const ALL = 0;
		const DAY = 1;
		const MONTH = 2;

		public static $typeStrings = array(
			 self::ALL => 'All'
			,self::DAY => 'Daily'
			,self::MONTH => 'Monthly'
		);

		public static $importStringTypes = array(
			 'daily' => self::DAY
			,'monthly' => self::MONTH
			,'all' => self::ALL
		);

		public static $typeImportTagNames = array(
			 self::DAY => 'day'
			,self::MONTH => 'month'
			,self::ALL => 'all'
		);

		private $time;


		public static function getTypeString( $type ) {
			global $twatch;
			if( isset( self::$typeStrings[ $type ] ) ) {
				return $twatch->locale->text( self::$typeStrings[ $type ] );
			}
			return $type.'-invalid';
		}

		public static function fromType( $type ) {
			global $twatch;
			if( $type == TwatchPeriod::DAY ) {
				$o = $twatch->makeObject( 'TwatchPeriodDay' );
			} elseif( $type == TwatchPeriod::MONTH ) {
				$o = new TwatchPeriodMonth();
			} else {
				$o = new TwatchPeriodAll();
			}
			return $o;
		}

		public static function fromCode( $type, $code ) {
			$o = self::fromType( $type );
			$o->initWithCode( $code );
			return $o;
		}

		public static function fromImportElement( DOMElement $element ) {
			if( $element->tagName == 'day' ) {
				return TwatchPeriodDay::_fromImportElement( $element );
			} elseif( $element->tagName == 'month' ) {
				return TwatchPeriodMonth::_fromImportElement( $element );
			} elseif( $element->tagName == 'all' ) {
				return TwatchPeriodAll::_fromImportElement( $element );
			} else {
				throw new TwatchException( 'invalid period tag "'.$element->tagName.'"');
			}
		}

		public static function getImportTagNames() {
			return array( 'day' => true, 'month' => true, 'all' => true );
		}

		protected static function _fromImportElement( DOMElement $element ) { throw new TwatchException( 'should not be called' ); }

		public static function isValidTypeCode( $type, $code ) {
			$o = self::fromType( $type );
			return $o->isValidCode( $code );
		}


		abstract public function isValidCode( $code );

		abstract public function offset( $offset );


		public static function makePeriod( $type, $ts ) {
			global $twatch;
			if( $type == TwatchPeriod::DAY ) {
				return $twatch->makeObject( 'TwatchPeriodDay', $ts );
			} elseif( $type == TwatchPeriod::MONTH ) {
				return $twatch->makeObject( 'TwatchPeriodMonth', $ts );
			} elseif( $type == TwatchPeriod::ALL ) {
				return $twatch->makeObject( 'TwatchPeriodAll', $ts );
			} else {
				throw new ArdeException( 'invalid period type' );
			}
		}

		public static function hourCountStatic( $hour, $startTs, $endTs ) {
			$r = floor( ( $endTs - $startTs ) / 86400 );
			$startTs += $r * 86400;
			while( $startTs < $endTs ) {
				$nextStart = TwatchTime::getTime( $startTs )->hourOffset( 1 )->getHourStart();
				if( TwatchTime::getTime( $startTs )->getHour() == $hour ) {

					if( $endTs <= $nextStart  ) {
						$r += ( $endTs - $startTs ) / 3600;
						break;
					} else {
						$r += ( $nextStart - $startTs ) / 3600;
					}
				}
				$startTs = $nextStart;
			}
			return $r;
		}

		public static function dayCountStatic( $weekday, $startTs, $endTs ) {
			$r = floor( ( $endTs - $startTs ) / 604800 );
			$startTs += $r * 604800;
			while( $startTs < $endTs ) {
				$nextStart = TwatchTime::getTime( $startTs )->dayOffset( 1 )->getDayStart();
				if( TwatchTime::getTime( $startTs )->getWeekday() == $weekday ) {

					if( $endTs <= $nextStart  ) {
						$r += ( $endTs - $startTs ) / 86400;
						break;
					} else {
						$r += ( $nextStart - $startTs ) / 86400;
					}
				}
				$startTs = $nextStart;
			}
			return $r;
		}

		public function jsObject( $highlight ) {
			return 'new Period( '.$this->type.", '".$this->getCode()."', '".ArdeJs::escape( $this->getName() )."', ".( $highlight ? 'true' : 'false' )." )";
		}

		abstract public function getStartTs( $offset = 0 );

		abstract public function getEndTs( $offset = 0 );


		function getIntersection( $portions ) {
			$outPortions = array();
			$startTs = $this->getStartTs();
			$endTs = $this->getEndTs();
			foreach( $portions as $portion ) {
				if( $portion->endTs >= $startTs && $portion->startTs < $endTs ) {
					if( $portion->startTs >= $startTs ) {
						$oStartTs = $portion->startTs;
					} else {
						$oStartTs = $startTs;
					}
					if( $portion->endTs < $endTs ) {
						$oEndTs = $portion->endTs;
					} else {
						$oEndTs = $endTs;
					}
					$outPortions[] = new TwatchTimePortion( $oStartTs, $oEndTs );
				}
			}
			return $outPortions;
		}

		function length( $portions ) {
			$portions = $this->getIntersection( $portions );
			$length = 0;
			foreach( $portions as $portion ) {
				$length += $portion->length();
			}
			return $length;
		}

		function unionLength( $tss ) {
			return TwatchCounterAvailability::tssLength(
				TwatchCounterAvailability::unionTss( $tss, array( $this->getStartTs(), $this->getEndTs() ) )
			);
		}

		function weightedDays( $portions, TwatchHourlyDistribution $distribution ) {
			$portions = $this->getIntersection( $portions );
			$days = 0;
			foreach( $portions as $portion ) {
				$days += $distribution->integral( $portion->startTs, $portion->endTs );
			}
			return $days;
		}

		function hourCount( $hour, $portions ) {
			$portions = $this->getIntersection( $portions );
			$count = 0;
			foreach( $portions as $portion ) {
				$count += TwatchPeriod::hourCountStatic( $hour, $portion->startTs, $portion->endTs );
			}
			return $count;
		}

		function dayCount( $weekday, $portions ) {
			$portions = $this->getIntersection( $portions );
			$count = 0;
			foreach( $portions as $portion ) {
				$count += TwatchPeriod::dayCountStatic( $weekday, $portion->startTs, $portion->endTs );
			}
			return $count;
		}

		abstract function getCode( $offset = 0 );

		abstract function getName();

		public function getString( $id ) {
			return $this->getName();
		}

		public function __s() {
			return get_class($this).' - '.$this->getName();
		}
	}

	class TwatchPeriodDay extends TwatchPeriod {
		var $type = TwatchPeriod::DAY;

		const STRING_COMPACT = 1000;

		function getString( $id ) {
			if( $id == self::STRING_COMPACT ) {
				return $this->time->format( 'Y-m-d');
			} elseif( $id == self::STRING_IMPORT_NAME ) {
				return $this->time->format( 'Y-M-d' );
			} else {
				return parent::getString();
			}
		}

		function __construct( $ts = 0 ) {
			$this->time = new TwatchTime( $ts );
		}

		protected static function _fromImportElement( DOMElement $element ) {
			$code = ArdeXml::strAttribute( $element, 'code' );
			if( !preg_match( '/(\d\d\d\d)\-(\w\w\w)\-(\d\d)/', $code, $matches ) ) {
				throw new TwatchException( 'invalid day code "'.$code.'"' );
			}
			if( !isset( TwatchTime::$monthShortNums[ strtolower( $matches[2] ) ] ) ) throw new TwatchException( 'invalid month "'.$matches[2].'"' );
			$month = TwatchTime::$monthShortNums[ strtolower( $matches[2] ) ];
			$time = new TwatchTime();
			if( !$time->isValidDate( (int)$matches[1], $month, (int)$matches[3] ) ) throw new TwatchException( 'invalid day code "'.$code.'"' );
			$time->initWithDate( (int)$matches[1], $month, (int)$matches[3] );
			return new self( $time->ts );
		}

		function offset( $offset ) {
			return new TwatchPeriodDay( $this->time->dayOffset( $offset )->ts );
		}

		function initWithCode( $code ) {
			$this->time = TwatchTime::getTime()->initWithDayCode( $code );
		}
		function getCode( $offset = 0 ) {
			return $this->time->dayOffset( $offset )->getDayCode();
		}
		function getMonthCode( $offset = 0 ) {
			return $this->time->monthOffset( $offset )->getMonthCode();
		}

		function getName() {
			global $twatch;
			$dt = array (
				'weekday' => $twatch->locale->text( $this->time->getWeekdayLong() ),
				'month' => $twatch->locale->text( $this->time->getMonthShort() ),
				'day' => $twatch->locale->number( $this->time->getDay() )
			);
			return $twatch->locale->text( '{weekday} {month} {day}', $dt );
		}

		function get_id() {
			return (int)( ( $this->time->get_day_start() - strtotime('2000-01-01 00:00:00') ) / 86400 );
		}

		function getStartTs( $offset = 0 ) {
			return $this->time->dayOffset( $offset )->getDayStart();
		}
		function getEndTs( $offset = 0 ) {
			return $this->time->dayOffset( 1 + $offset )->getDayStart();
		}

		public function isValidCode( $code ) {
			return $this->time->isValidDayCode( $code );
		}




	}
	class TwatchPeriodMonth extends TwatchPeriod {
		var $type = TwatchPeriod::MONTH;

		function __construct( $ts = 0 ) {
			$this->time = new TwatchTime( $ts );
		}

		protected static function _fromImportElement( DOMElement $element ) {
			$code = ArdeXml::strAttribute( $element, 'code' );
			if( !preg_match( '/(\d\d\d\d)\-(\w\w\w)/', $code, $matches ) ) {
				throw new TwatchException( 'invalid month code "'.$code.'"' );
			}
			if( !isset( TwatchTime::$monthShortNums[ strtolower( $matches[2] ) ] ) ) throw new TwatchException( 'invalid month "'.$matches[2].'"' );
			$month = TwatchTime::$monthShortNums[ strtolower( $matches[2] ) ];
			$time = new TwatchTime();
			if( !$time->isValidDate( (int)$matches[1], $month, 1 ) ) throw new TwatchException( 'invalid month code "'.$code.'"' );
			$time->initWithDate( (int)$matches[1], $month, 1 );
			return new self( $time->ts );
		}

		function offset( $offset ) {
			return new TwatchPeriodMonth( $this->time->monthOffset( $offset )->ts );
		}

		function initWithCode( $code ) {
			$this->time = TwatchTime::getTime()->initWithMonthCode( $code );
		}

		function getCode( $offset = 0 ) {
			return $this->time->monthOffset( $offset )->getMonthCode();
		}

		function getName() {
			global $twatch;
			$m = $twatch->locale->text( $this->time->getMonthShort() );
			$y = $twatch->locale->number( $this->time->getYear() );
			return $m.' '.$y;
		}

		function get_id() {
			$month = $this->time->get_month_int();
			$year=$this->time->get_year_int()-2000;
			echo $month."<br />".$year."<br />";
			return $month+$year*12;
		}

		function getStartTs( $offset = 0 ) {
			return $this->time->monthOffset( $offset )->getMonthStart();
		}

		function getEndTs( $offset = 0 ) {
			return $this->time->monthOffset( 1 + $offset )->getMonthStart();
		}

		public function isValidCode( $code ) {
			return $this->time->isValidMonthCode( $code );
		}

		public function getString( $id ) {
			if( $id == self::STRING_IMPORT_NAME ) {
				return $this->time->format( 'Y-M' );
			} else {
				return parent::getString();
			}
		}
	}


	class TwatchPeriodAll extends TwatchPeriod {
		var $type = TwatchPeriod::ALL;
		function __construct( $ts = 0 ) {
			$this->time = new TwatchTime( $ts );
		}

		protected static function _fromImportElement( DOMElement $element ) {
			return new self();
		}

		function offset( $offset ) {
			return new TwatchPeriodAll();
		}

		function getCode( $offset = 0 ) {
			return '';
		}
		function getName() {
			global $twatch;
			return $twatch->locale->text( 'All' );
		}

		function getStartTs( $offset = 0 ) {
			return 0;
		}

		function getEndTs( $offset = 0 ) {
			return $GLOBALS['twatch']->now->ts + 86400;
		}

		function initWithCode( $code ) {
		}

		public function isValidCode( $code ) {
			return $code == '';
		}
	}



	class TwatchVisitorType implements ArdeSerializable {

		const NORMAL = 1;
		const ROBOT = 2;
		const ADMIN = 3;
		const SPAMMER = 4;

		public $id;
		public $name;
		public $when;

		public static $idStrings = array(
			 self::NORMAL => 'Normal'
			,self::ROBOT => 'Robot'
			,self::ADMIN => 'Admin'
			,self::SPAMMER => 'Spammer'
		);

		public $predefinedIds = array();

		public function __construct( $id, $name, $when ) {
			$this->id = $id;
			$this->name = $name;
			$this->when = $when;
		}


		public static function removeIdentifier( $entityId, $entityVId ) {
			global $twatch;
			$dbVt = new TwatchDbVisitorTypes( $twatch->db );
			$dbVt->removeIdentifier( $entityId, $entityVId );
		}

		public static function getEntityVIdRefs( $entityId ) {
			global $twatch;
			$dbVt = new TwatchDbVisitorTypes( $twatch->db );
			return array( $dbVt->getEntityVIdReference( $entityId ) );
		}

		public function isEquivalent( self $visitorType ) {
			if( $visitorType->name != $this->name ) return false;
			if( !ardeEquivOrderedArrays( $visitorType->when, $this->when ) ) return false;
			return true;
		}

		public function adding() {
			$when = new TwatchExpression( $this->when, null );
			$when->install();
		}

		public function removing() {
			$when = new TwatchExpression( $this->when, null );
			$when->uninstall();
		}

		public static function fromParams( $a, $new ) {
			global $twatch;

			if( $new ) {
				$id = $twatch->config->getNewSubId( TwatchConfig::VISITOR_TYPES );
			} else {
				$id = ArdeParam::int( $a, 'i' );
				if( !$twatch->config->propertyExists( TwatchConfig::VISITOR_TYPES, $id ) ) throw new TwatchException( 'visitor type '.$id.' does not exist' );
			}

			$name = ArdeParam::str( $_POST, 'n' );

			if( $id != TwatchVisitorType::NORMAL ) {
				$when = TwatchExpression::fromParam( ArdeParam::str( $a, 'w' ) );
				$res = $when->isValid();

				if( $res !== true ) {
					$e = new TwatchUserError( '"when" has Syntax Error' );
					$e->safeExtras[] = $res;
					throw $e;
				}
			} else {
				$when = new TwatchExpression( $twatch->config->get( TwatchConfig::VISITOR_TYPES, $id )->when );

			}

			return new self( $id, $name, $when->a );
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->id, $this->name, $this->when ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2] );
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' id="'.$this->id.'" name="'.ArdeJs::escape( $this->name ).'"'.$extraAttrib.'>', 1 );
			$expression = new TwatchExpression( $this->when );
			$expression->printXml( $p, 'when' );
			$p->relnl();
			$p->pl( '	<identifiers>', 1 );
			foreach( $this->getIdentifiers() as $id ) {
				$id->printXml( $p, 'id', new TwatchEntityView( true, false, false, EntityV::STRING_SELECT ) );
				$p->nl();
			}
			$p->rel();
			$p->pl( '	</identifiers>' );
			$p->pl( '</'.$tagName.'>' );
		}

		public function adminJsObject() {
			global $twatch;

			$ids = new ArdeAppender( ', ' );
			foreach( $this->getIdentifiers() as $entityV ) {
				$ids->append( 'new Identifier( '.$entityV->jsObject( new TwatchEntityView( true, false, false, EntityV::STRING_SELECT ), EntityV::JS_MODE_INLINE ).' )' );
			}
			$when = new TwatchExpression( $this->when );
			return 'new VisitorType( '.$this->id.", '".ArdeJs::escape( $this->name )."', ".$when->jsObject().", [ ".$ids->s." ] )";
		}

		public function getIdentifiers() {
			global $twatch;

			$dbVt = new TwatchDbVisitorTypes( $twatch->db );
			$ids = $dbVt->getIdentifiers( $this->id );
			$entityVs = array();
			$entVGen = new TwatchEntityVGen();
			foreach( $ids as $id ) {
				$entityVs[] = &$entVGen->make( $id->entityId, $id->entityVId );
			}
			$entVGen->finalizeEntityVs();
			return $entityVs;
		}

		public function jsObject() {
			global $twatch;
			return 'new VisitorType( '.$this->id.", '".ArdeJs::escape( $twatch->locale->text( $this->name ) )."' )";
		}

		public static function identifyVisitorType( $entityValues, $request ) {
			global $twatch;
			foreach( $twatch->config->getList( TwatchConfig::VISITOR_TYPES ) as $vt ) {
				if( $vt->id == self::NORMAL ) continue;
				if( count( $vt->when ) ) {
					$when = new TwatchExpression( $vt->when, $request );
					if( $when->evaluate() ) return $vt->id;
				}
			}
			$dbVis = new TwatchDbVisitorTypes( $twatch->db );
			$res = $dbVis->get( $entityValues );
			if( $res === null ) return self::NORMAL;
			return $res;
		}

		public function addIdentifier( $entityId, $entityVId ) {
			global $twatch;
			$dbVis = new TwatchDbVisitorTypes( $twatch->db );
			$dbVis->set( $entityId, $entityVId, $this->id );
		}

		public function install() {
			global $twatch;
			if( $this->predefinedIds !== null ) {
				$dbVis = new TwatchDbVisitorTypes( $twatch->db );
				foreach( $this->predefinedIds as $id ) {
					$dbVis->set( $id->entityId, $id->entityVId, $this->id );
				}
			}
		}

		public function uninstall() {
			global $twatch;
			$dbVis = new TwatchDbVisitorTypes( $twatch->db );
			$dbVis->removeVisitorType( $this->id );
		}

		public static function fullInstall( $overwrite = false ) {
			global $twatch;
			$dbVis = new TwatchDbVisitorTypes( $twatch->db );
			$dbVis->install( true );
			foreach( $twatch->config->getList( TwatchConfig::VISITOR_TYPES ) as $visitorType ) {
				$visitorType->install();
			}
		}

		public static function fullUninstall() {
			global $twatch;
			$dbVis = new TwatchDbVisitorTypes( $twatch->db );
			$dbVis->uninstall();
		}
	}

	class TwatchCounterAvailability implements ArdeSerializable {
		var $cid = 0;
		var $timestamps = array();


		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.$extraAttrib.'>', 1 );
			foreach( $this->timestamps as $periodType => $tss ) {
				$p->pl( '<period_type name="'.ardeXmlEntities( TwatchPeriod::$typeStrings[ $periodType ] ).'">', 1 );
				self::printTssXml( $tss, $p );
				$p->rel();
				$p->pl( '</period_type>' );
			}
			$p->rel();
			$p->pn( '</'.$tagName.'>' );
		}

		public static function printTssXml( $tss, ArdePrinter $p ) {
			foreach( $tss as $ts ) {
				$time = new TwatchTime( $ts );
				$p->pl( '<time>'.ardeXmlEntities( $time->getString( TwatchTime::STRING_FULL ) ).'</time>' );
			}
		}

		public static function tssLength( $tss ) {

			$isOn = false;
			$result = 0;
			$prev = 0;
			foreach( $tss as $ts ) {

				$isOn = !$isOn;
				if( $isOn ) {
					$prev = $ts;
				} else {
					$result += $ts - $prev;
				}
			}
			return $result;
		}

		public static function subtractTss( $aTss, $bTss ) {
			reset( $aTss );
			reset( $bTss );
			$bIsOn = false;
			$aIsOn = false;
			$resultIsOn = false;
			$resultTss = array();
			while( true ) {
				if( current( $bTss ) == current( $aTss ) ) {

					if( current( $bTss ) === false ) break;
					$ts = array_shift( $bTss );
					array_shift( $aTss );
					$bIsOn = !$bIsOn;
					$aIsOn = !$aIsOn;

					if( ( !$aIsOn && $bIsOn ) || ( $aIsOn && !$bIsOn ) ) {
						$resultTss[] = $ts;
						$resultIsOn = !$resultIsOn;
					}

				} elseif( current( $aTss ) === false || ( current( $bTss ) !== false && current( $bTss ) < current( $aTss ) ) ) {
					$ts = array_shift( $bTss );
					$bIsOn = !$bIsOn;
					if( $aIsOn ) {
						$resultTss[] = $ts;
						$resultIsOn = !$resultIsOn;
					}

				} elseif( current( $bTss ) === false || ( current( $aTss ) !== false && current( $bTss ) > current( $aTss ) ) ) {

			 		$ts = array_shift( $aTss );
					$aIsOn = !$aIsOn;
					if( !$bIsOn  ) {
						$resultTss[] = $ts;
						$resultIsOn = !$resultIsOn;
					}
				}
			}
			return $resultTss;
		}

		public static function unionTss( $thisTss, $otherTss ) {
			reset( $thisTss );
			reset( $otherTss );
			$otherIsOn = false;
			$thisIsOn = false;
			$resultIsOn = false;
			$resultTss = array();

			while( true ) {

				if( current( $otherTss ) == current( $thisTss ) ) {

					if( current( $otherTss ) === false ) {
						break;
					}
					$ts = array_shift( $otherTss );
					array_shift( $thisTss );
					$otherIsOn = !$otherIsOn;
					$thisIsOn = !$thisIsOn;

					if( $thisIsOn == $otherIsOn ) {
						$resultTss[] = $ts;
						$resultIsOn = !$resultIsOn;
					}

				} elseif( current( $thisTss ) === false || ( current( $otherTss ) !== false && current( $otherTss ) < current( $thisTss ) ) ) {
					$ts = array_shift( $otherTss );
					$otherIsOn = !$otherIsOn;
					if( ( $otherIsOn && $thisIsOn ) || ( !$otherIsOn && $resultIsOn ) ) {
						$resultTss[] = $ts;
						$resultIsOn = !$resultIsOn;
					}

				} elseif( current( $otherTss ) === false || ( current( $thisTss ) !== false && current( $otherTss ) > current( $thisTss ) ) ) {

			 		$ts = array_shift( $thisTss );
					$thisIsOn = !$thisIsOn;
					if( ( $thisIsOn && $otherIsOn ) || ( !$thisIsOn && $resultIsOn ) ) {
						$resultTss[] = $ts;
						$resultIsOn = !$resultIsOn;
					}
				}
			}
			return $resultTss;
		}

		public static function mergeTss( $thisTss, $otherTss ) {
			reset( $thisTss );
			reset( $otherTss );
			$otherIsOn = false;
			$thisIsOn = false;
			$resultIsOn = false;
			$resultTss = array();
			while( true ) {
				if( current( $otherTss ) == current( $thisTss ) ) {

					if( current( $otherTss ) === false ) break;
					$ts = array_shift( $otherTss );
					array_shift( $thisTss );
					$otherIsOn = !$otherIsOn;
					$thisIsOn = !$thisIsOn;
					if( $otherIsOn == $thisIsOn && $resultIsOn != $thisIsOn ) {
						$resultTss[] = $ts;
						$resultIsOn = !$resultIsOn;
					}
				} elseif( current( $thisTss ) === false || ( current( $otherTss ) !== false && current( $otherTss ) < current( $thisTss ) ) ) {
					$ts = array_shift( $otherTss );
					$otherIsOn = !$otherIsOn;
					if( ( $resultIsOn && !$otherIsOn && !$thisIsOn ) || ( !$resultIsOn && ($thisIsOn || $otherIsOn ) ) ) {
						$resultTss[] = $ts;
						$resultIsOn = !$resultIsOn;
					}

				} elseif( current( $otherTss ) === false || ( current( $thisTss ) !== false && current( $otherTss ) > current( $thisTss ) ) ) {

					$ts = array_shift( $thisTss );
					$thisIsOn = !$thisIsOn;
					if( ( $resultIsOn && !$otherIsOn && !$thisIsOn ) || ( !$resultIsOn && ($thisIsOn || $otherIsOn ) ) ) {
						$resultTss[] = $ts;
						$resultIsOn = !$resultIsOn;
					}
				}

			}
			return $resultTss;
		}

		function subtract( TwatchCounterAvailability $other ) {

			foreach( $this->timestamps as $periodType => $v ) $periodTypes[ $periodType ] = true;
			foreach( $other->timestamps as $periodType => $v ) $periodTypes[ $periodType ] = true;

			foreach( $periodTypes as $periodType => $notImportant ) {
				$otherTss = self::removeRedundantTs( $other->getTimestamps( $periodType ) );
				$thisTss = self::removeRedundantTs( $this->getTimestamps( $periodType ) );

				$this->timestamps[ $periodType ] = self::subtractTss( $thisTss, $otherTss );
			}
		}

		function union( TwatchCounterAvailability $other ) {
			foreach( $this->timestamps as $periodType => $v ) $periodTypes[ $periodType ] = true;
			foreach( $other->timestamps as $periodType => $v ) $periodTypes[ $periodType ] = true;

			foreach( $periodTypes as $periodType => $notImportant ) {
				$otherTss = self::removeRedundantTs( $other->getTimestamps( $periodType ) );
				$thisTss = self::removeRedundantTs( $this->getTimestamps( $periodType ) );

				$this->timestamps[ $periodType ] = self::unionTss( $thisTss, $otherTss );
			}
		}

		function merge( TwatchCounterAvailability $other ) {

			foreach( $this->timestamps as $periodType => $v ) $periodTypes[ $periodType ] = true;
			foreach( $other->timestamps as $periodType => $v ) $periodTypes[ $periodType ] = true;

			foreach( $periodTypes as $periodType => $notImportant ) {
				$otherTss = self::removeRedundantTs( $other->getTimestamps( $periodType ) );
				$thisTss = self::removeRedundantTs( $this->getTimestamps( $periodType ) );


				$this->timestamps[ $periodType ] = self::mergeTss( $thisTss, $otherTss );
			}
		}


		protected static function removeRedundantTs( $timestamps ) {
			if( !count( $timestamps ) ) return $timestamps;
			$ts = reset( $timestamps );
			$count = 1;
			$newTimestamps = array();
			while( true ) {
				$nextTs = next( $timestamps );
				if( $nextTs !== $ts ) {
					if( $count % 2 ) {
						$newTimestamps[] = $ts;
					}
					if( $nextTs === false ) break;
					$ts = $nextTs;
					$count = 1;
				} else {
					++$count;
				}

			}
			return $newTimestamps;
		}

		function getTimestamps( $periodType, $cutFuture = false ) {
			if( isset( $this->timestamps[ $periodType ] ) ) {
				$res = $this->timestamps[ $periodType ];
				if( $cutFuture ) {
					global $twatch;
					$res = $this->timestamps[ $periodType ];
					while( end( $res ) >= $twatch->now->ts ) {
						array_pop( $res );
					}
					if( count( $res ) % 2 ) $res[] = $twatch->now->ts;
				}
				return $res;
			}
			return array();
		}

		function getStart( $periodType ) {
			if( isset( $this->timestamps[ $periodType ][0] ) ) return $this->timestamps[ $periodType ][0];
			return null;
		}

		function getStop( $periodType ) {
			if( isset( $this->timestamps[ $periodType ] ) && !( count( $this->timestamps[ $periodType ] ) % 2 ) ) return end( $this->timestamps[ $periodType ] );
			return null;
		}

		function isAvailable() {
			if( !count( $this->timestamps ) ) return false;
			return count( reset( $this->timestamps ) ) % 2 == 1;
		}

		function removePeriodType( $periodType ) {
			unset( $this->timestamps[ $periodType ] );
		}

		function addTime( $ts, $periodType ) {
			if( !isset( $this->timestamps[$periodType] ) ) $this->timestamps[$periodType] = array();

			if( end( $this->timestamps[$periodType] ) > $ts ) throw new TwatchException( 'time '.$ts.' smaller than previous time '.end( $this->timestamps[ $periodType ] ) );
			$this->timestamps[ $periodType ][] = $ts;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->cid, $this->timestamps ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			$o = new self();
			$o->cid = $d->data[0];
			$o->timestamps = $d->data[1];
			return $o;
		}

		public function getPortions( $periodType ) {
			global $twatch;
			$timestamps = $this->getTimestamps( $periodType );
			if( !count( $timestamps ) ) return array();
			$portions = array();
			$portion = new TwatchTimePortion( null, null );
			foreach( $timestamps as $ts ) {
				if( $portion->startTs === null ) {
					$portion->startTs = $ts;
				} else {
					$portion->endTs = $ts;
					$portions[] = $portion;
					$portion = new TwatchTimePortion( null, null );
				}
			}

			if( $portion->startTs !== null && $portion->endTs === null ) {
				$portion->endTs = $twatch->now->ts;
				$portions[] = $portion;
			}

			return $portions;
		}

		public function setStart( $ts, $periodType ) {

			if( !isset( $this->timestamps[ $periodType ][0] ) ) {
				return;
			}


			if( $this->timestamps[ $periodType ][0] > $ts ) {
				return;
			}

			$newTimestamps = array();

			$isEndTs = false;
			$found = false;
			$count = count( $this->timestamps[ $periodType ] );
			for( $i = 0; $i < $count; ++$i ) {
				if( !$found ) {
					if( $ts < $this->timestamps[$periodType][$i] ) {
						if( $isEndTs ) $newTimestamps[] = $ts;
						$newTimestamps[] = $this->timestamps[$periodType][$i];
						$found = true;
					}
				} else {
					$newTimestamps[] = $this->timestamps[$periodType][$i];
				}
				$isEndTs = !$isEndTs;
			}
			if( !$found ) {
				$newTimestamps[] = $ts;
			}
			$this->timestamps[ $periodType ] = $newTimestamps;
		}

	}

	class TwatchPathAnalyzer implements ArdeSerializable {

		const MAX_COLUMNS = 10;

		const PATH_UNKNOWN = 0;
		const PATH_START = 1;
		const PATH_UNKNOWN_FATE = 2;
		const PATH_END = 3;
		const PATH_UNKNOWN_PAST = 4;
		const PATH_UNKNOWN_DATA = 5;

		public $maxSamples;
		public $perTask;
		public $depth;
		public $dataColumns;
		public $cleanupCycle;
		public $pathsLiveFor;

		public function TwatchPathAnalyzer( $maxSamples, $perTask, $depth, $dataColumns, $cleanupCycle, $pathsLiveFor ) {
			$this->maxSamples = $maxSamples;
			$this->perTask = $perTask;
			$this->depth = $depth;
			$this->dataColumns = $dataColumns;
			$this->cleanupCycle = $cleanupCycle;
			$this->pathsLiveFor = $pathsLiveFor;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->maxSamples, $this->perTask, $this->depth, $this->dataColumns, $this->cleanupCycle, $this->pathsLiveFor ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3], $d->data[4], $d->data[5] );
		}

		public function jsObject( $width, $height, $receiver, $websiteId ) {
			return 'new PathAnalyzer( '.$width.', '.$height.', '.count( $this->dataColumns ).', '.$this->depth.', "'.$receiver.'", '.$websiteId.' )';
		}

		public function adminJsObject() {
			$dataColumns = new ArdeAppender( ', ' );
			foreach( $this->dataColumns as $dataColumn ) {
				$dataColumns->append( $dataColumn );
			}
			return 'new PathAnalyzer( '.$this->maxSamples.', '.$this->perTask.', '.$this->depth.', [ '.$dataColumns->s.' ], '.$this->cleanupCycle.', '.$this->pathsLiveFor.' )';
		}



		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' max_samples="'.$this->maxSamples.'" per_task="'.$this->perTask.'" depth="'.$this->depth.
					'" cleanup_cycle="'.$this->cleanupCycle.'" paths_live_for="'.$this->pathsLiveFor.'"'.$extraAttrib.'>' );
			$p->pl( '	<data_columns>', 1 );
			foreach( $this->dataColumns as $entityId ) {
				$p->pl( '<column entity_id = "'.$entityId.'" />' );
			}
			$p->rel();
			$p->pl( '	</data_columns>' );
			$p->pn( '</'.$tagName.'>' );
		}

		public function getDbAccessUnitInfo() {
			global $twatch;

			$res = new ArdeDbAccessUnitInfo( 'Path Analysis', array() );
			$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
			foreach( $websites as $website ) {
				if( $website->parent ) continue;
				$dbPaths = new TwatchDbPathAnalyzer( $twatch->db, $website->getSub() );
				$dbPaths->addDbAccessUnitInfo( $res, $website->name );
			}
			return $res;
		}

		public static function entityIsUsed( $entityId ) {
			global $twatch;

			if( !$twatch->state->get( TwatchState::PATH_ANALYZER_INSTALLED ) ) return false;
			$pathAnalyzer = $twatch->config->get( TwatchConfig::PATH_ANALYZER );
			if( $entityId == TwatchEntity::PAGE ) return 'Path Analyzer\'s paths';
			if( in_array( $entityId, $pathAnalyzer->dataColumns ) ) return 'Path Analyzer\s data columns';
			return false;
		}

		public static function getAllEntityVIdRefs( $entityId ) {
			global $twatch;

			$res = array();
			if( !$twatch->state->get( TwatchState::PATH_ANALYZER_INSTALLED ) ) return $res;
			$pathAnalyzer = $twatch->config->get( TwatchConfig::PATH_ANALYZER );
			foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $website ) {
				if( $website->parent ) continue;
				foreach( $pathAnalyzer->getEntityVIdRefs( $entityId, $website ) as $ref ) $res[] = $ref;
			}
			return $res;
		}

		public function getEntityVIdRefs( $entityId, TwatchWebsite $website ) {
			$res = array();
			$dataColumnsCount = count( $this->dataColumns );
			if( $entityId == TwatchEntity::PAGE ) {
				for( $i = 0; $i < $this->depth; ++$i ) {
					$res[] = TwatchDbPathAnalyzer::getColumnRef( $website->getSub(), $dataColumnsCount + $i );
				}
			}
			for( $i = 0; $i < $dataColumnsCount; ++$i ) {
				if( $this->dataColumns[ $i ] == $entityId ) {
					$res[] = TwatchDbPathAnalyzer::getColumnRef( $website->getSub(), $i );
				}
			}
			return $res;
		}

		public function installForWebsite( TwatchWebsite $website, $overwrite = false ) {
			global $twatch;

			if( $website->parent ) return;

			$dbPaths = new TwatchDbPathAnalyzer( $twatch->db, $website->getSub() );
			$dbPaths->install( $this->depth + count( $this->dataColumns ), $overwrite );

			$round = $twatch->state->get( TwatchState::PATH_NEXT_CLEANUP_ROUND );

			$taskManager = new TwatchTaskManager();

			$cleanupTask = new TwatchCleanupPaths( $round, $website->getSub() );
			$cleanupTask->due = $twatch->now->ts + $this->cleanupCycle;
			$taskManager->queueTasks( array( $cleanupTask ) );
		}

		public function uninstallForWebsite( TwatchWebsite $website ) {
			global $twatch;

			if( $website->parent ) return;

			$dbPaths = new TwatchDbPathAnalyzer( $twatch->db, $website->getSub() );
			$dbPaths->uninstall();

			$taskManager = new TwatchTaskManager();

			$tasks = $taskManager->getAllTasks( 'TwatchCleanupPaths' );
			foreach( $tasks as $task ) {
				if( $task->sub == $website->getSub() ) {
					$taskManager->deleteTask( $task->taskId, $task->inQueue );
				}
			}

			$tasks = $taskManager->getAllTasks( 'TwatchSamplePaths' );
			foreach( $tasks as $task ) {
				if( $task->sub == $website->getSub() ) {
					$taskManager->deleteTask( $task->taskId, $task->inQueue );
				}
			}

		}


		public static function fullInstall( $overwrite ) {
			global $twatch;
			$pathAnalyzer = $twatch->config->get( TwatchConfig::PATH_ANALYZER );
			foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $website ) {
				$pathAnalyzer->installForWebsite( $website, $overwrite );
			}
			$twatch->state->set( true, TwatchState::PATH_ANALYZER_INSTALLED );
		}

		public static function fullUninstall() {
			global $twatch;
			$pathAnalyzer = $twatch->config->get( TwatchConfig::PATH_ANALYZER );

			$twatch->state->set( false, TwatchState::PATH_ANALYZER_INSTALLED );

			foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $website ) {
				$pathAnalyzer->uninstallForWebsite( $website );
			}

			$taskManager = new TwatchTaskManager();
			$taskManager->deleteAllTasks( 'TwatchCleanupPaths' );
			$taskManager->deleteAllTasks( 'TwatchSamplePaths' );

			$twatch->state->set( 1, TwatchState::PATH_NEXT_CLEANUP_ROUND );
		}

		public static function fullBaseUninstall() {
			global $twatch;
			foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $website ) {
				if( $website->parent ) continue;
				$dbPaths = new TwatchDbPathAnalyzer( $twatch->db, $website->getSub() );
				$dbPaths->uninstall();
			}
		}

		public function getSamplingTasks( $time, $subs ) {
			global $twatch;

			$start = $time->dayOffset(-2)->getDayStart();
			$end = $time->dayOffset(-1)->getDayStart();
			$unions = new ArdeAppender(' union all ');
			foreach( $subs as $sub ) {
				$unions->append( '('.$twatch->db->make_query_sub( $sub, "SELECT '".$sub."',COUNT(*) FROM", 's',
														'WHERE first>='.$start.' AND first<'.$end ).' AND vt='.TwatchVisitorType::NORMAL.')' );
			}
			$res = $twatch->db->query( $unions->s );

			$tasks = array();
			while( $r = mysql_fetch_row( $res ) ) {
				$sub = $r[0];
				$availableSamples = (int)$r[1];
				$brk = false;
				for( $i=0; !$brk && $i < $this->maxSamples + $this->perTask; $i += $this->perTask ) {
					$offset = $i;
					$limit = $this->perTask;
					if( $offset + $limit > $this->maxSamples ) {
						$limit = $this->maxSamples - $offset;
						$brk = true;
					} elseif( $offset + $limit > $availableSamples ) {
						$limit = $availableSamples - $offset;
						$brk = true;
					}
					if( $limit >= 1 ) {
						$tasks[] = new twatchSamplePaths( $sub, $start, $end, $offset, $limit, $this->depth, $this->dataColumns, $this->pathsLiveFor );
					}
				}
			}
			return $tasks;
		}

	}

	class TwatchCleanupPaths extends ArdeDisTask {

		var $round;
		var $sub;

		function __construct( $round = null, $sub = null ) {
			$this->round = $round;
			$this->sub = $sub;
		}


		public function jsParams() {
			global $twatch;

			$sub = $this->sub;

			$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
			foreach( $websites as $website ) {
				if( $website->getSub() == $this->sub ) {
					$sub = $website->name;
					break;
				}
			}

			$dueTime = new TwatchTime( $this->due );
			return ' '.$this->round.", '".ArdeJs::escape( $sub )."', '".$dueTime->getString( TwatchTime::STRING_FULL )."', ".($this->inQueue?'true':'false')." ";
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			global $twatch;

			$sub = $this->sub;

			$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
			foreach( $websites as $website ) {
				if( $website->getSub() == $this->sub ) {
					$sub = $website->name;
					break;
				}
			}

			$dueTime = new TwatchTime( $this->due );
			$p->pn( '<'.$tagName.' round="'.$this->round.'" website="'.$sub.'" in_queue="'.($this->inQueue?'true':'false').'" due="'.$dueTime->getString( TwatchTime::STRING_FULL ).'"'.$extraAttrib.' />' );
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->round, $this->sub ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1] );
		}

		function run() {
			global $twatch;
			$twatch->db->query_sub( $this->sub, "DELETE FROM",'p',"WHERE cr <= ".$this->round );

			$pathAnalyzer = &$twatch->config->get( TwatchConfig::PATH_ANALYZER );
			$nextCleanupTask = new TwatchCleanupPaths( $this->round + 1, $this->sub );
			$nextCleanupTask->due = $this->due + $pathAnalyzer->cleanupCycle;
			$taskm = new TwatchTaskManager();
			$taskm->queueTasks( array( $nextCleanupTask ) );

			$twatch->state->set( $this->round + 1, TwatchState::PATH_NEXT_CLEANUP_ROUND );
		}
	}

	class TwatchSamplePaths extends ArdeDisTask {

		public $sub;
		private $startTs;
		private $endTs;
		private $offset;
		private $limit;
		private $depth;
		private $dataColumns;
		private $pathsLiveFor;

		function __construct( $sub = 0, $startTs = 0, $endTs = 0, $offset = 0, $limit = 0, $depth = 0, $dataColumns = 0, $pathsLiveFor ) {
			$this->sub = $sub;
			$this->startTs = $startTs;
			$this->endTs = $endTs;
			$this->offset = $offset;
			$this->limit = $limit;
			$this->depth = $depth;
			$this->dataColumns = $dataColumns;
			$this->pathsLiveFor = $pathsLiveFor;
		}



		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->sub, $this->startTs, $this->endTs, $this->offset, $this->limit, $this->depth, $this->dataColumns, $this->pathsLiveFor ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3], $d->data[4], $d->data[5], $d->data[6], $d->data[7] );
		}

		function run() {
			global $twatch;

			$nextCleanupRound = $twatch->state->get( TwatchState::PATH_NEXT_CLEANUP_ROUND );

			$dbPaths = new TwatchDbPathAnalyzer( $twatch->db, $this->sub );
			$dbPaths->samplePaths( $this->startTs, $this->endTs, $this->offset, $this->limit, $this->depth, $this->dataColumns, $this->pathsLiveFor, $nextCleanupRound, TwatchVisitorType::NORMAL );
		}
	}

	class TwatchRDataWriter implements ArdeSerializable {

		const AGT_STR = 1;
		const IP = 2;
		const PIP = 3;
		const REF = 4;
		const AGT = 5;
		const REF_GROUP = 7;
		const ADMIN_COOKIE = 8;

		var $id;
		var $entityId;
		var $when;

		const MAX_DEFAULTS = 100;

		function __construct( $id, $entityId, $when = array() ) {
			$this->id = $id;
			$this->entityId = $entityId;
			$this->when = $when;
		}

		public function adding() {
			$when = new TwatchExpression( $this->when, null );
			$when->install();
		}

		public function removing() {
			$when = new TwatchExpression( $this->when, null );
			$when->uninstall();
		}

		public function removeData() {
			global $twatch;
			foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $website ) {
				if( $website->parent ) continue;
				$dbRequestData = new TwatchDbRequestData( $twatch->db, $website->getSub() );
				$dbRequestData->delete( $this->entityId );
			}
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->id, $this->entityId, $this->when ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2] );
		}

		public function jsObject() {
			$when = new TwatchExpression( $this->when );
			return 'new DataWriter( '.$this->id.', '.$this->entityId.', '.$when->jsObject().' )';
		}

		public function isEquivalent( self $dataWriter ) {
			if( $dataWriter->id != $this->id ) return false;
			if( $dataWriter->entityId != $this->entityId ) return false;
			if( !ardeEquivOrderedArrays( $dataWriter->when, $this->when ) ) return false;
			return true;
		}

		public static function fromParams( $a, $new ) {
			global $twatch;

			if( $new ) {
				$id = null;
			} else {
				$id = ArdeParam::int( $a, 'i' );
				if( !$twatch->config->propertyExists( TwatchConfig::RDATA_WRITERS, $id ) ) throw new TwatchException( 'data writer '.$id.' not found' );
			}
			$entityId = ArdeParam::int( $a, 'ei' );
			if( !$twatch->config->propertyExists( TwatchConfig::ENTITIES, $entityId ) ) throw new TwatchException( 'entity '.$id.' does not exist' );

			$when = TwatchExpression::fromParam( ArdeParam::str( $a, 'w' ) );
			$res = $when->isValid();

			if( $res !== true ) {
				$e = new TwatchUserError( '"when" has Syntax Error' );
				$e->safeExtras[] = $res;
				throw $e;
			}
			$when = $when->a;

			return new self( $id, $entityId, $when );
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' id="'.$this->id.'" entity_id="'.$this->entityId.'"'.$extraAttrib.'>', 1 );
			$when = new TwatchExpression( $this->when );
			$when->printXml( $p, 'when' );
			$p->relnl();
			$p->pn( '</'.$tagName.'>' );
		}
	}


	abstract class TwatchCounter implements ArdeSerializable {

		const PAGE_VIEWS = 1;
		const SESSIONS = 2;
		const VISITORS = 3;
		const NEW_VISITORS = 4;
		const ROBOT_PVIEWS = 5;
		const PAGES = 6;
		const REFGROUPS = 7;
		const REFERRERS = 8;
		const BROWSERS = 9;
		const UA_STRINGS = 10;
		const DIST_HOURLY = 12;
		const DIST_WEEKLY = 13;
		const ROBOTS = 19;

		public $id;
		public $name;
		public $periodTypes;
		public $delete;
		public $when;

		const TYPE_SINGLE = 0;
		const TYPE_LIST = 1;
		const TYPE_GROUPED = 2;

		const MAX_DEFAULTS = 100;

		public static $typeStrings = array (
			 self::TYPE_SINGLE => 'single'
			,self::TYPE_LIST => 'list'
			,self::TYPE_GROUPED => 'grouped'
		);

		function __construct( $id, $name, $periodTypes, $when = array(), $delete = array() ) {
			$this->id = $id;
			$this->name = $name;
			$this->periodTypes = $periodTypes;
			$this->delete = $delete;
			$this->when = $when;
		}

		public function importNameMatches( $name ) {
			if( !strcasecmp( $name, $this->name ) ) return true;
		}

		abstract public function isViewable( ArdeUserOrGroup $user );
		
		public function update( TwatchCounter $counter ) {
			global $twatch;

			$this->id = $counter->id;
			$this->name = $counter->name;

			$cavail = &$twatch->state->get( TwatchState::COUNTERS_AVAIL, $this->id );
			foreach( $this->periodTypes as $periodType ) {
				if( !in_array( $periodType, $counter->periodTypes ) ) {
					$cavail->removePeriodType( $periodType );
					$twatch->state->setInternal( TwatchState::COUNTERS_AVAIL, $this->id );
					$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
					foreach( $websites as $website ) {
						if( $website->parent ) continue;
						$dbCounters = new TwatchDbHistory( $twatch->db, $website->getSub() );
						$dbCounters->deleteCounterData( $this->id, $periodType );
					}
				}
			}

			foreach( $counter->periodTypes as $periodType ) {
				if( !in_array( $periodType, $this->periodTypes ) ) {
					if( $cavail->isAvailable() ) {
						$cavail->addTime( $GLOBALS['twatch']->now->ts, $periodType );
						$twatch->state->setInternal( TwatchState::COUNTERS_AVAIL, $this->id );
					}
				}
			}

			$this->periodTypes = $counter->periodTypes;

			$this->delete = $counter->delete;
			$this->when = $counter->when;
		}

		public function increment( TwatchDbHistory $dbCounters, $groupId = 0, $entityVId = 0 ) {
			global $twatch;
			$list = ( $this->getType() != TwatchCounter::TYPE_SINGLE );
			foreach( $this->periodTypes as $periodType ) {
				$period = TwatchPeriod::makePeriod( $periodType, $twatch->now->ts );
				if( $list ) {
					$withBuffer = isset( $this->activeTrim[ $periodType ] );
				} else {
					$withBuffer = false;
				}
				$dbCounters->increment( $this->id, $groupId, $entityVId, $periodType, $period->getCode(), $list, $withBuffer );
			}
		}

		public function isEquivalent( self $counter ) {
			if( $counter->id != $this->id ) return false;
			if( $counter->name != $this->name ) return false;
			if( !ardeEquivSets( $counter->periodTypes, $this->periodTypes ) ) return false;
			if( !ardeEquivArrays( $counter->delete, $this->delete ) ) return false;
			if( !ardeEquivOrderedArrays( $counter->when, $this->when ) ) return false;
			return true;
		}

		abstract function allowImport();

		public function getSerialData() {
			return new ArdeSerialData( 2, array( $this->id, $this->name, $this->periodTypes, $this->when, $this->delete ) );
		}

		public function getType() {
			if( $this instanceof TwatchGroupedCounter ) return self::TYPE_GROUPED;
			if( $this instanceof TwatchListCounter ) return self::TYPE_LIST;
			return self::TYPE_SINGLE;
		}



		public static function getCleanupTasks( $time, &$subs, $dtt ) {
			global $twatch;

			$tasks = array();

			foreach( $twatch->config->getList( TwatchConfig::COUNTERS ) as $counter ) {

				if( isset( $counter->delete[$dtt] ) ) {
					if( $dtt == TwatchPeriod::DAY )
						$del_dt = $time->dayOffset( -$counter->delete[$dtt] )->getDayCode();
					else
						$del_dt = $time->monthOffset( -$counter->delete[$dtt] )->getMonthCode();
					foreach( $subs as $sub ) {
						$tasks[] = new TwatchDeleteCounter( $counter->id, $dtt, $del_dt, $sub );
					}
				}
				if( isset( $counter->trim[$dtt] )) {
					if( $dtt == TwatchPeriod::DAY )
						$trim_dt = $time->dayOffset( -$counter->trim[$dtt][0] )->getDayCode();
					else
						$trim_dt = $time->monthOffset( -$counter->trim[$dtt][0] )->getMonthCode();
					foreach( $subs as $sub ) {
						$tasks[] = new TwatchTrimCounter( $counter->id, $dtt, $trim_dt, $counter->trim[$dtt][1], $sub );
					}
				}
			}
			return $tasks;
		}

		public function cleanup() {
			global $twatch;

			$this->removeTasks();

			$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
			foreach( $websites as $website ) {
				if( $website->parent ) continue;
				$dbCounters = new TwatchDbHistory( $twatch->db, $website->getSub() );
				foreach( $this->periodTypes as $periodType ) {
					$period = TwatchPeriod::makePeriod( $periodType, $twatch->now->ts );
					if( isset( $this->delete[ $periodType ] ) ) {
						$periodCode =  $period->getCode( -$this->delete[ $periodType ] );
						$dbCounters->deleteOldData( $this->id, $periodType, $periodCode );
					}
					if( isset( $this->trim[ $periodType ] ) ) {
						$periodCode =  $period->getCode( -$this->trim[ $periodType ][0] );
						$dbCounters->trimOldData( $this->id, $periodType, $periodCode, $this->trim[ $periodType ][1] );
					}
				}
			}
			$avail = &$twatch->state->get( TwatchState::COUNTERS_AVAIL, $this->id );
			foreach( $this->periodTypes as $periodType ) {
				if( isset( $this->delete[ $periodType ] ) ) {
					$period = TwatchPeriod::makePeriod( $periodType, $twatch->now->ts );
					$newStartTs = $period->getStartTs( -$this->delete[ $periodType ] + 1 );
					$avail->setStart( $newStartTs, $periodType );
				}
			}
			$twatch->state->setInternal( TwatchState::COUNTERS_AVAIL, $this->id );
		}

		public function isUsed() {
			global $twatch;

			return false;
		}

		public function install() {
			global $twatch;
			if( !$twatch->state->propertyExists( TwatchState::COUNTERS_AVAIL, $this->id ) ) {
				$cavail = new TwatchCounterAvailability();
				$cavail->cid = $this->id;
				$twatch->state->set( $cavail, TwatchState::COUNTERS_AVAIL, $this->id );
			}

		}



		public function adding() {
			$when = new TwatchExpression( $this->when, null );
			$when->install();
		}

		public function removing() {
			$when = new TwatchExpression( $this->when, null );
			$when->uninstall();
		}

		public static function entityIsUsed( $entityId ) {
			global $twatch;
			$counters = $twatch->config->getList( TwatchConfig::COUNTERS );
			foreach( $counters as $counter ) {
				$res = $counter->usesEntity( $entityId );
				if( $res !== false ) return $res;
			}
			return false;
		}

		public static function getAllEntityVIdReferences( $entityId ) {
			global $twatch;

			$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
			$counters = $twatch->config->getList( TwatchConfig::COUNTERS );
			$res = array();
			foreach( $websites as $website ) {
				if( $website->parent ) continue;
				foreach( $counters as $counter ) {
					$resC = $counter->getEntityVIdReferences( $entityId, $website );
					foreach( $resC as $resCE ) $res[] = $resCE;
				}
			}
			return $res;
		}

		protected static function updateDeletedAvail( TwatchCounterAvailability& $counterAvail, $periodType, $timestamps ) {
			global $twatch;
			$currentTss = $counterAvail->getTimestamps( $periodType );
			if( count( $timestamps ) ) {
				if( count( $currentTss ) ) {
					$counterAvail->timestamps[ $periodType ] = TwatchCounterAvailability::subtractTss( $currentTss, $timestamps );
				}
			} else {
				if( count( $currentTss ) % 2 ) {
					$counterAvail->timestamps[ $periodType ] = array( $twatch->now->ts );
				} else {
					$counterAvail->timestamps[ $periodType ] = array();
				}
			}
		}


		protected function deletePtData( TwatchDbHistory $dbHistory, TwatchCounterAvailability $counterAvail, $groupEntityVId, $entityVId, $periodTypeId, $timestamps ) {
			global $twatch;


			if( count( $timestamps ) ) {
				if( count( $timestamps ) % 2 ) throw new TwatchUserError( "delete operation must have a final stop" );

				$currentTss = $counterAvail->getTimestamps( $periodTypeId, true );
				$difference = TwatchCounterAvailability::subtractTss( $currentTss, $timestamps );


				$period = TwatchPeriod::makePeriod( $periodTypeId, reset( $timestamps ) );
				$startCode = $period->getCode();
				$stop = TwatchPeriod::makePeriod( $periodTypeId, end( $timestamps ) );
				$stopCode = $stop->getCode();



				$periodCode = $period->getCode();
				do {
					$currentLength = $period->unionLength( $currentTss );
					if( $currentLength ) {
						$mergeRatio = $period->unionLength( $difference ) / $currentLength;

						if( $entityVId !== null ) {
							$this->replaceRow( $dbHistory, $period, $groupEntityVId === null ? 0 : $groupEntityVId, $entityVId, 0, $mergeRatio );
						} else {
							$this->replace( $dbHistory, $period, $this instanceof TwatchListCounter ? array() : 0, $mergeRatio, $groupEntityVId );
						}
					}

					$period = $period->offset(1);
					$periodCode = $period->getCode();
				} while( $periodCode <= $stopCode && $periodCode != $startCode );


			} else {
				$dbHistory->deleteData( $this->id, $groupEntityVId, $entityVId, $periodTypeId );
			}
		}

		public function deleteData( TwatchDbHistory $dbHistory, TwatchDbPassiveDict $dbDict, TwatchWebsite $website, $groupName, $entityVName, $periodTypeId, $timestamps ) {
			global $twatch;

			$counterAvail = &$twatch->state->get( TwatchState::COUNTERS_AVAIL, $this->id );

			if( $groupName !== null ) {
				if( !( $this instanceof TwatchGroupedCounter ) ) throw new TwatchUserError( 'not a grouped counter' );
				$groupGene = $twatch->config->get( TwatchConfig::ENTITIES, $this->groupEntityId )->gene->getPassiveGene( $dbDict, TwatchEntityPassiveGene::MODE_READ_ONLY, TwatchEntityPassiveGene::CONTEXT_IMPORT );
				$groupEntityVId = $groupGene->getStringEntityVId( $groupName, $website );
				if( !$groupEntityVId ) return;
			} else {
				if( $entityVName === null ) {
					$groupEntityVId = null;
				} else {
					$groupEntityVId = 0;
				}
			}
			if( $entityVName !== null ) {
				if( !( $this instanceof TwatchListCounter ) ) throw new TwatchUserError( 'not a list counter' );
				$gene = $twatch->config->get( TwatchConfig::ENTITIES, $this->entityId )->gene->getPassiveGene( $dbDict, TwatchEntityPassiveGene::MODE_READ_ONLY, TwatchEntityPassiveGene::CONTEXT_IMPORT );
				$entityVId = $gene->getStringEntityVId( $entityVName, $website );
				if( !$entityVId ) return;
			} else {
				$entityVId = null;
			}

			if( $periodTypeId === null ) {
				foreach( $counterAvail->timestamps as $pt => $tss ) {
					$this->deletePtData( $dbHistory, $counterAvail, $groupEntityVId, $entityVId, $pt, $timestamps );
				}
			} else {
				$this->deletePtData( $dbHistory, $counterAvail, $groupEntityVId, $entityVId, $periodTypeId, $timestamps );
			}

			if( $this instanceof TwatchListCounter ) {
				$dbHistory->recomputeAllTotals( $this->id );
			}

			if( $groupName === null && $entityVName === null ) {
				if( $periodTypeId !== null ) {
					self::updateDeletedAvail( $counterAvail, $periodTypeId, $timestamps );
				} else {
					foreach( $counterAvail->timestamps as $pt => $tss ) {
						self::updateDeletedAvail( $counterAvail, $pt, $timestamps );
					}
				}
				$twatch->state->setInternal( TwatchState::COUNTERS_AVAIL, $this->id );
			}
		}

		public static function deleteDataS( TwatchDbHistory $dbHistory, TwatchDbPassiveDict $dbDict, TwatchWebsite $website, $counterId, $groupName, $entityVName, $periodType, $timestamps ) {
			global $twatch;
			if( $counterId ) {
				$counter = $twatch->config->get( TwatchConfig::COUNTERS, $counterId );
				$counter->deleteData( $dbHistory, $dbDict, $website, $groupName, $entityVName, $periodType, $timestamps );
			} else {
				foreach( $twatch->config->getList( TwatchConfig::COUNTERS ) as $counter ) {
					$counter->deleteData( $dbHistory, $dbDict, $website, $groupName, $entityVName, $periodType, $timestamps );
				}
			}
		}

		public function usesEntity( $entityId ) {
			return false;
		}

		public function getEntityVIdReferences( $entityId, TwatchWebsite $website ) {
			return array();
		}

		public function uninstall() {
			global $twatch, $ardeUser;

			if( !$twatch->state->propertyExists( TwatchState::COUNTERS_AVAIL, $this->id ) ) {
				ArdeException::reportError( new TwatchWarning( 'counter availability for counter '.$this->id.' did not exist' ) );
			} else {
				$twatch->state->remove( TwatchState::COUNTERS_AVAIL, $this->id );
			}

			$this->removeTasks();

			$this->removeData();
			
			$ardeUser->user->data->clearId( TwatchUserData::VIEW_COUNTER, $this->id );
		}

		public function removeData() {
			global $twatch;
			$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
			foreach( $websites as $website ) {
				if( $website->parent ) continue;
				$dbCounters = new TwatchDbHistory( $twatch->db, $website->getSub() );
				$dbCounters->deleteCounterData( $this->id );
			}
		}

		public function removeTasks( $website = null ) {
			$taskM = new TwatchTaskManager();
			$tasks = $taskM->getAllTasks( 'TwatchDeleteCounter' );
			foreach( $tasks as $task ) {
				if( $task->cid == $this->id && ( $website === null || $website->getSub() == $task->sub ) ) {
					$taskM->deleteTask( $task->taskId, $task->inQueue );
				}
			}
			$tasks = $taskM->getAllTasks( 'TwatchTrimCounter' );
			foreach( $tasks as $task ) {
				if( $task->cid == $this->id && ( $website === null || $website->getSub() == $task->sub ) ) {
					$taskM->deleteTask( $task->taskId, $task->inQueue );
				}
			}
		}

		public static function installBase( $overwrite = false ) {
			global $twatch;
			$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
			foreach( $websites as $website ) {
				if( $website->parent ) continue;
				TwatchDbHistory::install( $twatch->db, $website->getSub(), $overwrite );
			}
		}

		public static function fullInstall( $overwrite = false ) {
			global $twatch;
			self::installBase( $overwrite );
			foreach( $twatch->config->getList( TwatchConfig::COUNTERS ) as $counter ) {
				$counter->install();
			}
		}

		public static function uninstallBase() {
			global $twatch;
			$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
			foreach( $websites as $website ) {
				if( $website->parent ) continue;
				TwatchDbHistory::uninstall( $twatch->db, $website->getSub() );
			}
		}

		public static function fullUninstall() {
			global $twatch;

			$counters = $twatch->config->getList( TwatchConfig::COUNTERS );
			foreach( $counters as $counter ) {
				if( !$twatch->state->propertyExists( TwatchState::COUNTERS_AVAIL, $counter->id ) ) {
					ArdeException::reportError( new TwatchWarning( 'counter availability for counter '.$counter->id.' did not exist' ) );
				} else {
					$twatch->state->remove( TwatchState::COUNTERS_AVAIL, $counter->id );
				}
			}

			self::uninstallBase();

			$taskM = new TwatchTaskManager();
			$taskM->deleteAllTasks( 'TwatchDeleteCounter' );
			$taskM->deleteAllTasks( 'TwatchTrimCounter' );
		}



		public static function startAll() {
			global $twatch;
			$counters = $twatch->config->getList( TwatchConfig::COUNTERS );
			foreach( $counters as $counter ) {
				if( !$twatch->state->get( TwatchState::COUNTERS_AVAIL, $counter->id )->isAvailable() ) {
					$counter->start();
				}
			}
		}

		public function installForWebsite( TwatchWebsite $website ) {}

		public static function installAllForWebsite( TwatchWebsite $website, $overwrite = false ) {
			global $twatch;

			TwatchDbHistory::install( $twatch->db, $website->getSub(), $overwrite );
			foreach( $twatch->config->getList( TwatchConfig::COUNTERS ) as $counter ) {
				$counter->installForWebsite( $website );
			}
		}

		public function uninstallForWebsite( TwatchWebsite $website ) {
			$this->removeTasks( $website );
		}

		public static function uninstallAllForWebsite( TwatchWebsite $website, TwatchTaskManager $taskManager ) {
			global $twatch;

			foreach( $twatch->config->getList( TwatchConfig::COUNTERS ) as $counter ) {
				$counter->uninstallForWebsite( $website );
			}

			TwatchDbHistory::uninstall( $twatch->db, $website->getSub() );
		}

		public function start() {
			global $twatch;
			$cavail = &$twatch->state->get( TwatchState::COUNTERS_AVAIL, $this->id );
			if( $cavail->isAvailable() ) throw new TwatchException( 'counter "'.$this->name.'" is already started' );
			foreach( $this->periodTypes as $periodType ) {
				$cavail->addTime( $twatch->now->ts, $periodType );
			}
			$twatch->state->setInternal( TwatchState::COUNTERS_AVAIL, $this->id );
			if( $twatch->state->propertyExists( TwatchState::OFF_COUNTER, $this->id ) ) {
				$twatch->state->remove( TwatchState::OFF_COUNTER, $this->id );
			}
		}

		public function stop() {
			global $twatch;
			$cavail = &$twatch->state->get( TwatchState::COUNTERS_AVAIL, $this->id );
			if( !$cavail->isAvailable() ) throw new TwatchException( 'counter "'.$this->name.'" is already stopped' );
			foreach( $this->periodTypes as $periodType ) {
				$cavail->addTime( $twatch->now->ts, $periodType );
			}
			$twatch->state->setInternal( TwatchState::COUNTERS_AVAIL, $this->id );
			$twatch->state->set( true, TwatchState::OFF_COUNTER, $this->id );
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			global $twatch;

			$avail = $twatch->state->get( TwatchState::COUNTERS_AVAIL, $this->id );
			$p->pl( '<'.$tagName.' id="'.$this->id.'" type="'.$this->getType().'" on="'.($avail->isAvailable()?'true':'false').'" name="'.htmlentities( $this->name ).'"'.$extraAttrib.'>', 1 );
			$expression = new TwatchExpression( $this->when );
			$expression->printXml( $p, 'when' );
			$p->relnl();
			$p->pl( '	<period_types>', 1 );
			foreach( $this->periodTypes as $periodType ) {
				$p->pl( '<type>'.$periodType.'</type>' );
			}
			$p->rel();
			$p->pl( '	</period_types>' );
			$p->pl( '	<delete>', 1 );
			foreach( $this->delete as $periodType => $age ) {
				$p->pl( '<period_type id="'.$periodType.'" age="'.$age.'" />' );
			}
			$p->rel();
			$p->pl(	'	</delete>', 0 );
			$this->completeXml( $p );
			$p->rel();
			$p->pn( '</'.$tagName.'>' );
		}

		abstract public function request( TwatchHistoryReader $historyR, $periodType, $periodCode, $group = 0, $rows = 0 );
		abstract public function getResult( TwatchHistoryReader $historyR, $periodType, $periodCode, $group = 0 );

		public function completeXml( ArdePrinter $p ) {}

		public function jsParams() {
			global $twatch;

			try {
				$avail = $twatch->state->get( TwatchState::COUNTERS_AVAIL, $this->id );
				$isAvailable = $avail->isAvailable();
			} catch( ArdeException $e ) {
				ArdeException::reportError( $e );
				$isAvailable = false;
			}

			$periodTypes = new ArdeAppender( ', ' );
			foreach( $this->periodTypes as $periodType ) {
				$periodTypes->append( $periodType );
			}
			$when = new TwatchExpression( $this->when, null );
			$delete = new ArdeAppender( ', ' );
			foreach( $this->delete as $periodType => $c ) {
				$delete->append( $periodType.': '.$c );
			}

			return $this->id.", '".ArdeJs::escape( $this->name )."', [ ".$periodTypes->s." ], ".$when->jsObject().", { ".$delete->s." }, ".ArdeJs::bool( $isAvailable );
		}

		abstract public function jsObject( $perm = 'null' );

		public function minimalJsObject() {
			return 'new Counter( '.$this->id.', '.$this->getType().", '".ArdeJs::escape( $this->name )."', null, null, false, [], false )";
		}

		public static function getDbAccessUnitInfo() {
			global $twatch;
			$res = new ArdeDbAccessUnitInfo( 'Counters', array() );
			$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
			foreach( $websites as $website ) {
				if( $website->parent ) continue;
				TwatchDbHistory::addDbAccessUnitInfo( $twatch->db, $res, $website->name, $website->getSub() );
			}
			return $res;
		}
	}

	class TwatchSingleCounter extends TwatchCounter {

		
		public function isViewable( ArdeUserOrGroup $user ) {
			return $user->hasPermission( TwatchUserData::VIEW_COUNTER, $this->id );
		}
		
		public function allowImport() { return true; }

		public static function makeSerialObject( ArdeSerialData $d ) {
			if( $d->version < 2 ) {
				if( array_search( TwatchPeriod::MONTH, $d->data[2] ) !== false ) {
					$d->data[2][] = TwatchPeriod::ALL;
				}
			}
			return new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3], $d->data[4] );
		}

		public function request( TwatchHistoryReader $historyR, $periodType, $periodCode, $group = 0, $rows = 0 ) {
			$historyR->get( $this->id, $periodType, $periodCode );
		}

		public function getResult( TwatchHistoryReader $historyR, $periodType, $periodCode, $group = 0 ) {
			return $historyR->getResult( $this->id, $periodType, $periodCode );
		}

		public function replace( TwatchDbHistory $dbHistory, $period, $count, $mergeRatio = 0 ) {
			if( $mergeRatio != 0 ) {
				$oldValues = $dbHistory->getCounts( $this->id, $period->type, $period->getCode() );
				if( isset( $oldValues[ '0-0' ] ) ) $count += (int)round( $oldValues[ '0-0' ] * $mergeRatio );
			}
			$dbHistory->replace( $this->id, $period->type, $period->getCode(), 0, 0, $count );
		}

		public function jsObject( $perm = 'null' ) {
			return "new SingleCounter( ".$this->jsParams().', '.$perm." )";
		}
	}

	class TwatchListCounter extends TwatchCounter {
		public $entityId;

		public $trim;

		public $activeTrim;

		public function isViewable( ArdeUserOrGroup $user ) {
			global $ardeUser;
			if( !$user->hasPermission( TwatchUserData::VIEW_COUNTER, $this->id ) ) return false;
			return $user->data->get( TwatchUserData::VIEW_ENTITY, $this->entityId ) != TwatchEntity::VIS_HIDDEN;
		}
		
		function __construct( $id, $name, $periodTypes, $when, $delete, $trim, $entityId, $activeTrim ) {
			parent::__construct( $id, $name, $periodTypes, $when, $delete );
			$this->trim = $trim;
			$this->activeTrim = $activeTrim;
			$this->entityId = $entityId;
		}

		public function makeActiveTrimTasks( $periodType, $website = null ) {
			global $twatch;
			if( !isset( $this->activeTrim[ $periodType ] ) ) throw new TwatchException( 'Trying to make active trim tasks for period type '.$periodType.' which is not active trimmed' );

			$taskm = new TwatchTaskManager();
			$tasks = array();

			if( $website !== null ) {
				$websites = array( $website );
			} else {
				$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
			}

			foreach( $websites as $website ) {
				if( $website->parent ) continue;
				$task = new TwatchActiveTrimCounter( $this->id, $periodType, $this->activeTrim[ $periodType ][1], $website->getSub() );
				$task->due = $twatch->now->dayOffset( $this->activeTrim[ $periodType ][0] )->ts;
				$tasks[] = $task;
			}
			$taskm->queueTasks( $tasks );
		}

		public function removeActiveTrimTasks( $periodType = null, $website = null ) {
			$taskM = new TwatchTaskManager();
			$tasks = $taskM->getAllTasks( 'TwatchActiveTrimCounter' );
			foreach( $tasks as $task ) {
				if( $task->counterId == $this->id && ( $periodType === null || $task->periodType == $periodType ) && ( $website === null || $task->sub == $website->getSub() ) ) {
					$taskM->deleteTask( $task->taskId, $task->inQueue );
				}
			}
		}

		public function removeActiveTrim( $periodType ) {
			global $twatch;
			$this->removeActiveTrimTasks( $periodType );
			foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $website ) {
				if( $website->parent ) continue;
				$dbCounters = new TwatchDbHistory( $twatch->db, $website->getSub() );
				$dbCounters->deleteBufferCounters( $this->id, $periodType );
			}

		}


		public function removeTasks( $website = null ) {
			parent::removeTasks( $website );
			$this->removeActiveTrimTasks( null, $website );
		}

		public function install() {
			parent::install();
			$this->removeActiveTrimTasks();
			foreach( $this->activeTrim as $periodType => $def ) {
				$this->makeActiveTrimTasks( $periodType );
			}
		}

		public function installForWebsite( TwatchWebsite $website ) {
			parent::installForWebsite( $website );
			foreach( $this->activeTrim as $periodType => $def ) {
				$this->makeActiveTrimTasks( $periodType, $website );
			}
		}

		public function allowImport() {
			global $twatch;
			$entity = $twatch->config->get( TwatchConfig::ENTITIES, $this->entityId );
			return $entity->gene->allowImport();
		}

		public function usesEntity( $entityId ) {
			if( $entityId == $this->entityId ) return "data field of counter '".$this->name."'";
			return false;
		}

		public function getEntityVIdReferences( $entityId, TwatchWebsite $website ) {
			$res = array();
			if( $entityId == $this->entityId ) {
				$res[] = TwatchDbHistory::getEntityReference( $this->id, $website->getSub() );
			}
			return $res;
		}

		public function replaceRow( TwatchDbHistory $dbHistory, $period, $groupId, $entityVId, $count, $mergeRatio = 0 ) {
			$dbHistory->replace( $this->id, $period->type, $period->getCode(), $groupId, $entityVId, $count, $mergeRatio );
			$this->recomputeTotal( $dbHistory, $period, $groupId );
		}

		public function replace( TwatchDbHistory $dbHistory, $period, $rows, $mergeRatio = 0, $groupId = null ) {

			if( $mergeRatio != 0 ) {
				$oldValues = $dbHistory->getCounts( $this->id, $period->type, $period->getCode(), $groupId );
			}

			$dbHistory->clearPeriod( $this->id, $period->type, $period->getCode(), $groupId );

			if( $this instanceof TwatchGroupedCounter && $groupId === null ) {
				foreach( $rows as $groupId => $groupRows ) {
					foreach( $groupRows as $entityVId => $count ) {
						if( $mergeRatio != 0 && isset( $oldValues[ $groupId.'-'.$entityVId ] ) ) {
							$count += (int)round( $oldValues[ $groupId.'-'.$entityVId ] * $mergeRatio );
							unset( $oldValues[ $groupId.'-'.$entityVId ] );
						}
						$dbHistory->replace( $this->id, $period->type, $period->getCode(), $groupId, $entityVId, $count );
					}

				}
			} else {
				foreach( $rows as $entityVId => $count ) {
					if( $mergeRatio != 0 && isset( $oldValues[ '0-'.$entityVId ] ) ) {
						$count += (int)round( $oldValues[ '0-'.$entityVId ] * $mergeRatio );
						unset( $oldValues[ '0-'.$entityVId ] );
					}
					$dbHistory->replace( $this->id, $period->type, $period->getCode(), $groupId === null ? 0 : $groupId, $entityVId, $count );
				}
			}

			if( $mergeRatio != 0 ) {
				foreach( $oldValues as $key => $count ) {
					$ids = explode( '-', $key );
					$groupId = ardeStrToU32( $ids[0] );
					$entityVId = ardeStrToU32( $ids[1] );
					$count = $count * $mergeRatio;
					$dbHistory->replace( $this->id, $period->type, $period->getCode(), $groupId, $entityVId, $count );
				}
			}
			$this->recomputeTotal( $dbHistory, $period, $groupId );
		}

		public function recomputeTotal( TwatchDbHistory $dbHistory, $period, $groupId = null ) {
			$dbHistory->recomputeTotal( $this->id, $period->type, $period->getCode(), $groupId );
		}

		public function update( TwatchCounter $counter ) {
			parent::update( $counter );

			foreach( $counter->activeTrim as $periodType => $def ) {
				if( !isset( $this->activeTrim[ $periodType ] ) ) {
					$counter->makeActiveTrimTasks( $periodType );
				}
			}

			foreach( $this->activeTrim as $periodType => $def ) {
				if( !isset( $counter->activeTrim[ $periodType ] ) ) {
					$this->removeActiveTrim( $periodType );
				}
			}

			$this->trim = $counter->trim;
			$this->activeTrim = $counter->activeTrim;
			$this->entityId = $counter->entityId;
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			parent::printXml( $p, $tagName, ' entity_id="'.$this->entityId.'"'.$extraAttrib );
		}

		public function completeXml( ArdePrinter $p ) {
			$p->pl( '<trim>', 1 );
			foreach( $this->trim as $periodType => $trim ) {
				$p->pl( '<period_type id="'.$periodType.'" age="'.$trim[0].'" top="'.$trim[1].'" />' );
			}
			$p->rel();
			$p->pl( '</trim>' );
			$p->pl( '<active_trim>', 1 );
			foreach( $this->activeTrim as $periodType => $trim ) {
				$p->pl( '<period_type id="'.$periodType.'" days="'.$trim[0].'" top="'.$trim[1].'" />' );
			}
			$p->rel();
			$p->pl( '</active_trim>' );
		}

		public function getSerialData() {
			$d = parent::getSerialData();
			$d->data[] = $this->trim;
			$d->data[] = $this->entityId;
			$d->data[] = $this->activeTrim;
			return $d;
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			if( $d->version < 2 ) {
				if( array_search( TwatchPeriod::MONTH, $d->data[2] ) !== false ) {
					$d->data[2][] = TwatchPeriod::ALL;
				}
				if( $d->data[0] == TwatchCounter::DIST_HOURLY || $d->data[0] == TwatchCounter::DIST_WEEKLY || $d->data[0] == TwatchCounter::BROWSERS || $d->data[0] == TwatchCounter::ROBOTS ) {
					$activeTrim = array();
				} else {
					$activeTrim = array( TwatchPeriod::ALL => array(30,20) );
				}
			} else {
				$activeTrim = $d->data[7];
			}
			return new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3], $d->data[4], $d->data[5], $d->data[6], $activeTrim );
		}

		public function isEquivalent( self $counter ) {
			if( !parent::isEquivalent( $counter ) ) return false;
			if( $counter->entityId != $this->entityId ) return false;
			if( !ardeEquivArrays( $counter->trim, $this->trim ) ) return false;
			if( !ardeEquivArrays( $counter->activeTrim, $this->activeTrim ) ) return false;
			foreach( $counter->trim as $periodType => $v ) {
				if( !ardeEquivOrderedArrays( $v, $this->trim[ $periodType ] ) ) return false;
			}
			return true;
		}


		function request( TwatchHistoryReader $historyR, $periodType, $periodCode, $group = 0, $rows = 0, $total = true, $entityVId = null ) {
			$historyR->get( $this->id, $periodType, $periodCode, $group, $this->entityId, $rows, $total, $entityVId );
		}

		function getResult( TwatchHistoryReader $historyR, $periodType, $periodCode, $group = 0, $entityVId = null ) {
			return $historyR->getResult( $this->id, $periodType, $periodCode, $group, $entityVId );
		}

		public function jsParams() {
			$trim = new ArdeAppender( ', ' );
			foreach( $this->trim as $periodType => $v ) {
				$trim->append( $periodType.': ['.$v[0].','.$v[1].']' );
			}
			$activeTrim = new ArdeAppender( ', ' );
			foreach( $this->activeTrim as $periodType => $v ) {
				$activeTrim->append( $periodType.': ['.$v[0].','.$v[1].']' );
			}
			return parent::jsParams().", { ".$trim->s." }, ".$this->entityId.", { ".$activeTrim->s.' }';
		}

		public function jsObject( $perm = 'null' ) {
			return "new ListCounter( ".$this->jsParams().', '.$perm." )";
		}

		protected function getJsGroupEntityId() {
			return 'null';
		}

		protected function getJsGroupAllowExplicitAdd() {
			return 'false';
		}

		public function minimalJsObject() {
			global $twatch, $ardeUser;
			$possibleSubs = new ArdeAppender( ', ' );
			foreach( $twatch->config->getList( TwatchConfig::COUNTERS ) as $counter ) {
				if( $counter->getType() == TwatchCounter::TYPE_GROUPED ) {
					if( $counter->groupEntityId == $this->entityId ) {
						if( $counter->isViewable( $ardeUser->user ) ) {
							$possibleSubs->append( $counter->id );
						}
					}
				}
			}
			if( $twatch->config->propertyExists( TwatchConfig::ENTITIES, $this->entityId ) ) {
				$set = $twatch->config->get( TwatchConfig::ENTITIES, $this->entityId )->gene->getSet();
				if( $set === false ) {
					$set = 'false';
				}
			} else {
				$set = 'false';
			}

			return 'new Counter( '.$this->id.', '.$this->getType().", '".ArdeJs::escape( $this->name )."', ".$this->entityId.', '.$this->getJsGroupEntityId().", ".$this->getJsGroupAllowExplicitAdd().", [ ".$possibleSubs->s." ], ".$set." )";
		}
	}

	class TwatchGroupedCounter extends TwatchListCounter {

		var $groupEntityId;

		public function isViewable( ArdeUserOrGroup $user ) {
			global $ardeUser;
			if( !$user->hasPermission( TwatchUserData::VIEW_COUNTER, $this->id ) ) return false;
			if( $user->data->get( TwatchUserData::VIEW_ENTITY, $this->entityId ) == TwatchEntity::VIS_HIDDEN ) return false;
			if( $user->data->get( TwatchUserData::VIEW_ENTITY, $this->groupEntityId ) == TwatchEntity::VIS_HIDDEN ) return false;
			return true;
		}
		
		public function allowImport() {
			global $twatch;
			$entity = $twatch->config->get( TwatchConfig::ENTITIES, $this->entityId );
			$groupEntity = $twatch->config->get( TwatchConfig::ENTITIES, $this->groupEntityId );
			return $entity->gene->allowImport() && $groupEntity->gene->allowImport();
		}

		function __construct( $id, $name, $periodTypes, $when, $delete, $trim, $entity, $groupEntityId, $activeTrim ) {
			parent::__construct( $id, $name, $periodTypes, $when, $delete, $trim, $entity, $activeTrim );
			$this->groupEntityId = $groupEntityId;
		}

		public function usesEntity( $entityId ) {
			if( ( $res = parent::usesEntity( $entityId ) ) !== false ) return $res;
			if( $entityId == $this->groupEntityId ) return "group field of counter '".$this->name."'";
			return false;
		}

		public function getEntityVIdReferences( $entityId, TwatchWebsite $website ) {
			$res = parent::getEntityVIdReferences( $entityId, $website );
			if( $entityId == $this->groupEntityId ) {
				$res[] = TwatchDbHistory::getGroupReference( $this->id, $website->getSub() );
			}
			return $res;
		}

		public function update( TwatchCounter $counter ) {
			parent::update( $counter );
			$this->groupEntityId = $counter->groupEntityId;
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			parent::printXml( $p, $tagName, ' group_entity_id="'.$this->groupEntityId.'"'.$extraAttrib );
		}

		public function getSerialData() {
			$d = parent::getSerialData();
			$d->data[] = $this->groupEntityId;
			return $d;
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			if( $d->version < 2 ) {
				if( array_search( TwatchPeriod::MONTH, $d->data[2] ) !== false ) {
					$d->data[2][] = TwatchPeriod::ALL;
				}

				if( $d->data[0] == TwatchCounter::DIST_HOURLY || $d->data[0] == TwatchCounter::DIST_WEEKLY || $d->data[0] == TwatchCounter::BROWSERS || $d->data[0] == TwatchCounter::ROBOTS ) {
					$activeTrim = array();
				} else {
					$activeTrim = array( TwatchPeriod::ALL => array( 30, 20 ) );
				}
				$groupEntityId = $d->data[7];
			} else {
				$activeTrim = $d->data[7];
				$groupEntityId = $d->data[8];
			}
			return new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3], $d->data[4], $d->data[5], $d->data[6], $groupEntityId, $activeTrim );
		}


		public function isEquivalent( self $counter ) {
			if( !parent::isEquivalent( $counter ) ) return false;
			if( $counter->groupEntityId != $this->groupEntityId ) return false;
			return true;
		}

		protected function getJsGroupEntityId() {
			return $this->groupEntityId;
		}

		protected function getJsGroupAllowExplicitAdd() {
			global $twatch;
			if( !$twatch->config->propertyExists( TwatchConfig::ENTITIES, $this->groupEntityId ) ) return 'false';
			return ArdeJs::bool( $twatch->config->get( TwatchConfig::ENTITIES, $this->groupEntityId )->gene->allowExplicitAdd() );
		}

		public function jsParams() {
			return parent::jsParams().', '.$this->groupEntityId;
		}

		public function jsObject( $perm = 'null' ) {
			return "new GroupedCounter( ".$this->jsParams().", ".$perm." )";
		}
	}

	class TwatchDeleteCounter extends ArdeDisTask {
		public $cid;
		public $dtt;
		public $dt;
		public $sub;

		function __construct( $cid = 0, $dtt = 0, $dt = 0, $sub = 0 ) {
			$this->cid = $cid;
			$this->dtt = $dtt;
			$this->dt = $dt;
			$this->sub = $sub;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->cid, $this->dtt, $this->dt, $this->sub ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3] );
		}

		function run() {
			global $twatch;
			if( !$twatch->state->listExists( TwatchState::COUNTERS_AVAIL ) ) {
				$twatch->state = new TwatchState( $twatch->db );
				$twatch->state->addDefaults( TwatchState::$defaultProperties );
				$twatch->state->addDefaults( TwatchState::$extraDefaults );
				$twatch->state->applyAllChanges();
			}
			$avail = &$twatch->state->get( TwatchState::COUNTERS_AVAIL, $this->cid );
			$p = TwatchPeriod::fromCode( $this->dtt, $this->dt );
			$avail->setStart( $p->getStartTs(1), $this->dtt );
			$twatch->state->setInternal( TwatchState::COUNTERS_AVAIL, $this->cid );

			$dbCounters = new TwatchDbHistory( $twatch->db, $this->sub );
			$dbCounters->deleteOldData( $this->cid, $this->dtt, $this->dt );
		}
	}

	class TwatchActiveTrimCounter extends ArdeDisTask {
		public $counterId;
		public $periodType;
		public $rows;
		public $sub;

		function __construct( $counterId, $periodType, $rows, $sub ) {
			$this->counterId = $counterId;
			$this->periodType = $periodType;
			$this->rows = $rows;
			$this->sub = $sub;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->counterId, $this->periodType, $this->rows, $this->sub ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3] );
		}

		function run() {
			global $twatch;
			$dbCounters = new TwatchDbHistory( $twatch->db, $this->sub );
			$dbCounters->activeTrimData( $this->counterId, $this->periodType, $this->rows, $this->sub );

			$taskm = new TwatchTaskManager();
			$counter = $twatch->config->get( TwatchConfig::COUNTERS, $this->counterId );
			$task = new self( $this->counterId, $this->periodType, $counter->activeTrim[$this->periodType][1], $this->sub );
			$task->due = $twatch->now->dayOffset( $counter->activeTrim[$this->periodType][0] )->ts;
			$taskm->queueTasks( array( $task ) );
		}
	}

	class TwatchTrimCounter extends ArdeDisTask {
		public $cid;
		public $dt;
		public $dtt;
		public $count;
		public $sub;

		function __construct( $cid = 0, $dtt = 0, $dt = 0, $count = 0, $sub = 0 ) {
			$this->cid = $cid;
			$this->dt = $dt;
			$this->dtt = $dtt;
			$this->count = $count;
			$this->sub = $sub;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->cid, $this->dtt, $this->dt, $this->count, $this->sub ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3], $d->data[4] );
		}

		function run() {
			global $twatch;
			$dbCounters = new TwatchDbHistory( $twatch->db, $this->sub );
			$dbCounters->trimData( $this->cid, $this->dtt, $this->dt, $this->count );
		}
	}

	class TwatchWebsite implements ArdeSerializable {

		private $id;
		private $sub;

		public $name;
		public $handle;
		public $parent;
		public $domains;
		public $cookieDomain;
		public $cookieFolder;
		
 

		function __construct( $id, $name, $handle, $parent = 0, $domains = array(), $cookieDomain = '', $cookieFolder = '' ) {
			$this->id = $id;
			$this->name = $name;
			$this->handle = $handle;
			$this->parent = $parent;
			$this->sub = 'w'.$id;
			$this->domains = $domains;
			$this->cookieDomain = $cookieDomain;
			$this->cookieFolder = $cookieFolder;

		}

		public function setId( $id ) {
			$this->id = $id;
			$this->sub = 'w'.$id;
		}

		public function getId() {
			return $this->id;
		}

		public function getSub() {
			return $this->sub;
		}

		public function isEquivalent( self $website ) {
			if( $this->id != $website->id ) return false;
			if( $this->name != $website->name ) return false;
			if( $this->handle != $website->handle ) return false;
			if( $this->parent != $website->parent ) return false;
			if( !ardeEquivOrderedArrays( $this->domains, $website->domains ) ) return false;
			if( $this->cookieDomain != $website->cookieDomain ) return false;
			if( $this->cookieFolder != $website->cookieFolder ) return false;
			return true;
		}

		public function getSerialData() {
			return new ArdeSerialData( 2, array( $this->id, $this->name, $this->handle, $this->parent, $this->domains, $this->cookieDomain, $this->cookieFolder ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			if( $d->version < 2 ) {
				$cookieDomain = '';
				$cookieFolder = '';
			} else {
				$cookieDomain = $d->data[5];
				$cookieFolder = $d->data[6];
			}
			return new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3], $d->data[4], $cookieDomain, $cookieFolder );
		}

		function install( TwatchTaskManager $taskManager, $overwrite = false ) {
			global $twatch;
			if( $this->parent ) return;
			TwatchCounter::installAllForWebsite( $this, $overwrite );
			TwatchDbSession::install( $twatch->db, $this->sub, $overwrite );
			$pathAnalyzer = $twatch->config->get( TwatchConfig::PATH_ANALYZER );
			$pathAnalyzer->installForWebsite( $this, $taskManager, $overwrite );
		}


		function uninstall( TwatchTaskManager $taskManager ) {
			global $twatch;
			if( $this->parent ) return;
			TwatchCounter::uninstallAllForWebsite( $this, $taskManager );
			TwatchDbSession::uninstall( $twatch->db, $this->sub );
			$pathAnalyzer = $twatch->config->get( TwatchConfig::PATH_ANALYZER );
			$pathAnalyzer->uninstallForWebsite( $this, $taskManager );
		}



		function js_object( $perm ) {
			$doms = new ArdeAppender( ',' );
			foreach( $this->domains as $d ) {
				$doms->append( "'".ArdeJs::escape( $d )."'" );
			}
			return 'new Website('.$this->id.",'".ArdeJs::escape( $this->name )."','".ArdeJs::escape( $this->handle )."',".(int)$this->parent.",new Array(".$doms->s."), '".$this->cookieDomain."', '".$this->cookieFolder."', ".$perm." )";
		}

		function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' id="'.$this->id.'" handle="'.$this->handle.'" parent_id="'.$this->parent.'"'.$extraAttrib.'>' );
			$p->pl( '	<name>'.ardeXmlEntities( $this->name ).'</name>' );
			$p->pl( '	<cookie_domain>'.ardeXmlEntities( $this->cookieDomain ).'</cookie_domain>' );
			$p->pl( '	<cookie_folder>'.ardeXmlEntities( $this->cookieFolder ).'</cookie_folder>' );
			$p->pl( '	<domains>', 1 );
			foreach( $this->domains as $d ) {
				$p->pl( '<domain>'.ardeXmlEntities( $d ).'</domain>' );
			}
			$p->rel();
			$p->pl( '	</domains>' );
			$p->pn( '</'.$tagName.'>' );
		}

		public static function &getWebsiteFromId( $id ) {
			global $twatch;
			if( is_int( $id ) ) {
				return $twatch->config->get( TwatchConfig::WEBSITES, $id );
			} else {
				$websites = &$twatch->config->getList( TwatchConfig::WEBSITES );
				foreach( $websites as &$website ) {
					if( $website->handle == $id ) return $website;
				}
			}
			throw new TwatchUserError( 'no website found with id '.$id );
		}

	}

	class TwatchLatest implements ArdeSerializable {
		public $delete;

		function __construct( $delete ) {
			$this->delete = $delete;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->delete ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0] );
		}

		public function isEquivalent( self $latest ) {
			return $latest->delete == $this->delete;
		}

		public static function getDbAccessUnitInfo() {
			global $twatch;

			$unit = new ArdeDbAccessUnitInfo( 'Latest Visitors' );
			$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
			foreach( $websites as $website ) {
				if( $website->parent ) continue;
				TwatchDbSession::addDbAccessUnitInfo( $twatch->db, $unit, $website->name, $website->getSub() );
			}
			return $unit;
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' delete="'.$this->delete.'"'.$extraAttrib.'>' );
			$p->pl( '</'.$tagName.'>' );
		}

		public function jsObject() {
			return 'new AdminLatest( '.$this->delete.' )';
		}

		public static function fromParams( $a ) {
			$delete = ArdeParam::int( $a, 'd', 2 );
			return new self( $delete );
		}

		public static function entityIsUsed( $entityId ) {
			global $twatch;

			if( $entityId == TwatchEntity::IP ) return 'Latest Visitors';
			if( $entityId == TwatchEntity::PAGE ) return 'Latest Visitors';
			if( $entityId == TwatchEntity::PCOOKIE ) return 'Latest Visitors';
			if( $entityId == TwatchEntity::SCOOKIE ) return 'Latest Visitors';
			$dataWriters = $twatch->config->getList( TwatchConfig::RDATA_WRITERS );
			foreach( $dataWriters as $dataWriter ) {
				if( $dataWriter->entityId == $entityId ) return 'Latest Visitors Data Writers';
			}

			return false;
		}

		public static function getEntityVIdRefs( $entityId ) {
			global $twatch;

			$res = array();
			foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $website  ) {
				if( $website->parent ) continue;
				if( $entityId == TwatchEntity::IP ) $res[] = TwatchDbSession::getIpRef( $website->getSub() );
				elseif( $entityId == TwatchEntity::PAGE ) $res[] = TwatchDbSession::getPageRef( $website->getSub() );
				elseif( $entityId == TwatchEntity::PCOOKIE ) $res[] = TwatchDbSession::getPCookieRef( $website->getSub() );
				elseif( $entityId == TwatchEntity::SCOOKIE ) $res[] = TwatchDbSession::getSCookieRef( $website->getSub() );
				$res[] = TwatchDbSession::getDataRef( $entityId, $website->getSub() );
			}
			return $res;
		}

		public function cleanup() {
			global $twatch;
			$taskM = new TwatchTaskManager();
			$taskM->deleteAllTasks( 'TwatchDeleteLatest' );

			$websites = $twatch->config->getList( TwatchConfig::WEBSITES );

			foreach( $websites as $website ) {
				if( $website->parent ) continue;
				$dbSessions = new TwatchDbSession( $twatch->db, 0, $website->getSub() );
				$dbSessions->deleteSessions( null, $twatch->now->dayOffset( -$this->delete + 1 )->getDayStart() );
			}

		}


		public static function uninstall() {

			$taskM = new TwatchTaskManager();
			$taskM->deleteAllTasks( 'TwatchDeleteLatest' );

			self::uninstallBase();
		}

		public static function uninstallBase() {
			global $twatch;
			$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
			foreach( $websites as $website ) {
				if( $website->parent ) continue;
				TwatchDbSession::uninstall( $twatch->db, $website->getSub() );
			}
		}

		public static function install( $overwrite = false ) {
			global $twatch;

			$websites = $twatch->config->getList( TwatchConfig::WEBSITES );
			foreach( $websites as $website ) {
				if( $website->parent ) continue;
				TwatchDbSession::install( $twatch->db, $website->getSub(), $overwrite );
			}
		}
	}

	class TwatchDeleteLatest extends ArdeDisTask {

		var $sub;
		var $startTs;
		var $endTs;

		function __construct( $sub = 0, $startTs = 0, $endTs = 0 ) {
			$this->sub = $sub;
			$this->startTs = $startTs;
			$this->endTs = $endTs;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->sub, $this->startTs, $this->endTs ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2] );
		}

		function run() {
			global $twatch;

			$dbSession = new TwatchDbSession( $twatch->db, 0, $this->sub );
			$dbSession->deleteSessions( $this->startTs, $this->endTs );
		}
	}

	class TwatchExpression extends ArdeExpression {
		const ENTITY = 101;
		const ENTITY_VALUE = 102;
		const VALUE_CHANGED = 103;
		const REQUEST_INFO = 104;
		const VISITOR_TYPE_IS = 105;


		public static $idStrings = array(
			 self::ENTITY => 'Entity'
			,self::ENTITY_VALUE => 'Entity Value'
			,self::VALUE_CHANGED => 'Entity Changed'
			,self::REQUEST_INFO => 'Request Info'
			,self::VISITOR_TYPE_IS => 'Visitor Type Is'
		);

		private $request;

		function __construct( $a, TwatchRequest $request = null ) {
			$this->request = $request;
			parent::__construct( $a );
		}


		public static function printJsInputElements( ArdePrinter $p ) {
			global $twatch;
			$reqInfos = new ArdeAppender( ', ' );
			foreach( TwatchRequest::$infoStrings as $id => $name ) {
				$reqInfos->append( $id.": '".ArdeJs::escape( $name )."'" );
			}
			$p->pl( "TwatchExpressionInput.elements.push( new ArdeExpressionInputSFVar( 'Request Info', ".self::REQUEST_INFO.", { ".$reqInfos->s." } ) );" );
			$entities = new ArdeAppender( ', ' );
			$entityNames = new ArdeAppender( ', ' );
			$entityChangeNames = new ArdeAppender( ', ' );
			foreach( $twatch->config->getList( TwatchConfig::ENTITIES ) as $entity ) {
				$entities->append( $entity->id.": ".$entity->minimalJsObject() );
				$entityNames->append( $entity->id.": '".ArdeJs::escape( $entity->name )."'" );
				$entityChangeNames->append( $entity->id.": '".ArdeJs::escape( $entity->name.' Changed' )."'" );
			}
			$p->pl( "TwatchExpressionInput.elements.push( new ArdeExpressionInputSFVar( 'Entity', ".self::ENTITY.", { ".$entityNames->s." } ) );" );
			$p->pl( "TwatchExpressionInput.elements.push( new TwatchExpressionInputEVVar( 'Entity Value', ".self::ENTITY_VALUE.", { ".$entities->s." } ) );" );
			$entities = new ArdeAppender( ', ' );
			foreach( $twatch->config->getList( TwatchConfig::ENTITIES ) as $entity ) {
				$entities->append( $entity->id.": '".ArdeJs::escape( $entity->name )." changed'" );
			}
			$p->pl( "TwatchExpressionInput.elements.push( new ArdeExpressionInputSFVar( 'Entity Changed', ".self::VALUE_CHANGED.", { ".$entityChangeNames->s." } ) );" );
			$vts = new ArdeAppender( ', ' );
			foreach( $twatch->config->getList( TwatchConfig::VISITOR_TYPES ) as $vt ) {
				$vts->append( $vt->id.": 'Visitor is ".ArdeJs::escape( $vt->name )."'" );
			}
			$p->pl( "TwatchExpressionInput.elements.push( new ArdeExpressionInputSFVar( 'Visitor Type is', ".self::VISITOR_TYPE_IS.", { ".$vts->s." } ) );" );

		}

		protected function getVar( $i, &$value ) {
			if( $this->a[ $i ] == self::VALUE_CHANGED ) {
				++$i;
				if( !isset( $this->a[$i] ) ) throw new ArdeException( 'integer expected at offset '.$i );
				$value = $this->request->valueChanged( $this->a[ $i ] );
			} elseif( $this->a[$i] == self::REQUEST_INFO ) {
				++$i;
				if( !isset( $this->a[$i] ) ) throw new ArdeException( 'integer expected at offset '.$i );
				if( !isset( $this->request->req_info[ $this->a[$i] ] ) ) $value = 0;
				else $value = $this->request->req_info[ $this->a[$i] ];
			} elseif( $this->a[$i] == self::VISITOR_TYPE_IS ) {
				++$i;
				if( !isset( $this->a[$i] ) ) throw new ArdeException( 'integer expected at offset '.$i );
				$value =  $this->request->det_vt == $this->a[$i];
			} elseif( $this->a[ $i ] == self::ENTITY ) {
				++$i;
				if( !isset( $this->a[$i] ) ) throw new ArdeException( 'integer expected at offset '.$i );
				if( !isset( $this->request->entityVIds[ $this->a[$i] ] ) ) $value = 0;
				else $value = $this->request->entityVIds[ $this->a[ $i ] ];
			} elseif( $this->a[ $i ] == self::ENTITY_VALUE ) {
				++$i;
				if( !isset( $this->a[$i] ) ) throw new ArdeException( 'integer expected at offset '.$i );
				++$i;
				if( !isset( $this->a[$i] ) ) throw new ArdeException( 'integer expected at offset '.$i );
				$value = $this->a[$i];
			}
			return $i;
		}


		protected function isValidVar( $i ) {
			global $twatch;

			if( $this->a[$i] == self::VALUE_CHANGED ) {
				++$i;
				if( !isset( $this->a[$i] ) ) return -1;
				if( !$twatch->config->propertyExists( TwatchConfig::ENTITIES, $this->a[$i] ) ) return -1;
				return $i;
			} elseif( $this->a[$i] == self::REQUEST_INFO ) {
				++$i;
				if( !isset( $this->a[$i] ) ) return -1;
				if( !isset( TwatchRequest::$infoStrings[ $this->a[$i] ] ) ) return -1;
				return $i;
			} elseif( $this->a[$i] == self::VISITOR_TYPE_IS ) {
				++$i;
				if( !isset( $this->a[$i] ) ) return -1;
				if( !isset( TwatchVisitorType::$idStrings[ $this->a[$i] ] ) ) return -1;
				return $i;
			} elseif( $this->a[ $i ] == self::ENTITY ) {

				++$i;
				if( !isset( $this->a[$i] ) ) return -1;
				$res = $twatch->config->propertyExists( TwatchConfig::ENTITIES, $this->a[$i] );
				if( !$res ) return -1;
				return $i;
			} elseif( $this->a[ $i ] == self::ENTITY_VALUE ) {

				++$i;
				if( !isset( $this->a[$i] ) ) return -1;
				if( !$twatch->config->propertyExists( TwatchConfig::ENTITIES, $this->a[$i] ) ) return -1;
				++$i;
				if( !isset( $this->a[$i] ) ) return -1;
				if( !( is_int( $this->a[$i] ) || is_float( $this->a[$i] ) ) || $this->a[$i] < 0 ) return -1;
				return $i;
			}
			return -1;
		}

		private static $noEvVars = array( self::INT => 1, self::VALUE_CHANGED => 1, self::REQUEST_INFO => 1, self::VISITOR_TYPE_IS => 1, self::ENTITY => 1 );

		public function getEntityVReferences() {
			$o = array();
			for( $i = 0; $i < count( $this->a ); ++$i ) {
				if( is_int( $this->a[$i] ) ) {
					if( isset( self::$noEvVars[ $this->a[$i] ] ) ) {
						$i += self::$noEvVars[ $this->a[$i] ];
					} elseif( $this->a[$i] == self::ENTITY_VALUE ) {
						++$i;
						if( isset( $this->a[$i] ) ) {
							$entityId = $this->a[$i];
							++$i;
							if( isset( $this->a[$i] ) ) {
								$entityVId = $this->a[$i];
								$o[] = array( $entityId, $entityVId );
							}
						}
					}
				}
			}
			return $o;
		}

		public function install() {
			$evRefs = $this->getEntityVReferences();
			foreach( $evRefs as $evRef ) {
				TwatchEntityVRefCounter::add( $evRef[0], $evRef[1] );
			}
		}

		public function uninstall() {
			$evRefs = $this->getEntityVReferences();
			foreach( $evRefs as $evRef ) {
				TwatchEntityVRefCounter::remove( $evRef[0], $evRef[1] );
			}
		}

		protected function varElement( $i, &$varElement ) {
			global $twatch;
			$name = '!NS';
			$as = array( $this->a[$i] );
			if( $this->a[$i] == self::VALUE_CHANGED ) {
				++$i;
				if( isset( $this->a[$i] ) ) {
					$as[] = $this->a[ $i ];
					if( $twatch->config->propertyExists( TwatchConfig::ENTITIES, $this->a[ $i ] ) ) {
						$name = $twatch->config->get( TwatchConfig::ENTITIES, $this->a[ $i ] )->name.' changed';
					}
				}
			} elseif( $this->a[$i] == self::REQUEST_INFO ) {
				++$i;
				if( isset( $this->a[$i] ) ) {
					$as[] = $this->a[ $i ];
					if( isset( TwatchRequest::$infoStrings[ $this->a[ $i ] ] ) ) {
						$name = TwatchRequest::$infoStrings[ $this->a[ $i ] ];
					}
				}
			} elseif( $this->a[$i] == self::VISITOR_TYPE_IS ) {
				++$i;
				if( isset( $this->a[$i] ) ) {
					$as[] = $this->a[$i];
					if( isset( TwatchVisitorType::$idStrings[ $this->a[$i] ] ) ) {
						$name = 'Visitor is '.TwatchVisitorType::$idStrings[ $this->a[$i] ];
					}
				}
			} elseif( $this->a[$i] == self::ENTITY ) {
				++$i;
				if( isset( $this->a[$i] ) ) {
					$as[] = $this->a[$i];
					if( $twatch->config->propertyExists( TwatchConfig::ENTITIES, $this->a[$i] ) ) {
						$name = $twatch->config->get( TwatchConfig::ENTITIES, $this->a[$i] )->name;
					}
				}
			} elseif( $this->a[$i] == self::ENTITY_VALUE ) {
				++$i;
				if( isset( $this->a[$i] ) ) {
					$as[] = $this->a[$i];
					if( $twatch->config->propertyExists( TwatchConfig::ENTITIES, $this->a[$i] ) ) {
						++$i;
						if( isset( $this->a[$i] ) ) {
							$as[] = $this->a[$i];
							$entityV = TwatchEntityVGen::makeFinalized( $as[1], $as[2] );
							$name = $entityV->getString( EntityV::STRING_EXPRESSION );
						}
					}
				}
			}
			$varElement = new ArdeExpressionElement( $name, $as );
			return $i;
		}

		public static function getJsArray() {
			global $twatch;

			$ids = new ArdeAppender( ', ' );
			return '{ '.$ids->s.' }';
		}

		public static function fromParam( $param ) {
			return new self( self::arrayFromParam( $param ) ) ;
		}

		protected function isValidValue( $id, $subId ) {
			global $twatch;

			if( $id == self::VALUE_CHANGED ) {
				return $twatch->config->propertyExists( TwatchConfig::ENTITIES, $subId );
			} elseif( $id == self::REQUEST_INFO ) {
				return isset( TwatchRequest::$infoStrings[ $subId ] );
			} elseif( $id == self::VISITOR_TYPE ) {
				return $twatch->config->propertyExists( TwatchConfig::VISITOR_TYPES, $subId );
			} else {
				return false;
			}
		}
	}



	class TwatchCookieKeys implements ArdeSerializable {
		private $keys = array();

		public function __construct( $keys ) {
			$this->keys = $keys;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, $this->keys );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data );
		}

		public function _upgradeKeys() {
			global $twatch;
			$salt = crc32( $twatch->settings[ 'salt' ] );
			$tm = crc32( (string)ArdeTime::getMicrotime() );
			for( $i = 0; $i < 4; ++$i ) {
				$this->keys[ $i ] = ( $tm ^ mt_rand( 0, 0x7fffffff ) ^ $salt ^ $this->keys[ 3 - $i ] ) * ( mt_rand( 0, 1 ) == 1 ? -1 : 1 );
			}
		}

		public static function upgradeKeys() {
			global $twatch;
			$cookieKeys = &$twatch->config->get( TwatchConfig::COOKIE_KEYS );
			$cookieKeys->_upgradeKeys();
			$twatch->config->setInternal( TwatchConfig::COOKIE_KEYS );
		}


		public function encrypt( $nums, $scookie ) {
			$o = new ArdeAppender( '_' );
			foreach( $nums as $websiteId => $rid ) {
				$o->append( $websiteId );
				$o->append( $this->encryptNum( $rid, $scookie, $websiteId ) );
			}
			return $o->s;
		}

		public function decrypt( $text, $scookie ) {
			if( strpos( $text, '_' ) === false ) {
				return $this->decryptNum( $text, $scookie, 0 );
			}
			$o = array();
			$parts = explode( '_', $text );
			for( $i = 0; $i < count( $parts ); $i += 2 ) {
				if( isset( $parts[ $i+1 ] ) ) {
					$websiteId = (int)$parts[$i];
					if( $websiteId <= 0 ) continue;
					$rid = $this->decryptNum( $parts[ $i+1 ], $scookie, $websiteId );
					if( $rid === false ) continue;
					$o[ $websiteId ] = $rid;
				}
			}
			return $o;
		}

		public function encryptNum( $num, $scookie, $websiteId ) {
			if( $scookie ) {
				$a = self::_encrypt( $num, $this->keys[0], $this->keys[1], $this->keys[2], $this->keys[3], $websiteId );
			} else {
				$a = self::_encrypt( $num, $this->keys[3], $this->keys[2], $this->keys[1], $this->keys[0], $websiteId );
			}
			return $a[0].' '.$a[1];
		}

		public function decryptNum( $text, $scookie, $websiteId ) {
			if( strlen( $text ) > 23 ) return false;
			$texts = explode( ' ', $text );
			if( count( $texts ) != 2 ) return false;
			$a = array( (int)$texts[0], (int)$texts[1] );
			if( $scookie ) {
				return self::_decrypt( $a, $this->keys[0], $this->keys[1], $this->keys[2], $this->keys[3], $websiteId );
			} else {
				return self::_decrypt( $a, $this->keys[3], $this->keys[2], $this->keys[1], $this->keys[0], $websiteId );
			}
		}

		private static function _encrypt( $num, $secretKey, $hashKey1, $hashKey2, $hashKey3, $websiteId ) {

			$num = $num ^ $websiteId;

			$hash1 = (int)($num * $hashKey1);
			$hash2 = (int)($secretKey * $hashKey2);

			$num2 = $num ^ $hash2;
			$secretKey2 = $secretKey ^ $hash1;

			$hash3 = (int)($secretKey2 * $hashKey3);
			$num3 = $num2 ^ $hash3;

			return array( $num3, $secretKey2 );
		}

		private static function _decrypt( $a, $secretKey, $hashKey1, $hashKey2, $hashKey3, $websiteId ) {

			$hash3 = (int)($a[1] * $hashKey3);
			$num2 = $a[0] ^ $hash3;

			$hash2 = (int)($secretKey * $hashKey2);
			$num = $num2 ^ $hash2;

			$hash1 = (int)($num * $hashKey1);
			if( $secretKey != ( $a[1] ^ $hash1 ) ) return false;

			$num = $num ^ $websiteId;

			return $num;
		}


		public function oldEncrypt( $num ) {
			$a = self::_oldEncrypt( $num, $this->keys[0], $this->keys[1], $this->keys[2], $this->keys[3] );
			return $a[0].' '.$a[1].' '.$a[2].' '.$a[3];
		}

		public function olDDecrypt( $text ) {
			if( strlen( $text ) > 44 ) return false;
			$texts = explode( ' ', $text );
			if( count ( $texts ) != 4 ) return false;
			$a = array();
			for( $i = 0; $i < 4; ++$i ) {
				$a[ $i ] = (int)$texts[ $i ];
			}

			return self::_oldDecrypt( $a, $this->keys[0], $this->keys[1], $this->keys[2], $this->keys[3] );
		}

		private static function _oldEncrypt( $num, $secretKey, $maskKey, $padKey1, $padKey2 ) {
			$a = array();
			$num = ardeRotate32Right( $num, $maskKey & 0x1F );
			$a[0] = ( $num & $maskKey ) | ( $secretKey & ~$maskKey );
			$a[2] = ( $num & ~$maskKey ) | ( $secretKey & $maskKey );

			$a[1] = ( $a[0] & 0xffff ) * ( ( $a[0] & 0xffff0000 ) >> 16 );

			$a[3] = ( $a[2] & 0xffff ) * ( ( $a[2] & 0xffff0000 ) >> 16 );

			$a[0] ^= $padKey1;
			$a[1] ^= $padKey1;
			$a[2] ^= $padKey2;
			$a[3] ^= $padKey2;

			return $a;
		}

		private static function _oldDecrypt( $a, $secretKey, $maskKey, $padKey1, $padKey2 ) {
			$a[0] ^= $padKey1;
			$a[1] ^= $padKey1;
			$a[2] ^= $padKey2;
			$a[3] ^= $padKey2;


			if( $secretKey != ( ( $a[0] & ~$maskKey ) | ( $a[2] & $maskKey ) ) ) return false;
			if( $a[1] != ( $a[0] & 0xffff ) * ( ( $a[0] & 0xffff0000 ) >> 16 ) ) return false;
			if( $a[3] != ( $a[2] & 0xffff ) * ( ( $a[2] & 0xffff0000 ) >> 16 ) ) return false;

			$num = ( $a[0] & $maskKey ) | ( $a[2] & ~$maskKey );

			$num = ardeRotate32Left( $num, $maskKey & 0x1F );

			return $num;
		}

	}

	class TwatchMakeDailyTasks extends ArdeDisTask {
		var $ts;

		function __construct( $ts = 0 ) {
			$this->ts = $ts;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->ts ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0] );
		}

		function run() {
			global $twatch;

			$websites = &$twatch->config->getList( TwatchConfig::WEBSITES );
			$latest = &$twatch->config->get( TwatchConfig::LATEST );

			$subs = array();
			$time = new TwatchTime( $this->ts );
			$task_sch = new ArdeDisTaskScheduler( $time->ts, $time->dayOffset(1)->getDayStart() - 1 );

			foreach( $websites as $k => $v ) {
				if( !$websites[$k]->parent ) $subs[] = $websites[$k]->getSub();
			}

			if( $twatch->state->get( TwatchState::PATH_ANALYZER_INSTALLED ) == true ) {
				$pathAnalyzer = &$twatch->config->get( TwatchConfig::PATH_ANALYZER );
				$path_tasks = $pathAnalyzer->getSamplingTasks( $time, $subs );
				$task_sch->addTasks( $path_tasks );
			}

			$cou_tasks = TwatchCounter::getCleanupTasks( $time, $subs, TwatchPeriod::DAY );
			$task_sch->addTasks( $cou_tasks );

			$taskm = new TwatchTaskManager();
			$queued_tasks = $taskm->getQueuedTasksDueToday( $time );
			$task_sch->addTasks( $queued_tasks );

			$latest_tasks = self::analyzeDeleteLatest( $latest, $subs, $time );
			$task_sch->addTasks( $latest_tasks );

			$task_sch->scheduleTasks();

			$next_day_task = new self( $time->dayOffset(1)->getDayStart() );
			$next_day_task->due = $time->dayOffset(1)->getDayStart();
			$task_sch->tasks[] = $next_day_task;


			$taskm->addTasks( $task_sch->tasks );

		}



		const LATEST_DELETE_PTASK = 50;
		const MAX_LATEST_DELETE_DIVS = 10;
		private static function analyzeDeleteLatest( $latest, $subs, $dt ) {
			global $twatch;

			$startTs = $dt->dayOffset( -$latest->delete )->getDayStart();
			$endTs = $dt->dayOffset( -$latest->delete + 1 )->getDayStart();
			$unions = new ArdeAppender( ' union all ' );
			foreach( $subs as $sub ) {
				$unions->append( '('.$twatch->db->make_query_sub( $sub, "SELECT '".$sub."',count(*) FROM",'s', 'WHERE first>='.$startTs.' AND first<'.$endTs ).')' );
			}
			$res = $twatch->db->query( $unions->s );

			$tasks = array();
			while( $r = mysql_fetch_row( $res ) ) {
				if( !$r[1] ) continue;
				$divs = (int)ceil( ((int)$r[1]) / self::LATEST_DELETE_PTASK );
				if( $divs > self::MAX_LATEST_DELETE_DIVS ) {
					$divs = self::MAX_LATEST_DELETE_DIVS;
				}
				$section = (int)floor( ( $endTs - $startTs ) / $divs );
				for( $i = 0; $i < $divs - 1; $i++ ) {
					$tasks[] = new TwatchDeleteLatest( $r[0], $i * $section + $startTs, ($i+1) * $section + $startTs );
				}
				$tasks[] = new TwatchDeleteLatest( $r[0], $endTs - $section, $endTs );
			}
			return $tasks;
		}
	}

	class TwatchAdminCookie implements ArdeSerializable {
		public $secret;

		public function __construct( $secret ) {
			$this->secret = $secret;
		}

		public function isAdminCookie( $str ) {
			return $str == $this->secret;
		}

		public function _upgradeSecret() {
			global $twatch;
			$this->secret = md5( ArdeTime::getMicrotime().mt_rand( 0, 0x7fffffff ).$twatch->settings[ 'salt' ].$this->secret );
		}

		public static function upgradeSecret() {
			global $twatch;
			$twatch->config->get( TwatchConfig::ADMIN_COOKIE )->_upgradeSecret();
			$twatch->config->setInternal( TwatchConfig::ADMIN_COOKIE );
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->secret ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0] );
		}
	}

	class TwatchMakeMonthlyTasks extends ArdeDisTask {
		var $ts;

		function __construct( $ts = 0 ) {
			$this->ts = $ts;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->ts ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0] );
		}

		function run() {
			global $twatch;

			$websites = &$twatch->config->getList( TwatchConfig::WEBSITES );
			$subs = array();
			$time = new TwatchTime( $this->ts );
			$task_sch = new ArdeDisTaskScheduler( $time->dayOffset(1)->getDayStart() + 600, $time->dayOffset(4)->getDayStart() );
			foreach( $websites as $website ) {
				if( !$website->parent ) $subs[] = $website->getSub();
			}
			$cou_tasks = TwatchCounter::getCleanupTasks( $time, $subs, TwatchPeriod::MONTH );
			$task_sch->addTasks( $cou_tasks );
			$task_sch->scheduleTasks();

			$next_m_task = new self( $time->monthOffset(1)->getMonthStart() );
			$next_m_task->due = $time->monthOffset(1)->getMonthStart();
			$tasks_sch->tasks[] = $next_m_task;

			$taskm = new TwatchTaskManager();
			$taskm->queueTasks( $task_sch->tasks );
		}
	}

	class TwatchEntityVRefCounter {
		public static function add( $entityId, $entityVId ) {
			global $twatch;
			TwatchDbEntityVRefCounter::add( $twatch->db, $entityId, $entityVId );
		}

		public static function remove( $entityId, $entityVId ) {
			global $twatch;
			TwatchDbEntityVRefCounter::remove( $twatch->db, $entityId, $entityVId );
		}

		public static function install( $overwrite ) {
			global $twatch;
			TwatchDbEntityVRefCounter::install( $twatch->db, $overwrite );
		}

		public static function uninstall() {
			global $twatch;
			TwatchDbEntityVRefCounter::uninstall( $twatch->db );
		}

		public static function getEntityVIdRefs( $entityId ) {
			global $twatch;
			return array( TwatchDbEntityVRefCounter::getEntityVReference( $entityId ) );
		}
	}
	$twatch->applyOverrides( array() );
?>