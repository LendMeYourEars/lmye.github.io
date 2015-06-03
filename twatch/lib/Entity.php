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

	class TwatchEntity implements ArdeSerializable {

		const MAX_PREDEFINED = 1000;

		const REF = 1;
		const REF_GROUP = 2;
		const AGENT_STR = 4;
		const USER_AGENT = 5;
		const PAGE = 6;
		const IP = 7;
		const PIP = 9;
		const SCOOKIE = 12;
		const PCOOKIE = 13;
		const TIME = 17;
		const PROC_REF = 18;
		const SE_KEYWORD = 19;
		const HOUR = 22;
		const WEEKDAY = 23;
		const REF_TYPE = 24;
		const PATH = 31;
		const RIP = 32;
		const FIP = 33;
		const ADMIN_COOKIE = 34;

		const VIS_VISIBLE = 1;
		const VIS_SHOW_AS_HIDDEN = 2;
		const VIS_HIDDEN = 3;
		
		public static $idString = array(
			 self::REF => 'Referrer'
			,self::REF_GROUP => 'Referrer Group'
			,self::AGENT_STR => 'User Agent String'
			,self::USER_AGENT => 'User Agent'
			,self::PAGE => 'Page'
			,self::IP => 'IP'
			,self::PIP => 'Proxy IP'
			,self::SCOOKIE => 'SCookie'
			,self::PCOOKIE => 'PCookie'
			,self::TIME => 'Time'
			,self::PROC_REF => 'Processed Referrer'
			,self::SE_KEYWORD => 'Search Engine - Keyword'
			,self::HOUR => 'Hour'
			,self::WEEKDAY => 'Weekday'
			,self::REF_TYPE => 'Referrer Type'
			,self::PATH => 'Path'
			,self::RIP => 'Request IP'
			,self::FIP => 'Forwarded IP'
			,self::ADMIN_COOKIE => 'Admin Cookie'
		);

		public static function getIdString( $id ) { return isset( self::$idString[ $id ] ) ? self::$idString[ $id ] : '[nf]'; }

		public $id;
		public $name;
		public $visitorTitle;
		public $gene;
		public $unstoppable = false;
		public $hasImage = false;

		public function __construct( $name, $visitorTitle, TwatchEntityGene $gene ) {
			$this->id = $gene->entityId;
			$this->name = $name;
			$this->visitorTitle = $visitorTitle;
			$this->gene = $gene;
		}

		public function setId( $id ) {
			$this->id = $id;
			$this->gene->entityId = $id;
		}

		public static function fromParams( $a, $new ) {
			global $twatch;
			if( !$new ) {
				$id = ArdeParam::int( $a, 'i' );
				if( !$twatch->config->propertyExists( TwatchConfig::ENTITIES, $id ) ) throw new TwatchException( 'unknown entity '.$id );
				$unstoppable = $twatch->config->get( TwatchConfig::ENTITIES, $id )->unstoppable;
				$hasImage = $twatch->config->get( TwatchConfig::ENTITIES, $id )->hasImage;
			} else {
				$id = $twatch->config->getNewSubId( TwatchConfig::ENTITIES );
				$unstoppable = false;
				$hasImage = false;
			}
			$name = ArdeParam::str( $a, 'n' );
			$visitorTitle = ArdeParam::str( $a, 'vt' );
			$gene = TwatchEntityGene::fromParams( $a, 'g_', $new, $id );
			if( !$new ) {
				$origEntity = $twatch->config->get( TwatchConfig::ENTITIES, $id );
			}
			$o = new self( $name, $visitorTitle, $gene );
			$o->unstoppable = $unstoppable;
			$o->hasImage = $hasImage;
			return $o;
		}

		public function isEquivalent( self $entity ) {
			if( $entity->id != $this->id ) return false;
			if( $entity->name != $this->name ) return false;
			if( $entity->visitorTitle != $this->visitorTitle ) return false;

			if( !$this->gene->isEquivalent( $entity->gene ) ) return false;
			return true;
		}

		public function entityIsUsed( $entityId ) {
			global $twatch;

			$entities = $twatch->config->getList( TwatchConfig::ENTITIES );

			foreach( $entities as $entity ) {
				if( ( $res = $entity->gene->usesEntity( $entityId ) ) !== false ) return $res;
			}

			return false;
		}

		public function getValueIdReferences() {
			$res = array();
			foreach( TwatchCounter::getAllEntityVIdReferences( $this->id ) as $ref ) $res[] = $ref;
			foreach( TwatchPathAnalyzer::getAllEntityVIdRefs( $this->id ) as $ref ) $res[] = $ref;
			foreach( TwatchLatest::getEntityVIdRefs( $this->id ) as $ref ) $res[] = $ref;
			foreach( TwatchVisitorType::getEntityVIdRefs( $this->id ) as $ref ) $res[] = $ref;
			foreach( TwatchEntityVRefCounter::getEntityVIdRefs( $this->id ) as $ref ) $res[] = $ref;
			return $res;
		}

		public function getDictEntryIdRefs( $dictId ) {
			return $this->gene->getDictEntryIdReferences( $dictId, $this );
		}

		public static function getAllDictEntryIdRefs( $dictId ) {
			global $twatch;

			$res = array();
			foreach( $twatch->config->getList( TwatchConfig::ENTITIES ) as $entity ) {
				foreach( $entity->getDictEntryIdRefs( $dictId ) as $ref ) $res[] = $ref;
			}
			return $res;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->id, $this->name, $this->visitorTitle, $this->gene, $this->unstoppable, $this->hasImage ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			if( $d->data[0] != $d->data[3]->entityId ) throw new TwatchException( 'entities id doesn\'t match the one stored in it\s gene' );
			$o = new self( $d->data[1], $d->data[2], $d->data[3] );
			$o->unstoppable = $d->data[4];
			$o->hasImage = $d->data[5];
			return $o;
		}

		function getValueIds( $offset, $count, $beginWith = null, $websiteId = null ) {
			if( $this->gene->valueClassName !== null ) {
				$func = $this->gene->valueClassName.'::getIds';
				return call_user_func( array( $this->gene->valueClassName, 'getIds' ), $this->id, $offset, $count, $beginWith, $websiteId );
			} else {
				throw new TwatchException( "selected entity doesn't have a value class" );
			}

		}

		function js_entity_info() {
			return "new EntityInfo('".$this->name."')";
		}

		public function itemJsObject() {
			return "new EntityItem(' ".$this->id.", ".$this->name." )";
		}

		public function jsObject( $vis = 'null' ) {
			global $twatch;
			$on = !$twatch->state->propertyExists( TwatchState::OFF_ENTITY, $this->id );
			return "new Entity( ".$this->id.", '".ArdeJs::escape( $this->name )."', '".ArdeJs::escape( $this->visitorTitle )."', ".$this->gene->jsObject().", new EntityState(".ArdeJs::bool( $on )."), null, ".$vis." )";
		}

		public function minimalJsObject() {
			global $twatch;
			return "new Entity( '".ArdeJs::escape( $twatch->locale->text( $this->name ) )."', ".ArdeJs::bool( $this->gene->allowExplicitAdd() )." )";
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '', $vis = null ) {
			global $twatch;

			$on = !$twatch->state->propertyExists( TwatchState::OFF_ENTITY, $this->id );
			$p->pl( '<'.$tagName.' id="'.$this->id.'"'.$extraAttrib.' >' );
			$p->pl( '	<name>'.ardeXmlEntities( $this->name ).'</name>' );
			$p->pl( '	<state on="'.($on?'true':'false').'" />' );
			$p->pl( '	<visitor_title>'.ardeXmlEntities( $this->visitorTitle ).'</visitor_title>', 0 );
			$this->gene->printXml( $p, 'gene' );
			if( $vis !== null ) {
				$p->pl( $vis );
			}
			$p->relnl();
			$p->pn( '</'.$tagName.'>' );
		}


		
		public function isViewable( ArdeUserOrGroup $user ) {
			if( $user->data->get( TwatchUserData::VIEW_ENTITY, $this->id ) == TwatchEntity::VIS_HIDDEN ) return false;
			return true;
		}

		public function isUsed() {
			if( ( $res = TwatchCounter::entityIsUsed( $this->id ) ) !== false ) return $res;
			if( ( $res = TwatchLatest::entityIsUsed( $this->id ) ) !== false ) return $res;
			if( ( $res = TwatchPathAnalyzer::entityIsUsed( $this->id ) ) !== false ) return $res;
			return TwatchEntity::entityIsUsed( $this->id );
		}

		public function start() {
			global $twatch;
			if( !$twatch->state->propertyExists( TwatchState::OFF_ENTITY, $this->id ) ) ArdeException::report( new TwatchWarning( 'data '.$entities[ $id ]->name.' is already active' ) );
			$twatch->state->remove( TwatchState::OFF_ENTITY, $this->id );
		}

		public function stop() {
			global $twatch;
			if( $this->unstoppable ) throw new TwatchUserError( "sorry data '".$this->name."' is unstoppable!" );
			if( $twatch->state->propertyExists( TwatchState::OFF_ENTITY, $this->id ) ) ArdeException::report( new TwatchWarning( 'data '.$this->name.' is already inactive' ) );
			$twatch->state->set( true, TwatchState::OFF_ENTITY, $this->id );
		}

		public static function fullInstall( $overwrite ) {
			global $twatch;
			foreach( $twatch->config->getList( TwatchConfig::ENTITIES ) as $entity ) {
				$entity->gene->install();
			}
		}

	}


	abstract class TwatchEntityGene implements ArdeSerializable {

		public $valueClassName = null;
		public $entityId = null;

		public $valueId = null;



		public function __construct( $entityId ) {
			$this->entityId = $entityId;
		}

		public static function isUserCreatable() { return false; }

		public function allowExplicitAdd() { return false; }

		public function allowImport() { return false; }

		public function getSet() { return false; }

		public function getPrecedents() { return array(); }

		public function getDictPutPrecedents() { return array(); }

		public function usesEntity( $entityId ) { return false; }

		public function getDictEntryIdReferences( $dictId, $entity ) {
			return array();
		}


		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return null;
		}

		public function getDictsUsed() { return array(); }

		public function managesDicts() { return array(); }

		public function init( &$dicts ) {}

		public function install() {
			global $twatch;

			foreach( $this->getDictsUsed() as $dictId ) {
				if( !$twatch->state->propertyExists( TwatchState::DICT_STATES, $dictId ) ) {
					$dict = $twatch->config->get( TwatchConfig::DICTS, $dictId );
					$dict->install();
				}

				$dictState = &$twatch->state->get( TwatchState::DICT_STATES, $dictId );
				$dictState->addEntityUsage( $this->entityId );
				$twatch->state->setInternal( TwatchState::DICT_STATES, $dictId );
			}
		}

		public function uninstall() {
			global $twatch;
			foreach( $this->getDictsUsed() as $dictId ) {
				if( !$twatch->config->propertyExists( TwatchConfig::DICTS, $dictId ) ) {
					ArdeException::reportError( new TwatchException( 'dict '.$dictId.' not found' ) );
					continue;
				} else {
					$dict = $twatch->config->get( TwatchConfig::DICTS, $dictId );
				}
				if( $twatch->state->propertyExists( TwatchState::DICT_STATES, $dictId ) ) {
					$dictState = &$twatch->state->get( TwatchState::DICT_STATES, $dictId );
					$dictState->removeEntityUsage( $this->entityId );
					if( $dictState->isUsed() ) {
						$twatch->state->setInternal( TwatchState::DICT_STATES, $dictId );
					} else {
						$dict->uninstall();
					}
				} else {
					ArdeException::reportError( new ArdeException( 'There is no indication that dictionary '.$dictId.' is installed' ) );
					$dict->uninstall();
				}
			}

		}

		public function ArdeVerboseExtend( ArdeVerbose $v ) {}

		public function attempt1( TwatchRequest $request ) {}

		public function attempt2( TwatchRequest $request ) {}

		public function attempt3( TwatchRequest $request ) {}

		public function attempt4( TwatchRequest $request ) {}


		public function getJsParams() {
			global $twatch;

			$dicts = new ArdeAppender( ', ' );
			foreach( $this->managesDicts() as $dictId ) {
				$dicts->append( $twatch->config->get( TwatchConfig::DICTS, $dictId )->jsObject() );
			}
			return "'".get_class( $this )."', [ ".$dicts->s." ]";
		}

		public function getJsClassName() {
			return 'EntityGene';
		}

		public function jsObject() {
			return "new ".$this->getJsClassName()."( ".$this->getJsParams()." )";
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			global $twatch;

			$p->pl( '<'.$tagName.' class_name="'.get_class( $this ).'" js_class_name="'.$this->getJsClassName().'" '.$extraAttrib.'>', 1 );
			$this->extendPrintXml( $p );
			$p->rel();
			$p->pl( '	<dicts>', 1 );
			foreach( $this->managesDicts() as $dictId ) {
				$twatch->config->get( TwatchConfig::DICTS, $dictId )->printXml( $p, 'dict' );
				$p->nl();
			}
			$p->rel();
			$p->pl( '	</dicts>' );
			$p->pl( '</'.$tagName.'>' );
		}

		public function extendPrintXml() {}

		public function isEquivalent( self $gene ) {
			if( get_class( $gene ) !== get_class( $this ) ) return false;
			if( $gene->entityId != $this->entityId ) return false;
			return true;

		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->entityId ) );
		}


		protected static function _fromParams( $a, $prefix, $new, $className, $entityId ) {
			return new $className( $entityId );
		}

		public static function fromParams( $a, $prefix, $new, $entityId ) {
			global $twatch;

			$className = ArdeParam::str( $a, $prefix.'cn' );
			if( !is_subclass_of( $className, __CLASS__ ) ) throw new TwatchException( 'invalid Entity Gene class '.$className );
			if( !$new ) {
				$entity = $twatch->config->get( TwatchConfig::ENTITIES, $entityId );
				if( get_class( $entity->gene ) != $className ) throw new TwatchException( "you can't change the class of this entity's gene from '".get_class( $entity->gene )."' to '".$className."'" );
			} else {
				if( !call_user_func( array( $className, 'isUserCreatable' ) ) ) throw new TwatchException( 'entity gene class '.$className.' can\'t be created by user' );
			}
			return call_user_func( array( $className, '_fromParams' ), $a, $prefix, $new, $className, $entityId );
		}
	}

	abstract class TwatchEntGeneInput extends TwatchEntityGene {

		protected $inputKey;
		public $str = null;

		public function __construct( $entityId, $inputKey ) {
			parent::__construct( $entityId );
			$this->inputKey = $inputKey;
		}

		protected function makeString( TwatchRequest $request ) {
			if( !isset( $request->data[ $this->inputKey ] ) ) return false;
			
			if( function_exists( 'mb_convert_encoding' ) ) {
				$this->str = mb_convert_encoding( $request->data[ $this->inputKey ], "UTF-8", "UTF-8" );
			} elseif( function_exists( 'iconv' ) ) {
				try {
					$this->str = @iconv( "UTF-8", "UTF-8//IGNORE", $request->data[ $this->inputKey ] );
				} catch( Exception $e ) {}
			} else {
				$this->str = $request->data[ $this->inputKey ];
			}
		}

		public function getJsClassName() {
			return "EntityGeneInput";
		}

		public function jsObject() {
			return "new ".$this->getJsClassName()."( ".$this->getJsParams().", '".ArdeJs::escape( $this->inputKey )."' )";
		}

		protected static function _fromParams( $a, $prefix, $new, $className, $entityId ) {
			$inputKey = ArdeParam::str( $a, $prefix.'ik' );
			if( !$inputKey ) throw new TwatchUserError( 'data key cannot be empty' );
			return new $className( $entityId, $inputKey );
		}

		public function isEquivalent( self $gene ) {
			if( !parent::isEquivalent( $gene ) ) return false;
			if( $this->inputKey != $gene->inputKey ) return false;
			return true;
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			parent::printXml( $p, $tagName, ' input_key="'.htmlentities( $this->inputKey ).'" '.$extraAttrib );
		}


		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->entityId, $this->inputKey ) );
		}


	}

	class TwatchEntGeneNull extends TwatchEntGeneInput {

		const EXISTS = 1;

		public $valueClassName = 'EntityVNull';

		public $value;

		public function __construct( $entityId, $inputKey, $value ) {
			parent::__construct( $entityId, $inputKey );
			$this->value = $value;
		}

		public function getJsClassName() {
			return "EntityGeneNull";
		}

		public function jsObject() {
			return "new ".$this->getJsClassName()."( ".$this->getJsParams().", '".ArdeJs::escape( $this->inputKey )."', '".ArdeJs::escape( $this->value )."' )";
		}

		protected static function _fromParams( $a, $prefix, $new, $className, $entityId ) {
			$inputKey = ArdeParam::str( $a, $prefix.'ik' );
			$value = ArdeParam::str( $a, $prefix.'v' );
			return new $className( $entityId, $inputKey, $value );
		}

		public function isEquivalent( self $gene ) {
			if( !parent::isEquivalent( $gene ) ) return false;
			if( $this->value != $gene->value ) return false;
			return true;
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			parent::printXml( $p, $tagName, ' value="'.ardeXmlEntities( $this->value ).'" '.$extraAttrib );
		}


		public function attempt1( TwatchRequest  $request ) {
			if( !isset( $request->data[ $this->inputKey ] ) ) return false;
			$this->valueId = self::EXISTS;
			return true;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->entityId, $this->inputKey, $this->value ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2] );
		}

		public static function isUserCreatable() { return true; }
	}

	abstract class TwatchEntGeneDict extends TwatchEntGeneInput {

		public $dictId = null;

		public function getDictsUsed() {
			return array( $this->dictId );
		}

		public function getDictEntryIdReferences( $dictId, $entity ) {
			$res = parent::getDictEntryIdReferences( $dictId, $entity );
			if( $dictId == $this->dictId ) {
				foreach( $entity->getValueIdReferences() as $ref ) $res[] = $ref;
			}
			return $res;
		}

		public function attempt1( TwatchRequest $request ) {
			if( $this->makeString( $request ) === false ) return false;
			$request->dict->get( $this->entityId, $this->dictId, $this->str );
		}

		public function attempt2( TwatchRequest $request ) {
			if( isset( $request->dict->results[ $this->entityId ] ) ) {
				$this->valueId = $request->dict->results[ $this->entityId ]->id;
				return true;
			} else {
				$request->dict->put( $this->entityId, $this->dictId, $this->str, null, null, null );
			}
		}

		public function attempt4( TwatchRequest $request ) {
			if( !isset( $request->dict->insertIds[ $this->entityId ] ) ) throw new ArdeException( 'insert id not received for entity '.$this->entityId.' ('.TwatchEntity::$idString[ $this->entityId ].')' );
			$this->valueId = $request->dict->insertIds[ $this->entityId ];
			return true;
		}


		public function managesDicts() {
			return array( $this->dictId );
		}


		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntDictPasvGene( $this->dictId, $dict, $mode, $context );
		}
	}


	class TwatchEntGeneGeneric extends TwatchEntGeneDict {

		public $valueClassName = 'EntityVGeneric';

		public static function isUserCreatable() { return true; }

		public function init( &$dicts ) {
			global $twatch;
			$this->dictId = $twatch->config->getNewSubId( TwatchConfig::DICTS );
			if( !isset( $dicts[0] ) ) throw new TwatchException( 'one dict should be sent' );
			$dicts[0]->id = $this->dictId;
			$twatch->config->addToBottom( $dicts[0], TwatchConfig::DICTS, $this->dictId );
		}

		protected static function _fromParams( $a, $prefix, $new, $className, $entityId ) {
			global $twatch;

			$o = parent::_fromParams( $a, $prefix, $new, $className, $entityId );
			if( $new ) {
				return $o;
			} else {
				$o->dictId = $twatch->config->get( TwatchConfig::ENTITIES, $o->entityId )->gene->dictId;
				return $o;
			}
		}

		public function allowImport() { return true; }


		public function uninstall() {
			global $twatch;
			parent::uninstall();
			$twatch->config->remove( TwatchConfig::DICTS, $this->dictId );
		}



		public function getSerialData() {
			$d = parent::getSerialData();
			$d->data[] = $this->dictId;
			return $d;
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			$o = new self( $d->data[0], $d->data[1] );
			$o->dictId = $d->data[2];
			return $o;
		}
	}



	class TwatchEntGeneRef extends TwatchEntGeneDict {

		public $dictId = TwatchDict::REF;

		public $valueClassName = 'EntityVRef';

		public function allowExplicitAdd() { return true; }

		public function allowImport() { return true; }

		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntRefPasvGene( $dict, $mode, $context );
		}

		public function getDictPutPrecedents() {
			return array( TwatchEntity::REF_GROUP, TwatchEntity::SE_KEYWORD );
		}
		public function makeString( TwatchRequest $request ) {
			global $twatch;
			if( parent::makeString( $request ) === false ) return false;

			if( $this->str == '' ) {
				$this->str = null;
				return false;
			}

			if( preg_match( '/^http\:\/\/([^\/]+)(\/|$)/', $this->str, $matches ) ) {
				$domain = strtolower( $matches[1] );
				if( $request->website->parent ) $pwid = $request->website->parent;
				else $pwid = $request->websiteId;
				$ws = $twatch->config->getList( TwatchConfig::WEBSITES );
				foreach( $ws as $k => $v ) {
					if( $k == $pwid || $ws[$k]->parent == $pwid ) {
						foreach( $ws[$k]->domains as $d ) {
							if( $domain == strtolower( $d ) ) {
								$this->str = null;
								return false;
							}
						}
					}
				}
			}


		}






		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1] );
		}

	}

	class TwatchEntGeneRefGroup extends TwatchEntityGene {

		const MAX_SEARCHE = 50000;
		const NONE = 1;

		public $domain = null;
		public $keyword = null;

		public $updatingCache = false;

		public $valueClassName = 'EntityVRefGroup';

		public function getPrecedents() {
			return array( TwatchEntity::REF );
		}

		public function usesEntity( $entityId ) {
			if( $entityId == TwatchEntity::REF ) return 'Referrer Group entity';
			return false;
		}

		public function getDictEntryIdReferences( $dictId, $entity ) {
			$res = parent::getDictEntryIdReferences( $dictId, $entity );
			if( $dictId == TwatchDict::REF_DOMAIN ) {
				foreach( $entity->getValueIdReferences() as $ref ) $res[] = $ref;
				$res[] = TwatchDbDict::getCacheRef( TwatchDict::REF, 1 );
			}

			return $res;
		}

		public function allowImport() { return true; }

		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntRefGroupPasvGene( $dict, $mode, $context );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0] );
		}

		public function getDictsUsed() {
			return array( TwatchDict::REF_DOMAIN );
		}


		public function managesDicts() {
			return array( TwatchDict::REF_DOMAIN );
		}

		public static function getDomain( $s ) {
			if( preg_match( "/^[^\/]+\:\/\/(\d+\.\d+\.\d+\.\d+)(?:\:|\/|$)/", $s, $matches ) ) {

				return $matches[1];

			} elseif(preg_match("/^[^\/]+\:\/\/([^\/]+)(?:\/|$)/",$s,$matches)) {

				$b=explode('.',$matches[1]);
				if($b[0]=='www') unset($b[0]);
				$a=array();
				foreach($b as $bs) $a[]=$bs;
				$ac=count($a);
				if($ac>=3&&strlen($a[$ac-1])==2&&strlen($a[$ac-2])<=3) $c=3;
				else $c=2;
				if(count($a)>$c) unset($a[0]);
				$domain='';
				$c=0;
				foreach($a as $as) {
					if($c) $domain.='.';
					$domain.=$as;
					$c++;
				}
				return $matches[1];

			}
			return null;
		}

		public function attempt2( TwatchRequest $request ) {
			global $twatch;

			if( !$request->geneExists( TwatchEntity::REF ) ) return false;

			if( isset( $request->dict->results[ TwatchEntity::REF ] ) ) {
				if( $request->dict->results[ TwatchEntity::REF ]->timestamp < $twatch->state->get( TwatchState::SEARCHE_CACHE_VALID ) ) {
					$this->updatingCache = true;
				} elseif( $request->dict->results[ TwatchEntity::REF ]->cache1 != 0 ) {
					$this->valueId = $request->dict->results[ TwatchEntity::REF ]->cache1;

					return true;
				}

			}

			$refGene = $request->getGene( TwatchEntity::REF );

			if( $refGene->str === null ) return false;
			$s = $refGene->str;

			$res = TwatchSearchEngine::match( $s );
			
			if( $res !== false ) {
				if( $res instanceof TwatchSEKeyword ) {
					$webAreaId = $res->searchEngineId;
					$this->keyword = $res->keyword;
				} else {
					$webAreaId = $res;
					$this->keyword = null;
				}

				$this->genAndCacheValue( $webAreaId, $request );
				return true;
			}



			$this->domain = self::getDomain( $s );

			if( $this->domain != null ) {

				$request->dict->get( $this->entityId, TwatchDict::REF_DOMAIN, $this->domain );

			} else {
				$this->genAndCacheValue( self::NONE, $request );
				return true;
			}


		}

		public function attempt3( TwatchRequest $request ) {
			if( isset( $request->dict->results[ $this->entityId ] ) ) {
				$this->genAndCacheValue( $request->dict->results[ $this->entityId ]->id, $request );
				return true;
			} else {
				$request->dict->put( $this->entityId, TwatchDict::REF_DOMAIN, $this->domain, null, null, null );
				if( !$this->updatingCache ) {
					$request->dict->putInsertIdIntoCache( TwatchEntity::REF, 1, $this->entityId );
				}
			}
		}

		public function attempt4( TwatchRequest $request ) {
			if( !isset( $request->dict->insertIds[ $this->entityId ] ) ) throw new ArdeException( 'insert id not received for domain' );
			$this->valueId = $request->dict->insertIds[ $this->entityId ];
			if( $this->updatingCache ) {
				$request->dict->updateCache( TwatchDict::REF, $request->dict->results[ TwatchEntity::REF ]->id, 1, $this->valueId );
			}
			return true;
		}

		private function genAndCacheValue( $value, $request ) {
			$this->valueId = $value;
			if( $this->updatingCache ) {
				$request->dict->updateCache( TwatchDict::REF, $request->dict->results[ TwatchEntity::REF ]->id, 1, $value );
			} else {
				$request->dict->putCache( TwatchEntity::REF, 1, $value );
			}
		}
	}


	class TwatchEntGeneSeKeyword extends TwatchEntityGene {

		private $str = null;

		public $updatingCache = false;

		public $valueClassName = 'EntityVSeKeyword';

		public function getDictsUsed() {
			return array( TwatchDict::SE_KEYWORD );
		}

		public function getDictEntryIdReferences( $dictId, $entity ) {
			$res = parent::getDictEntryIdReferences( $dictId, $entity );
			if( $dictId == TwatchDict::SE_KEYWORD ) {
				foreach( $entity->getValueIdReferences() as $ref ) $res[] = $ref;
				$res[] = TwatchDbDict::getCacheRef( TwatchDict::REF, 2 );
			}
			return $res;
		}

		public function getPrecedents() {
			return array( TwatchEntity::REF_GROUP, TwatchEntity::REF );
		}

		public function getDictPutPrecedents() {
			return array( TwatchEntity::REF_GROUP );
		}

		public function usesEntity( $entityId ) {
			if( $entityId == TwatchEntity::REF ) return 'Search Engine - Keyword Entity';
			if( $entityId == TwatchEntity::REF_GROUP ) return 'Search Engine - Keyword Entity';
			return false;
		}

		public function managesDicts() {
			return array( TwatchDict::SE_KEYWORD );
		}

		public function attempt2( TwatchRequest $request ) {

			if( !$request->geneExists( TwatchEntity::REF ) ) return false;

			if( !isset( $request->doneGenes[ TwatchEntity::REF_GROUP ] ) ) {
				if( $request->geneExists( TwatchEntity::REF_GROUP ) ) {
					if( $request->getGene( TwatchEntity::REF_GROUP )->updatingCache ) {
						$request->dict->updateCache( TwatchDict::REF, $request->dict->results[ TwatchEntity::REF ]->id, 2, 0 );
					}
				}
				return false;
			}
			$refGGen = $request->doneGenes[ TwatchEntity::REF_GROUP ];

			if( $refGGen->updatingCache ) {

				$this->updatingCache = true;

			} elseif( isset( $request->dict->results[ TwatchEntity::REF ] ) && $request->dict->results[ TwatchEntity::REF ]->cache2 != 0 ) {


				$this->valueId = $request->dict->results[ TwatchEntity::REF ]->cache2;
				return true;

			}

			if( $refGGen->valueId > TwatchEntGeneRefGroup::MAX_SEARCHE || $refGGen->keyword === null ) {
				if( $this->updatingCache ) $request->dict->updateCache( TwatchDict::REF, $request->dict->results[ TwatchEntity::REF ]->id, 2, 0 );
				return false;
			}
			$this->str = $refGGen->valueId.'-'.$refGGen->keyword;
			$request->dict->get( $this->entityId, TwatchDict::SE_KEYWORD, $this->str );


		}

		public function attempt3( TwatchRequest $request ) {
			if( isset( $request->dict->results[ $this->entityId ] ) ) {
				$this->valueId = $request->dict->results[ $this->entityId ]->id;
				if( $this->updatingCache ) {
					$request->dict->updateCache( TwatchDict::REF, $request->dict->results[ TwatchEntity::REF ]->id, 2, $this->valueId );
				} else {
					$request->dict->putCache( TwatchEntity::REF, 2, $this->valueId );
				}
				return true;
			}

			$request->dict->put( $this->entityId, TwatchDict::SE_KEYWORD, $this->str, array( $request->doneGenes[ TwatchEntity::REF_GROUP ]->valueId ), null, null );
			if( !$this->updatingCache ) {
				$request->dict->putInsertIdIntoCache( TwatchEntity::REF, 2, $this->entityId );
			}

		}

		public function attempt4( TwatchRequest $request ) {
			if( !isset( $request->dict->insertIds[ $this->entityId ] ) ) throw new ArdeException( 'insert id not received for search engine-keyword' );
			$this->valueId = $request->dict->insertIds[ $this->entityId ];
			if( $this->updatingCache ) {
				$request->dict->updateCache( TwatchDict::REF, $request->dict->results[ TwatchEntity::REF ]->id, 2, $this->valueId );
			}
			return true;
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0] );
		}

		public function allowImport() { return true; }

		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntSeKeywordPasvGene( $dict, $mode, $context );
		}


	}

	class TwatchEntGeneProcRef extends TwatchEntityGene {

		const MAX_KEYWORDS = 1073741824;

		public $valueClassName = 'EntityVProcRef';

		public function usesEntity( $entityId ) {
			if( $entityId == TwatchEntity::REF ) return 'Processed Referrer Entity';
			if( $entityId == TwatchEntity::SE_KEYWORD ) return 'Processed Referrer Entity';
			if( $entityId == TwatchEntity::REF_GROUP ) return 'Processed Referrer Entity';
			return false;
		}

		public function getDictEntryIdReferences( $dictId, $entity ) {
			$res = parent::getDictEntryIdReferences( $dictId, $entity );
			if( $dictId == TwatchDict::SE_KEYWORD || $dictId == TwatchDict::REF ) {
				foreach( $entity->getValueIdReferences() as $ref ) $res[] = $ref;
			}
			return $res;
		}

		public function getDictsUsed() {
			return array( TwatchDict::REF, TwatchDict::REF_DOMAIN, TwatchDict::SE_KEYWORD );
		}

		public function getPrecedents() {
			return array( TwatchEntity::REF, TwatchEntity::REF_GROUP, TwatchEntity::SE_KEYWORD );
		}

		public function attempt4( TwatchRequest $request ) {
			global $twatch;
			if( isset( $request->doneGenes[ TwatchEntity::SE_KEYWORD ] ) ) {
				$this->valueId = $request->doneGenes[ TwatchEntity::SE_KEYWORD ]->valueId;
				return true;
			}
			if( isset( $request->doneGenes[ TwatchEntity::REF_GROUP ] ) ) {
				$isSearchEngine = false;
				if( $request->doneGenes[ TwatchEntity::REF_GROUP ]->valueId <= TwatchEntGeneRefGroup::MAX_SEARCHE && $request->doneGenes[ TwatchEntity::REF_GROUP ]->valueId != TwatchEntGeneRefGroup::NONE ) {
					if( $twatch->config->propertyExists( TwatchConfig::SEARCH_ENGINES, $request->doneGenes[ TwatchEntity::REF_GROUP ]->valueId ) ) {
						$isSearchEngine = $twatch->config->get( TwatchConfig::SEARCH_ENGINES, $request->doneGenes[ TwatchEntity::REF_GROUP ]->valueId )->isSearchEngine;
					} else {
						$isSearchEngine = true;
					}
				}
				if( !$isSearchEngine ) {
					if( !isset( $request->doneGenes[ TwatchEntity::REF ] ) ) return false;
					$this->valueId = $request->doneGenes[ TwatchEntity::REF ]->valueId;
					return true;
				}
			}
			return false;
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0] );
		}

		public function allowImport() { return true; }

		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntProcRefPasvGene( $dict, $mode, $context );
		}
	}

	class TwatchEntGeneRefType extends TwatchEntityGene {

		const NONE = 1;
		const SEARCHE = 2;
		const URL = 3;
		const WEB_AREA = 4;

		public $valueClassName = 'EntityVRefType';

		public function attempt4( TwatchRequest $request ) {
			global $twatch;
			if( !isset( $request->doneGenes[ TwatchEntity::REF_GROUP ] ) ) return false;
			$groupId = $request->doneGenes[ TwatchEntity::REF_GROUP ]->valueId;

			if( $groupId == TwatchEntGeneRefGroup::NONE ) {
				$this->valueId = self::NONE;
			} elseif( $groupId < TwatchEntGeneRefGroup::MAX_SEARCHE ) {
				$this->valueId = self::SEARCHE;
				if( $twatch->config->propertyExists( TwatchConfig::SEARCH_ENGINES, $groupId ) ) {
					if( $twatch->config->get( TwatchConfig::SEARCH_ENGINES, $groupId )->isSearchEngine ) {
						$this->valueId = self::WEB_AREA;
					}
				}
				
			} elseif( $groupId > TwatchEntGeneRefGroup::MAX_SEARCHE ) {
				$this->valueId = self::URL;
			}
			return true;
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0] );
		}

		public function allowImport() { return true; }

		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntRefTypePasvGene( $mode, $context );
		}

	}
	class TwatchEntGeneHour extends TwatchEntityGene {

		public $valueClassName = 'EntityVHour';

		public function getSet() { return 24; }

		public function attempt1( TwatchRequest $request ) {
			global $twatch;
			$this->valueId = $twatch->now->getHour() + 1;
			return true;
		}

		public function allowImport() { return true; }

		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntHourPasvGene( $dict, $mode, $context );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0] );
		}
	}


	class TwatchEntGeneWeekday extends TwatchEntityGene {

		public $valueClassName = 'EntityVWeekday';

		public function getSet() { return 7; }
		public function attempt1( TwatchRequest $request ) {
			global $twatch;
			$this->valueId = $twatch->now->getWeekday() + 1;
			return true;
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0] );
		}

		public function allowImport() { return true; }

		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntWeekdayPasvGene( $dict, $mode, $context );
		}
	}

	class TwatchEntGenePage extends TwatchEntGeneDict {

		public $valueClassName = 'EntityVPage';

		public $dictId = TwatchDict::PAGE;

		protected function makeString( TwatchRequest $request ) {
			if( parent::makeString( $request ) === false ) return false;
			$this->str = $request->websiteId.'-'.$this->str;
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1] );
		}

		public function allowImport() { return true; }

		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntPagePasvGene( $dict, $mode, $context );
		}


	}

	class TwatchEntGeneAgentString extends TwatchEntGeneDict {

		public $valueClassName = 'EntityVAgtStr';

		public $dictId = TwatchDict::AGENT;

		public function allowExplicitAdd() {
			return true;
		}

		public function allowImport() { return true; }

		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntDictPasvGene( TwatchDict::AGENT, $dict, $mode, $context );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1] );
		}
	}

	class TwatchEntGeneUserAgent extends TwatchEntityGene {

		public $valueClassName = 'EntityVUserAgent';



		public function getPrecedents() {
			return array( TwatchEntity::AGENT_STR );
		}

		public function usesEntity( $entityId ) {
			if( $entityId == TwatchEntity::AGENT_STR ) return 'User Agent Entity';
			return false;
		}

		public function attempt2( TwatchRequest $request ) {
			global $twatch;

			if( !$request->geneExists( TwatchEntity::AGENT_STR ) ) return false;

			if( isset( $request->dict->results[ TwatchEntity::AGENT_STR ] ) ) {
				if( $request->dict->results[ TwatchEntity::AGENT_STR ]->timestamp < $twatch->state->get( TwatchState::USER_AGENT_CACHE_VALID ) ) {
					$agentGene = $request->getGene( TwatchEntity::AGENT_STR );

					$this->valueId = TwatchUserAgent::match( $agentGene->str );

					$request->dict->updateCache( TwatchDict::AGENT, $request->dict->results[ TwatchEntity::AGENT_STR ]->id, 1, $this->valueId );

					return true;
				} elseif( $request->dict->results[ TwatchEntity::AGENT_STR ]->cache1 != 0 ) {
					$this->valueId = $request->dict->results[ TwatchEntity::AGENT_STR ]->cache1;
					return true;
				}


			}

			$agentGene = $request->getGene( TwatchEntity::AGENT_STR );

			if( $agentGene->str === null ) return false;

			$this->valueId = TwatchUserAgent::match( $agentGene->str );

			$request->dict->putCache( TwatchEntity::AGENT_STR, 1, $this->valueId );
			return true;

		}

		public function allowImport() { return true; }

		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntUserAgentPasvGene( $dict, $mode, $context );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0] );
		}
	}

	class TwatchEntGeneBool extends TwatchEntGeneInput {
		public $valueClassName = 'EntityVBool';

		const TRUE = 1;
		const FALSE = 2;

		public static function isUserCreatable() { return true; }

		public function attempt1( TwatchRequest  $request ) {
			if( !isset( $request->data[ $this->inputKey ] ) ) return false;
			if( $request->data[ $this->inputKey ] == true ) {
				$this->valueId = self::TRUE;
			} else {
				$this->valueId = self::FALSE;
			}
			return true;
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1] );
		}

		public function allowImport() { return true; }

		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntBoolPasvGene( $mode, $context );
		}

	}

	class TwatchEntGeneAdminCookie extends TwatchEntGeneNull {

		public function attempt1( TwatchRequest $request ) {
			global $twatch;
			if( !isset( $request->data[ $this->inputKey ] ) ) return false;
			if( $request->data[ $this->inputKey ] === true || $twatch->config->get( TwatchConfig::ADMIN_COOKIE )->isAdminCookie( $request->data[ $this->inputKey ] ) ) {
				$this->valueId = self::EXISTS;
				return true;
			} else {
			}
			return false;
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2] );
		}

	}

	class TwatchEntGeneInputIp extends TwatchEntGeneInput {

		public $valueClassName = 'EntityVIp';

		public function allowExplicitAdd() { return true; }
		public function allowImport() { return true; }

		public function getDictsUsed() { return array( TwatchDict::IP ); }

		public function getDictEntryIdReferences( $dictId, $entity ) {
			$res = parent::getDictEntryIdReferences( $dictId, $entity );
			if( $dictId == TwatchDict::IP ) {
				foreach( $entity->getValueIdReferences() as $ref ) $res[] = $ref;
			}
			return $res;
		}

		protected function makeString( TwatchRequest $request ) {
			if( !isset( $request->data[ $this->inputKey ] ) ) return false;
			$ipStr = ardeGetIp( $request->data[ $this->inputKey ] );
			if( $ipStr === false ) return false;
			$this->str = $ipStr;
		}

		public function attempt1( TwatchRequest $request ) {

			if( $this->makeString( $request ) === false ) return false;

			$request->dict->get( $this->entityId, TwatchDict::IP, $this->str );
		}

		public function attempt2( TwatchRequest $request ) {

			$this->valueId = ardeIpToU32( $this->str );

			if( !isset( $request->dict->results[ $this->entityId ] ) ) {

				$request->dict->put( $this->entityId, TwatchDict::IP, $this->str, null, null, $this->valueId );
			}

			return true;

		}

		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntIpPasvGene( TwatchDict::IP, $dict, $mode, $context );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1] );
		}

	}

	class TwatchEntGeneIp extends TwatchEntityGene {

		public $valueClassName = 'EntityVIp';

		public $origin = null;

		public function allowExplicitAdd() { return true; }
		public function allowImport() { return true; }

		public function getDictEntryIdReferences( $dictId, $entity ) {
			$res = parent::getDictEntryIdReferences( $dictId, $entity );
			if( $dictId == TwatchDict::IP ) {
				foreach( $entity->getValueIdReferences() as $ref ) $res[] = $ref;
			}
			return $res;
		}

		public function getDictsUsed() { return array( TwatchDict::IP ); }

		public function managesDicts() { return array( TwatchDict::IP ); }

		public function usesEntity( $entityId ) {
			if( $entityId == TwatchEntity::RIP ) return 'IP Entity';
			return false;
		}

		public function getPrecedents() {
			return array( TwatchEntity::RIP, TwatchEntity::FIP );
		}

		public function attempt2( TwatchRequest $request ) {
			if( isset( $request->doneGenes[ TwatchEntity::FIP ] ) ) {
				$this->valueId = $request->doneGenes[ TwatchEntity::FIP ]->valueId;
				$this->origin = TwatchEntity::FIP;
			} elseif( isset( $request->doneGenes[ TwatchEntity::RIP ] ) ) {
				$this->valueId = $request->doneGenes[ TwatchEntity::RIP ]->valueId;
				$this->origin = TwatchEntity::RIP;
			} else {
				$this->valueId = 0;
			}
			return true;
		}


		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntIpPasvGene( TwatchDict::IP, $dict, $mode, $context );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0] );
		}

	}

	class TwatchEntGenePip extends TwatchEntityGene {

		public $valueClassName = 'EntityVIp';

		public $origin = TwatchEntity::RIP;

		public function allowExplicitAdd() { return true; }
		public function allowImport() { return true; }

		public function getDictEntryIdReferences( $dictId, $entity ) {
			$res = parent::getDictEntryIdReferences( $dictId, $entity );
			if( $dictId == TwatchDict::IP ) {
				foreach( $entity->getValueIdReferences() as $ref ) $res[] = $ref;
			}
			return $res;
		}

		public function getDictsUsed() { return array( TwatchDict::IP ); }

		public function getPrecedents() {
			return array( TwatchEntity::RIP, TwatchEntity::FIP );
		}

		public function attempt2( TwatchRequest $request ) {
			if( isset( $request->doneGenes[ TwatchEntity::FIP ] ) ) {
				if( isset( $request->doneGenes[ TwatchEntity::RIP ] ) ) {
					$this->valueId = $request->doneGenes[ TwatchEntity::RIP ]->valueId;
					return true;
				}
			}
			return false;
		}

		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntIpPasvGene( TwatchDict::IP, $dict, $mode, $context );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0] );
		}

	}



	class TwatchEntGeneCookie extends TwatchEntGeneInput {

		public $valueClassName = 'EntityVCookie';

		public function attempt1( TwatchRequest $request ) {
			global $twatch;
			if( $this->makeString( $request ) === false ) return false;
			$decrypted = $twatch->config->get( TwatchConfig::COOKIE_KEYS )->decrypt( $this->str, $this->entityId == TwatchEntity::SCOOKIE );
			if( $decrypted === false ) return false;

			if( is_int( $decrypted ) ) {
				$this->valueId = $decrypted;
			} else {
				foreach( $decrypted as $websiteId => $rid ) {
					if( $websiteId == $request->parentWebsiteId ) continue;
					if( $this->entityId == TwatchEntity::SCOOKIE ) {
						$request->otherWebsitesSCookies[ $websiteId ] = $rid;
					} else {
						$request->otherWebsitesPCookies[ $websiteId ] = $rid;
					}
				}

				if( !isset( $decrypted[ $request->parentWebsiteId ] ) ) return false;

				$this->valueId = $decrypted[ $request->parentWebsiteId ];
			}
			return true;
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1] );
		}

		public function allowImport() { return true; }

		public function getPassiveGene( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			return new TwatchEntCookiePasvGene( $mode, $context );
		}


	}

	abstract class TwatchPatternedObject implements ArdeSerializable {

		public $id;
		public $name;
		public $pattern;
		public $hasImage;

		public function __construct( $id, $name, $pattern, $hasImage = false ) {
			$this->id = $id;
			$this->name = $name;
			$this->pattern = $pattern;
			$this->hasImage = $hasImage;
		}

		protected function jsParams() {
			return $this->id.", '".ArdeJs::escape( $this->name )."', '".ArdeJs::escape( $this->pattern )."', ".ArdeJs::bool( $this->hasImage );
		}

		abstract public function matches( $str );

		abstract public function jsObject();

		public static function getParamsData( $a ) {
			$o = array();
			$o[ 'name' ] = ArdeParam::str( $a, 'n' );
			
			if( get_magic_quotes_gpc() ) {
				$o[ 'pattern' ] = stripslashes( ArdeParam::str( $a, 'p' ) );
			} else {
				$o[ 'pattern' ] = ArdeParam::str( $a, 'p' );
			}
			
			$o[ 'has_image' ] = ArdeParam::bool( $a, 'hi' );
			try {
				
				if( preg_match( $o[ 'pattern' ], '', $matches ) === false ) throw new TwatchUserError( 'you have an error in your pattern' );
			} catch( ArdeException $e ) {
				if( preg_match( '/^[^\:]+\:(.+)$/', $e->getMessage(), $matches ) ) {
					$message = $matches[1];
				} else {
					$message = $e->getMessage();
				}
				$e =  new TwatchUserError( 'you have an error in your pattern', 0, null );
				$e->safeExtras[] = $message;
				throw $e;
			}
			return $o;
		}



		public function isEquivalent( self $obj ) {
			if( $obj->name != $this->name ) return false;
			if( $obj->pattern != $this->pattern ) return false;
			if( $obj->hasImage != $this->hasImage ) return false;
			return true;
		}



		public function getSerialData() {
			return new ArdeSerialData( 2, array( $this->id, $this->name, $this->pattern, $this->hasImage ) );
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' id="'.$this->id.'" name="'.htmlentities( $this->name ).'"'.$extraAttrib.' has_image="'.($this->hasImage?'true':'false').'">' );
			$p->pl( '	<pattern>'.htmlentities( $this->pattern ).'</pattern>' );
			$p->hold( 1 );
			$this->extraPrintXml( $p );
			$p->rel();
			$p->pn( '</'.$tagName.'>' );
		}
		
		protected function extraPrintXml( ArdePrinter $p ) {}
	}

	class TwatchUserAgent extends TwatchPatternedObject {

		const UNKNOWN = 1;
		const FIREFOX = 2;
		const IE = 3;
		const OPERA = 4;
		const SAFARI = 5;
		const LYNX = 6;
		const KONQUEROR = 7;
		const MOZILLA = 8;
		const NETSCAPE = 9;
		const CHROME = 10;

		const ROBOT_GOOGLE = 1001;
		const ROBOT_GOOGLE_IMAGES = 1002;
		const ROBOT_YAHOO = 1003;
		const ROBOT_BING = 1004; 


		public function matches( $str ) {
			if( $this->pattern === null ) return false;
			return preg_match( $this->pattern, $str );
		}

		public function jsObject() {
			return 'new UserAgent( '.$this->jsParams()." )";
		}

		public static function fromParams( $a, $new ) {
			global $twatch;
			if( $new ) {
				$id = $twatch->config->getNewSubId( TwatchConfig::USER_AGENTS );
			} else {
				$id = ArdeParam::int( $a, 'i' );
				if( !$twatch->config->propertyExists( TwatchConfig::USER_AGENTS, $id ) ) throw new TwatchException( 'user agent '.$id.' not found' );
			}

			if( $id == TwatchUserAgent::UNKNOWN ) {
				$data = array();
				$data[ 'name' ] = ArdeParam::str( $a, 'n' );
				$data[ 'pattern' ] = null;
				$data[ 'has_image' ] = true;
			} else {
				$data = self::getParamsData( $a );
			}
			return new self( $id, $data[ 'name' ], $data[ 'pattern' ], $data[ 'has_image' ] );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3] );
		}

		public static function match( $s ) {
			global $twatch;
			$userAgents = &$twatch->config->getList( TwatchConfig::USER_AGENTS );

			end( $userAgents );
			while( key( $userAgents ) !== TwatchUserAgent::UNKNOWN && key( $userAgents ) !== null ) {
				if( current( $userAgents )->matches( $s ) ) {
					return current( $userAgents )->id;
				}
				prev( $userAgents );
			}
			return TwatchUserAgent::UNKNOWN;
		}

		public static function invalidateCache() {
			global $twatch;
			$res = $twatch->db->query( 'SELECT UNIX_TIMESTAMP()' );
			$r = $twatch->db->fetchRow( $res );
			$twatch->state->set( (int)$r[0], TwatchState::USER_AGENT_CACHE_VALID );
		}
	}

	class TwatchSEKeyword {
		public $searchEngineId;
		public $keyword;
		public function __construct( $searchEngineId, $keyword ) {
			$this->searchEngineId = $searchEngineId;
			$this->keyword = $keyword;
		}
	}

	class TwatchSearchEngine extends TwatchPatternedObject {

		const GOOGLE = 2;
		const YAHOO = 3;
		const GOOGLE_IMAGES = 4;
		const ALTAVISTA = 5;
		const MSN = 6;
		const MY_WAY = 7;
		const AOL = 8;
		const BING = 9;
		const GOOGLE_IMAGES_AREA = 10;
		const YANDEX = 11;
		public $isSearchEngine; 
		
		public function __construct( $id, $name, $pattern, $hasImage = false, $isSearchEngine = true ) {
			parent::__construct( $id, $name, $pattern, $hasImage );
			$this->isSearchEngine = $isSearchEngine;
		}

		public function matches( $str ) {
			if( !preg_match( $this->pattern, $str, $matches ) ) return false;
			if( $this->isSearchEngine ) {
				if( !isset( $matches[ 'keyword' ] ) ) {
					ArdeException::reportError( new TwatchWarning ( 'pattern for "'.$this->name.'" search engine must contain a sub pattern named <keyword>' ) );
					return false;
				}
			} else {
				return null;
			}
			return urldecode( $matches[ 'keyword' ] );
		}

		protected function jsParams() {
			return parent::jsParams().', '.ArdeJs::bool( $this->isSearchEngine );
		}
		
		public function jsObject() {
			return 'new SearchEngine( '.$this->jsParams()." )";
		}
		
		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			parent::printXml( $p, $tagName, ' is_searche="'.($this->isSearchEngine?'true':'false').'"'.$extraAttrib );
		}

		public static function fromParams( $a, $new ) {
			global $twatch;
			if( $new ) {
				$id = $twatch->config->getNewSubId( TwatchConfig::SEARCH_ENGINES );
				$hasImage = false;
				$isSearchEngine = ArdeParam::bool( $a, 'is' );
			} else {
				$id = ArdeParam::int( $a, 'i' );
				if( !$twatch->config->propertyExists( TwatchConfig::SEARCH_ENGINES, $id ) ) throw new TwatchException( 'search engine '.$id.' not found' );
				$isSearchEngine = $twatch->config->get( TwatchConfig::SEARCH_ENGINES, $id )->isSearchEngine;
				$hasImage = $twatch->config->get( TwatchConfig::SEARCH_ENGINES, $id )->hasImage;
			}
			$data = self::getParamsData( $a );
			return new self( $id, $data[ 'name' ], $data[ 'pattern' ], $hasImage, $isSearchEngine );
		}

		public function getSerialData() {
			$data = parent::getSerialData();
			$data->data[] = $this->isSearchEngine;
			return $data;
		}
		
		public static function makeSerialObject( ArdeSerialData $d ) {
			if( $d->version < 2 ) {
				$isSearchEngine = true;
			} else {
				$isSearchEngine = $d->data[4];
			}
			return new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3], $isSearchEngine );
		}

		public static function match( $s ) {
			global $twatch;
			$searchEngines = &$twatch->config->getList( TwatchConfig::SEARCH_ENGINES );

			if( preg_match( "/^ (http\:\/\/ images\.google\. .+? \/ imgres\? (?!(?:.*&|)q=) (?:.*&|)prev=)(.+?)(?=$|&) (.*)$ /xi", $s, $matches ) ) {
				if( preg_match( '/(?:.*&|)q=(.+?)(?=$|&)/', urldecode($matches[2]), $qMatches ) ) {
					$s = $matches[1].$matches[2].'&q='.$qMatches[1].$matches[3];
				}

			}


			end( $searchEngines );
			while( key( $searchEngines ) !== null  ) {
				$keyword = current( $searchEngines )->matches( $s );
				if( $keyword !== false ) {
					if( $keyword === null ) return current( $searchEngines )->id;
					return new TwatchSEKeyword( current( $searchEngines )->id, $keyword );
				}
				prev( $searchEngines );
			}
			return false;
		}

		public static function invalidateCache() {
			global $twatch;
			$res = $twatch->db->query( 'SELECT UNIX_TIMESTAMP()' );
			$r = $twatch->db->fetchRow( $res );
			$twatch->state->set( (int)$r[0], TwatchState::SEARCHE_CACHE_VALID );
		}
	}

	class TwatchDict implements ArdeSerializable {

		const MAX_PREDEFINED = 1000;

		const REF = 1;
		const AGENT = 2;
		const PAGE = 3;
		const IP = 4;
		const REF_DOMAIN = 8;
		const SE_KEYWORD = 9;

		const RFRNC_HISTORY = 1;
		const RFRNC_PATH = 2;
		const RFRNC_DICT = 3;
		const RFRNC_REVDICT = 4;
		const RFRNC_TABLE = 5;
		const RFRNC_DATA = 6;



		var $id;
		var $cleanupDays;
		var $defaultValues = null;
		var $idsStartFrom  = 1;
		var $predefinedData = array();
		public $singleName;
		public $pluralName;
		public $allowCleanup = true;

		public $keepAnyway = 0;

		public $allowExplicitAdd = false;

		public $cleanupEntriesPerTask = 20;

		function __construct( $id, $singleName, $pluralName, $cleanupDays = 0, $idsStartFrom = 1 ) {
			$this->id = $id;
			$this->singleName = $singleName;
			$this->pluralName = $pluralName;
			$this->cleanupDays = $cleanupDays;
			$this->idsStartFrom = $idsStartFrom;
		}

		public static function fromParams( $a, $prefix, $new, $valuesPrefix = '' ) {
			global $twatch;

			$cleanupDays = ArdeParam::int( $a, $prefix.'cd', 2, 90 );

			if( !$new ) {
				$id = ArdeParam::int( $a, $prefix.'i' );
				$origDict = $twatch->config->get( TwatchConfig::DICTS, $id );
				$o = new self( $id, $origDict->singleName, $origDict->pluralName, $cleanupDays, $origDict->idsStartFrom );
				$o->allowCleanup = $origDict->allowCleanup;
				$o->allowExplicitAdd = $origDict->allowExplicitAdd;
				$o->keepAnyway = $origDict->keepAnyway;
				$o->cleanupEntriesPerTask = $origDict->cleanupEntriesPerTask;
				return $o;
			} else {
				return new self( null, $valuesPrefix, $valuesPrefix.'s', $cleanupDays );
			}
		}

		public function clearCache( $pos ) {
			global $twatch;
			$dbDict = new TwatchDbPassiveDict( $twatch->db );
			$dbDict->clearCache( $this->id, $pos );
		}

		public function fullCleanup() {
			$this->removeCleanupTasks();

			$this->cleanup();

			$this->makeBeginningCleanupTask();
		}

		public function cleanup( $startId = null, $endId = null ) {
			global $twatch;
			$refs = $this->getEntryIdRefs();
			TwatchDbDict::cleanupDict( $twatch->db, $this->id, $refs, $this->keepAnyway, $this->idsStartFrom, $startId, $endId );
		}

		public function resetCleanup() {
			global $twatch;
			$this->removeCleanupTasks();
			$cleanupTask = new TwatchMakeDictCleanupTasks( $this->id, $twatch->now->dayOffset( 1 )->getDayStart() + 600 );
			$cleanupTask->run();
		}

		public function isEquivalent( self $dict ) {
			if( $dict->cleanupDays != $this->cleanupDays ) return false;
			return true;
		}

		public function getBoundaryIds( $groupSize ) {
			global $twatch;
			return TwatchDbDict::getBoundaryIds( $twatch->db, $groupSize, $this->id, $this->idsStartFrom );
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->id, $this->singleName, $this->pluralName, $this->cleanupDays, $this->idsStartFrom, $this->allowCleanup, $this->allowExplicitAdd, $this->keepAnyway, $this->cleanupEntriesPerTask ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			$o = new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3], $d->data[4] );
			$o->allowCleanup = $d->data[5];
			$o->allowExplicitAdd = $d->data[6];
			$o->keepAnyway = $d->data[7];
			$o->cleanupEntriesPerTask = $d->data[8];
			return $o;
		}

		public function getEntries( $offset, $count, $beginWith = null, $getStringsToo = false ) {
			global $twatch;
			return TwatchDbrDict::getEntries( $twatch->db, $this->id, $offset, $count, $beginWith, $this->idsStartFrom, $getStringsToo );
		}

		public function getEntryIdRefs() {
			return TwatchEntity::getAllDictEntryIdRefs( $this->id );
		}

		public function jsObject() {
			return 'new Dict( '.$this->id.", '".ArdeJs::escape( $this->singleName )."', '".ArdeJs::escape( $this->pluralName )."', ".ArdeJs::bool( $this->allowCleanup ).', '.$this->cleanupDays.', '.ArdeJs::bool( $this->allowExplicitAdd ).')';
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			$p->pl( '<'.$tagName.' id="'.$this->id.'" allow_cleanup="'.($this->allowCleanup?'true':'false').'" cleanup_days="'.$this->cleanupDays.'" explicit_add="'.($this->allowExplicitAdd?'true':'false').'"'.$extraAttrib.' >' );
			$p->pl( 	'<single_name>'.$this->singleName.'</single_name>' );
			$p->pl( 	'<plural_name>'.$this->pluralName.'</plural_name>' );
			$p->pn( '</'.$tagName.'>' );
		}

		public static function installBase( $overwrite ) {
			global $twatch;
			TwatchDbDict::install( $twatch->db, $overwrite );
		}

		public static function uninstallBase() {
			global $twatch;
			TwatchDbDict::uninstall( $twatch->db );
		}

		public function install() {
			global $twatch;


			$twatch->state->set( new TwatchDictState(), TwatchState::DICT_STATES, $this->id );
			$dbDict = new TwatchDbDict( $twatch->db );
			foreach( $this->predefinedData as $id => $data ) {
				$dbDict->put( $id, $this->id, $data[0], array(0,0), $data[1], $id );
			}
			$dbDict->rollPut();
			$dbDict->setIdsStartFrom( $this->id, $this->idsStartFrom );

			if( $this->cleanupDays ) {
				$this->makeBeginningCleanupTask();
			}

		}

		public function uninstall() {
			global $twatch;

			try {
				$twatch->state->remove( TwatchState::DICT_STATES, $this->id );
			} catch( ArdeException $e ) {
				ArdeException::reportError( $e );
			}
			$dbDict = new TwatchDbDict( $twatch->db );
			$dbDict->removeAllDictEntries( $this->id );

			if( $this->cleanupDays ) {
				$this->removeCleanupTasks();
			}
		}

		public function makeBeginningCleanupTask() {
			global $twatch;

			$taskManager = new TwatchTaskManager();
			$cleanupTask = new TwatchMakeDictCleanupTasks( $this->id, $twatch->now->dayOffset( $this->cleanupDays )->getDayStart() + 600 );
			$cleanupTask->due = $twatch->now->dayOffset( $this->cleanupDays )->getDayStart() + 600;
			$taskManager->queueTasks( array( $cleanupTask ) );
		}

		public function removeCleanupTasks() {

			$taskManager = new TwatchTaskManager();
			$tasks = $taskManager->getAllTasks( 'TwatchMakeDictCleanupTasks' );
			foreach( $tasks as $task ) {
				if( $task->dictId == $this->id ) {
					$taskManager->deleteTask( $task->taskId, $task->inQueue );
				}
			}
			$tasks = $taskManager->getAllTasks( 'TwatchCleanupDict' );
			foreach( $tasks as $task ) {
				if( $task->dictId == $this->id ) {
					$taskManager->deleteTask( $task->taskId, $task->inQueue );
				}
			}
		}
	}

	class TwatchDictState implements ArdeSerializable {
		public $usage;

		public function __construct( $usage = array() ) {
			$this->usage = $usage;
		}

		public function isUsed() {
			return count( $this->usage ) != 0;
		}

		public function addEntityUsage( $entityId ) {
			$this->usage[ $entityId ] = true;
		}

		public function removeEntityUsage( $entityId ) {
			unset( $this->usage[ $entityId ] );
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->usage ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0] );
		}

	}

	class TwatchMakeDictCleanupTasks extends ArdeDisTask {

		const MAX_TASKS_PER_DAY = 1000;

		public $dictId;
		public $ts;

		function __construct( $dictId, $ts ) {
			$this->dictId = $dictId;
			$this->ts = $ts;
		}

		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->dictId, $this->ts ) );
		}


		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1] );
		}

		function run() {
			global $twatch;

			$dict = $twatch->config->get( TwatchConfig::DICTS, $this->dictId );

			$boundaryIds = $dict->getBoundaryIds( $dict->cleanupEntriesPerTask );

			$cnt = count( $boundaryIds );
			$ratio = 1;
			if( $cnt / 2 > self::MAX_TASKS_PER_DAY ) {
				$ratio = (int)ceil( ( $cnt / 2 ) / self::MAX_TASKS_PER_DAY );
			}

			$tasks = array();

			$start = null;
			$consume = $ratio - 1;
			for( $i = 0; $i < $cnt; $i += 2 ) {
				if( $start === null ) {
					$start = $boundaryIds[ $i ];
				}
				if( $consume == 0 || !isset( $boundaryIds[ $i + 2 ] ) ) {
					$end = $boundaryIds[ $i + 1 ];
					$tasks[] = new TwatchCleanupDict( $this->dictId, $start, $end );
					$consume = $ratio - 1;
					$start = null;
				} else {
					--$consume;
				}
			}



			$taskScheduler = new ArdeDisTaskScheduler( TwatchTime::getTime( $this->ts )->dayOffset(1)->getDayStart(), TwatchTime::getTime( $this->ts )->dayOffset( $dict->cleanupDays )->getDayStart() - 3600 );
			$taskScheduler->addTasks( $tasks );
			$taskScheduler->scheduleTasks();

			$nextTask = new TwatchMakeDictCleanupTasks( $this->dictId, TwatchTime::getTime( $this->ts )->dayOffset( $dict->cleanupDays )->getDayStart() + 600 );
			$nextTask->due = TwatchTime::getTime( $this->ts )->dayOffset( $dict->cleanupDays )->getDayStart() + 600;
			$tasks[] = $nextTask;


			$taskManager = new TwatchTaskManager();
			$taskManager->queueTasks( $tasks );

		}
	}

	class TwatchCleanupDict extends ArdeDisTask {
		var $dictId;
		var $startId;
		var $endId;

		function __construct( $dictId = 0, $startId = 0, $endId = 0 ) {
			$this->dictId = $dictId;
			$this->startId = $startId;
			$this->endId = $endId;
		}


		public function getSerialData() {
			return new ArdeSerialData( 1, array( $this->dictId, $this->startId, $this->endId ) );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			return new self( $d->data[0], $d->data[1], $d->data[2] );
		}

		function run() {
			global $twatch;

			$dict = $twatch->config->get( TwatchConfig::DICTS, $this->dictId );
			$dict->cleanup( $this->startId, $this->endId );

		}
	}

	$twatch->includePosition( 'entity' );
?>