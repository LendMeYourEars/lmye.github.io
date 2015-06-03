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

	class TwatchEntityVGen {

		private $dict;
		private $entityVs = array();

		private static $utilityEntities = array(
			TwatchEntity::TIME => 'EntityVTime',
			TwatchEntity::PATH => 'EntityVPath'
		);

		public function __construct() {
			global $twatch;
			$this->dict = new TwatchDbrDict( $twatch->db );
		}

		public static function makeFinalized( $entityId, $id ) {
			$gen = new self();
			$res = &$gen->make( $entityId, $id );
			$gen->finalizeEntityVs();
			return $res;
		}

		public function &make( $entityId, $id ) {
			global $twatch;

			$className = null;


			$entities = &$twatch->config->getList( TwatchConfig::ENTITIES );


			if( isset( $entities[ $entityId ] ) ) {
				if( $entities[ $entityId ]->gene->valueClassName !== null ) {
					$className = $entities[ $entityId ]->gene->valueClassName;
				}
			} else {
				if( isset( self::$utilityEntities[ $entityId ] ) ) {
					$className = self::$utilityEntities[ $entityId ];
				}
			}
			if( $className === null ) {
				$className = 'EntityVError';
			}

			$o = $twatch->makeObject( $className, $entityId, $id );

			$this->entityVs[] = &$o;
			return $o;
		}

		public function finalizeEntityVs() {

			foreach( $this->entityVs as $k => &$entityV ) {
				try {
					if( $entityV->finalizeAttempt0() === false ) {
						unset( $this->entityVs[$k] );
						continue;
					}
					$dictReq = $entityV->finalizeAttempt1();
					if( $dictReq === false ) {
						unset( $this->entityVs[$k] );
					} elseif( $dictReq instanceof DictRequest ) {
						$this->dict->get( $dictReq->dictId, $dictReq->id );
					} else {
						throw new TwatchException( 'received unknown return value from '.get_class( $entityV ).'::finalizeAttempt1' );
					}
				} catch( ArdeException $e ) {
					ArdeException::reportError( $e );
					unset( $this->entityVs[$k] );
				}
			}

			$this->dict->rollGet();


			foreach( $this->entityVs as $k => &$entityV ) {
				try {
					$dictReq = $entityV->finalizeAttempt2( $this->dict );
					if( $dictReq === false ) {
						unset( $this->entityVs[$k] );
					} elseif( $dictReq instanceof DictRequest ) {
						$this->dict->get( $dictReq->dictId, $dictReq->id );
					} else {
						throw new TwatchException( 'received unknown return value from '.get_class( $entityV ).'::finalizeAttempt2' );
					}
				} catch( ArdeException $e ) {
					ArdeException::reportError( $e );
					unset( $this->entityVs[$k] );
				}
			}

			$this->dict->rollGet();

			foreach( $this->entityVs as $k => &$entityV ) {
				try {
					$entityV->finalizeAttempt3( $this->dict );
				} catch( ArdeException $e ) {
					ArdeException::reportError( $e );
				}
				unset( $this->entityVs[$k] );
			}
		}
	}

	class DictRequest {
		public $dictId;
		public $id;
		public function DictRequest( $dictId, $id ) {
			$this->dictId = $dictId;
			$this->id = $id;
		}
	}

	class EntityV {

		const STRING_DEFAULT = 1;
		const STRING_SELECT = 2;
		const STRING_SHORT = 3;
		const STRING_LONG = 4;
		const STRING_VISITOR_TITLE = 5;
		const STRING_PATH_ANALYSIS = 6;
		const STRING_NO_TITLE = 7;
		const STRING_EXPRESSION = 8;

		public static $stringIdNames = array(
			 self::STRING_DEFAULT => 'Default'
			,self::STRING_SELECT => 'Select'
			,self::STRING_SHORT => 'Short'
			,self::STRING_LONG => 'Long'
			,self::STRING_VISITOR_TITLE => 'Visitor Title'
			,self::STRING_PATH_ANALYSIS => 'Path Analysis'
			,self::STRING_NO_TITLE => 'No Title'
			,self::STRING_EXPRESSION => 'Expression'
		);

		public static function getExtraStringIdNames() {
			return null;
		}

		const JS_MODE_BLOCK = 1;
		const JS_MODE_INLINE = 2;
		const JS_MODE_INLINE_BLOCK = 3;

		public $id;
		public $entityId;
		public $str;

		protected $jsClassName = 'EntityV';

		public function __construct( $entityId, $id ) {
			$this->id = $id;
			$this->entityId = $entityId;
			$this->str = '!NS '.$entityId.'-'.$id;
		}

		public function finalizeAttempt0() {
			global $ardeUser, $twatch;
			$vis = $ardeUser->user->data->get( TwatchUserData::VIEW_ENTITY, $this->entityId );
			if(  $vis != TwatchEntity::VIS_VISIBLE ) {
				$this->str = 'hidden';
				return false;
			}
			return true;
		}
		
		function finalizeAttempt1() {
			return true;
		}

		function finalizeAttempt2( TwatchDbrDict $dict ) {
			return true;
		}

		function finalizeAttempt3( TwatchDbrDict $dict ) {
			return true;
		}




		function jsParams( TwatchEntityView $view, $mode ) {
			global $twatch;
			if( !$this->hasImage() ) $showImage = false;
			else $showImage = $view->showImage;

			if( $view->showText ) {
				$str = "'".ArdeJs::escape( $this->getString( $view->stringId ) )."'";
			} else {
				$str = 'null';
			}
			if( $view->link ) {
				$link = $this->getLink();
				if( $link !== false ) {
					$link = "'".ArdeJs::escape( $link )."'";
				} else {
					$link = 'null';
				}
			} else {
			 	$link = 'null';
			}
			if( $view->forceLtr === null ) {
				$forceLtr = $this->getForceLtr();
			} else{
				$forceLtr = $view->forceLtr;
			}
			return $this->entityId.', '.$this->getIdParam().", ".$str.", ".ArdeJs::bool( $showImage ).", ".$link.", ".$mode.", ".$view->trimString.', '.ArdeJs::bool( $forceLtr );
		}


		protected function getIdParam() {
			global $ardeUser;
			$vis = $ardeUser->user->data->get( TwatchUserData::VIEW_ENTITY, $this->entityId );
			if(  $vis != TwatchEntity::VIS_VISIBLE ) {
				return 0;
			}
			return $this->id;
		}

		function jsObject( TwatchEntityView $view, $mode ) {

			return 'new '.$this->jsClassName.'( '.$this->jsParams( $view, $mode ).' )';
		}

		function printXml( ArdePrinter $p, $tagName, TwatchEntityView $view, $extraAttrib = '' ) {
			global $twatch;
			if( !$this->hasImage() ) $image = 'false';
			else $image = ($view->showImage?'true':'false');

			if( $view->forceLtr === null ) {
				$forceLtr = $this->getForceLtr();
			} else{
				$forceLtr = $view->forceLtr;
			}

			$p->pl( '<'.$tagName.( $this->jsClassName == 'EntityV' ? '' : ' js_class="'.$this->jsClassName.'"' ).' entity_id="'.$this->entityId.'" id="'.$this->getIdParam().'" image="'.$image.'" force_ltr="'.($forceLtr?'true':'false').'"'.$extraAttrib.'>', 1 );
			if( $view->showText ) $p->pl( '<string>'.ardeXmlEntities( $this->getString( $view->stringId ) ).'</string>' );
			if( $view->link )	{
				$link = $this->getLink();
				if( $link !== false ) {
					$p->pl( '<link>'.ardeXmlEntities( $link ).'</link>' );
				}
			}
			$this->printExtraXml( $p );
			$p->rel();
			$p->pn( '</'.$tagName.'>' );
		}

		protected function printExtraXml( ArdePrinter $p ) {}

		public function hasImage() {
			global $twatch;
			return $twatch->config->propertyExists( TwatchConfig::ENTITIES, $this->entityId ) && $twatch->config->get( TwatchConfig::ENTITIES, $this->entityId )->hasImage;
		}

		protected function _getForceLtr() {
			return false;
		}

		public function getForceLtr() {
			global $ardeUser;
			if( $ardeUser->user->data->get( TwatchUserData::VIEW_ENTITY, $this->entityId ) == TwatchEntity::VIS_VISIBLE ) return $this->_getForceLtr();
			return false;
		}
		
		protected function _getString( $id ) {
			global $twatch;
			return $twatch->locale->text( $this->str );
		}

		public function getString( $id ) {
			global $twatch, $ardeUser;
			if( $ardeUser->user->data->get( TwatchUserData::VIEW_ENTITY, $this->entityId ) == TwatchEntity::VIS_SHOW_AS_HIDDEN ) return $twatch->locale->text( $this->str );
			return $this->_getString( $id );
		}
		
		public function getLink() {
			return false;
		}




		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			throw new TwatchException( 'not implemented' );
		}

		public static function getArrayIds( $a, $offset, $count, $beginWith = null ) {
			if( !$count ) return array();
			$o = array();
			foreach( $a as $k => $ae ) {
				if( $beginWith == null || preg_match( '/^'.preg_quote( $beginWith, '/' ).'/i', $ae ) ) {
					if( $offset > 0 ) {
						--$offset;
					} else {
						$o[] = $k;
						if( $count > 0 && count( $o ) >= $count ) break;
					}
				}
			}
			return $o;
		}

		public static function getArrayIdsCount( $a, $beginWith = null ) {
			$c = 0;
			foreach( $a as $k => $ae ) {
				if( $beginWith == null || preg_match( '/^'.preg_quote( $beginWith, '/' ).'/i', $ae ) ) ++$c;
			}
			return $c;
		}
	}

	class EntityVSimpleString extends EntityV {
		var $dictId;

		var $dictRes = null;


		function finalizeAttempt1() {
			return new DictRequest( $this->dictId, $this->id );
		}

		function finalizeAttempt2( TwatchDbrDict $dict ) {
			$res = $dict->getResult( $this->dictId, $this->id );
			if( $res ) {
				$this->str = $res->str;
				$this->dictRes = $res;
			} else {
				$this->str = 'missing value: '.$this->dictId.'-'.$this->id;
			}
			return false;
		}
	}

	class EntityVSeKeyword extends EntityV {

		const STRING_GROUP_SUB = 1001;

		var $searchEngine = null;
		var $keyword = '!NS';

		function finalizeAttempt1() {
			return new DictRequest( TwatchDict::SE_KEYWORD, $this->id );
		}

		function finalizeAttempt2(  TwatchDbrDict $dict ) {
			$res = $dict->getResult( TwatchDict::SE_KEYWORD, $this->id );
			if( $res ) {
				$dashPos = strpos( $res->str, '-' );
				if( $dashPos === false ) return false;
				$searchEngineId = (int)substr( $res->str, 0, $dashPos );
				$this->keyword = substr( $res->str, $dashPos + 1 );
				$searchEngineId = (int)$searchEngineId;
				$this->searchEngine = new EntityVRefGroup( TwatchEntity::REF_GROUP, $searchEngineId );
				$this->searchEngine->finalizeAttempt1();
			}
			return false;
		}

		function _getString( $id ) {
			global $twatch;
			if( $id == EntityVProcRef::STRING_GROUP_SUB ) {
				return $this->keyword;
			} else {
				return $twatch->locale->text( '{search_engine} search for [{keyword}]', array( 'search_engine' => $this->searchEngine->searcheName, 'keyword' => $this->keyword ) );
			}
		}


		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			global $twatch;
			$a = array();
			foreach( $twatch->config->getList( TwatchConfig::SEARCH_ENGINES ) as $searchEngine ) {
				$a[ $searchEngine->id ] = $twatch->locale->text( '{search_engine} search for [', array( 'search_engine' => $searchEngine->name ) );
				if( strlen( $beginWith ) > strlen( $a[ $searchEngine->id ] ) ) $a[ $searchEngine->id ] .= substr( $beginWith, strlen( $a[ $searchEngine->id ] ) );
			}
			ardeUtf8Sort( $a );
			$searchEngineIds = self::getArrayIds( $a, $offset, -1, $beginWith );
			$finalIds = array();
			if( count( $searchEngineIds ) == 1 ) {
				$seId = $searchEngineIds[0];
				$ss = $twatch->locale->text( '{search_engine} search for [', array( 'search_engine' => $twatch->config->get( TwatchConfig::SEARCH_ENGINES, $seId )->name ) );
				if( strlen( $beginWith ) > strlen( $ss ) ) {
					$beginWith = substr( $beginWith, strlen( $ss ) );
					if( $beginWith[ strlen( $beginWith ) - 1 ] == ']' ) $beginWith = substr( $beginWith, 0, strlen( $beginWith ) - 1 );
					return TwatchDbrDict::getEntries( $twatch->db, TwatchDict::SE_KEYWORD, $offset, $count, $seId.'-'.$beginWith );
				} else {
					return TwatchDbrDict::getEntries( $twatch->db, TwatchDict::SE_KEYWORD, $offset, $count, $seId.'-' );
				}
			} else {
				$prevCount = 0;
				foreach( $searchEngineIds as $searchEngineId ) {
					$offset -= $prevCount;
					if( $offset < 0 ) $offset = 0;
					$ids = TwatchDbrDict::getEntries( $twatch->db, TwatchDict::SE_KEYWORD, $offset, $count, $searchEngineId.'-' );
					foreach( $ids as $id ) {
						$finalIds[] = $id;
						--$count;
						if( $count == 0 ) return $finalIds;
					}
					$prevCount = TwatchDbrDict::getIdsCount( $twatch->db, TwatchDict::SE_KEYWORD, $searchEngineId.'-' );
				}
			}
			return $finalIds;
		}

		public static function getIdsCount( $beginWith = null ) {
			global $twatch;
			TwatchDbrDict::getIdsCount( $twatch->db, TwatchDict::SE_KEYWORD, $beginWith );
		}
	}

	class EntityVProcRef extends EntityV {

		const STRING_GROUP_SUB = 1001;

		var $refGroup = null;
		var $seKeyword;
		var $ref;

		public static function getExtraStringIdNames() {
			return array(
				self::STRING_GROUP_SUB => 'Referrer Group Sub'
			);
		}


		protected $jsClassName = "EntityVProcRef";


		protected function _getString( $id ) {
			if( $this->id <= TwatchEntGeneProcRef::MAX_KEYWORDS ) {
				return $this->seKeyword->getString( $id );
			} else {
				return $this->ref->getString( $id );
			}
		}

		protected function _getForceLtr() {
			if( $this->id > TwatchEntGeneProcRef::MAX_KEYWORDS ) return true;
			return false;
		}

		public function getLink() {
			if( $this->id > TwatchEntGeneProcRef::MAX_KEYWORDS && $this->refGroup !== null && $this->refGroup->type != TwatchEntGeneRefType::NONE ) return $this->ref->getLink();
			return false;
		}

		function finalizeAttempt1() {
			global $twatch;

			if( $this->id < TwatchEntGeneProcRef::MAX_KEYWORDS ) {
				$this->seKeyword = new EntityVSeKeyword( TwatchEntity::SE_KEYWORD, $this->id );
				return $this->seKeyword->finalizeAttempt1();
			} else {
				$this->ref = new EntityVRef( TwatchEntity::REF, $this->id );
				return $this->ref->finalizeAttempt1();
			}
		}

		function finalizeAttempt2( TwatchDbrDict $dict ) {
			$ret = false;

			if( $this->id < TwatchEntGeneProcRef::MAX_KEYWORDS ) {
				$this->seKeyword->finalizeAttempt2( $dict );
				$this->refGroup = $this->seKeyword->searchEngine;
			} else {
				$this->ref->finalizeAttempt2( $dict );
				if( $this->ref->dictRes !== null ) {
					$this->refGroup = new EntityVRefGroup( TwatchEntity::REF_GROUP, $this->ref->dictRes->cache1 );
					$ret = $this->refGroup->finalizeAttempt1();

				}
			}


			return $ret;
		}

		function finalizeAttempt3( TwatchDbrDict $dict ) {
			$this->refGroup->finalizeAttempt2( $dict );
		}



		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			global $twatch;

			$res = EntityVSeKeyword::getIds( $entityId, $offset, $count, $beginWith );
			if( count( $res ) < $count ) {
				$seKeywordC = EntityVSeKeyword::getIdsCount( $beginWith );
				$offset -= $seKeywordC;
				if( $offset < 0 ) $offset = 0;
				$count -= count( $res );
				$resRefs = EntityVRef::getNoneKeywordIds( $offset, $count, $beginWith );
				$res = array_merge( $res, $resRefs );
			}
			return $res;
		}

		function jsParams( TwatchEntityView $view, $mode ) {
			if( $this->refGroup == null ) $typeId = TwatchEntGeneRefType::NONE;
			else $typeId =  $this->refGroup->type;
			return parent::jsParams( $view, $mode ).', '.TwatchEntity::REF_TYPE.', '.$typeId.', '.($this->id <= TwatchEntGeneProcRef::MAX_KEYWORDS?"'".ArdeJs::escape( is_object( $this->seKeyword ) ? $this->seKeyword->keyword : '' )."'":'null');
		}

		function printXml( ArdePrinter $p, $tagName, TwatchEntityView $view, $extraAttrib = '' ) {
			if( $this->refGroup == null ) $typeId = TwatchEntGeneRefType::NONE;
			else $typeId =  $this->refGroup->type;

			return parent::printXml( $p, $tagName, $view, $extraAttrib.' type_entity_id="'.TwatchEntity::REF_TYPE.'" type_id="'.$typeId.'" '.($this->id <= TwatchEntGeneProcRef::MAX_KEYWORDS?' keyword="'.ardeXmlEntities($this->seKeyword->keyword).'"':'') );
		}
	}

	class EntityVRefType extends EntityV {

		public static $idStrings = array(
			 TwatchEntGeneRefType::NONE => 'Other'
			,TwatchEntGeneRefType::SEARCHE => 'Search Engine'
			,TwatchEntGeneRefType::URL => 'URL'
			,TwatchEntGeneRefType::WEB_AREA => 'Web Area'
		);

		public function finalizeAttempt1() {
			if( isset( self::$idStrings[ $this->id ] ) ) {
				$this->str = self::$idStrings[ $this->id ];
			} else {
				$this->str = '%error invalid type id%';
			}
			return false;
		}

		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			asort( self::$idStrings );

			return self::getArrayIds( self::$idStrings, $offset, $count, $beginWith );
		}

		public static function getIdsCount( $beginWith = null ) {
			asort( self::$idStrings );

			return self::getArrayIdsCount( self::$idStrings, $beginWith );
		}
	}


	class EntityVRefGroup extends EntityV {


		var $type = TwatchEntGeneRefType::NONE;
		var $searcheName = '!NS';

		protected $jsClassName = 'EntityVRefGroup';

		protected function _getString( $id ) {
			global $twatch;
			if( $this->type == TwatchEntGeneRefType::NONE ) return $twatch->locale->text( $this->str );
			elseif( $this->type == TwatchEntGeneRefType::SEARCHE ) return $twatch->locale->text( '{search_engine} Search', array( 'search_engine' => $this->searcheName ) );
			elseif( $this->type == TwatchEntGeneRefType::WEB_AREA ) return $this->searcheName;
			else return $this->str;
		}

		function finalizeAttempt1() {
			global $twatch;

			if( $this->id == 1 ) {
				$this->type = TwatchEntGeneRefType::NONE;
				$this->str = 'Other';
			} elseif( $this->id < TwatchEntGeneRefGroup::MAX_SEARCHE ) {
				
				if( !$twatch->config->propertyExists( TwatchConfig::SEARCH_ENGINES, $this->id ) ) {
					$this->searcheName = '!unknown '.$this->id;
					$this->type = TwatchEntGeneRefType::SEARCHE;
				} else {
					$webArea = $twatch->config->get( TwatchConfig::SEARCH_ENGINES, $this->id );
					if( $webArea->isSearchEngine ) {
						$this->type = TwatchEntGeneRefType::SEARCHE;
					} else {
						$this->type = TwatchEntGeneRefType::WEB_AREA;
					}
					$this->searcheName = $webArea->name;
				}
				return false;
			} elseif( $this->id > TwatchEntGeneRefGroup::MAX_SEARCHE ) {
				$this->type = TwatchEntGeneRefType::URL;
				return new DictRequest( TwatchDict::REF_DOMAIN, $this->id );
			}
			return false;
		}

		function finalizeAttempt2( TwatchDbrDict $dict ) {

			if( $res = $dict->getResult( TwatchDict::REF_DOMAIN, $this->id ) ) {
				$this->str = $res->str;
			}

			return false;
		}

		function jsParams( TwatchEntityView $view, $mode ) {
			return parent::jsParams( $view, $mode ).', '.TwatchEntity::REF_TYPE.', '.$this->type;
		}

		function printXml( ArdePrinter $p, $tagName, TwatchEntityView $view, $extraAttrib = '' ) {
			return parent::printXml( $p, $tagName, $view, $extraAttrib.' type_entity_id="'.TwatchEntity::REF_TYPE.'" type_id="'.$this->type.'" ' );
		}

		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			global $twatch;


			$res = EntityVSearchE::getIds( $entityId, $offset, $count, $beginWith );
			if( count( $res ) < $count ) {
				$searchEC = EntityVSearchE::getIdsCount( $beginWith );
				$offset -= $searchEC;
				if( $offset < 0 ) $offset = 0;
				$count -= count( $res );
				$resDomain = TwatchDbrDict::getEntries( $twatch->db, TwatchDict::REF_DOMAIN, $offset, $count, $beginWith, TwatchEntGeneRefGroup::MAX_SEARCHE );
				$res = array_merge( $res, $resDomain );
				if( count( $res ) < $count ) {
					$domainsC = TwatchDbrDict::getIdsCount( $twatch->db, TwatchDict::REF_DOMAIN, $beginWith, TwatchEntGeneRefGroup::MAX_SEARCHE );
					$offset -= $domainsC;
					if( $offset < 0 ) $offset = 0;
					$count -= count( $res );
					$resOthers = self::getArrayIds( array( 1 => 'Other' ), $offset, $count, $beginWith );
					$res = array_merge( $res, $resOthers );
				}
			}
			return $res;
		}


	}

	class EntityVPage extends EntityVSimpleString {
		var $dictId = TwatchDict::PAGE;

		protected $jsClassName = "EntityVPage";

		var $dictStr;
		var $path;
		var $websiteId;

		var $link = false;

		protected function _getForceLtr() {
			return true;
		}

		protected function _getString( $id ) {
			return $this->str;
		}

		public function getLink() {
			return $this->link;
		}

		public function finalizeAttempt2( TwatchDbrDict $dict ) {
			global $twatch;
			if( $res = $dict->getResult( $this->dictId, $this->id ) ) {
				$this->dictStr = $res->str;
				$dashPos = strpos( $this->dictStr, '-' );
				if( $dashPos === false ) return false;
				$this->websiteId = (int)substr( $this->dictStr, 0, $dashPos );
				$this->path = substr( $this->dictStr, $dashPos + 1 );
				try {
					$website = $twatch->config->get( TwatchConfig::WEBSITES, $this->websiteId );
					if( isset( $website->domains[0] ) ) {
						$this->link = 'http://'.$website->domains[0].$this->path;
					} else {
						$this->link = $this->path;
					}
					if( $website->parent ) {

						$this->str = $website->name.': '.$this->path;
					} else {
						$this->str = $this->path;
					}
				} catch( ArdeException $e ) {
					$this->str = 'unknown website '.$this->websiteId.' : '.$this->str;

					return false;
				}

			}
			return false;
		}

		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			global $twatch;

			if( $websiteId == null ) throw new ArdeUserError( 'please select a website to see values of the page entity.' );

			$websites = array();
			$beginWiths = array();

			$website = $twatch->config->get( TwatchConfig::WEBSITES, $websiteId );

			$websites[ $websiteId ] = $beginWith;
			$beginWiths[ $websiteId ] = $websiteId.'-'.$beginWith;

			$subWebsites = array();
			foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $subWebsite ) {
				if( $subWebsite->parent == $websiteId ) {

					$subWebsites[ $subWebsite->getId() ] = $subWebsite->name.': ';

					if( strlen( $subWebsites[ $subWebsite->getId() ] ) < strlen( $beginWith ) ) {
						$subWebsites[ $subWebsite->getId() ] .= $beginWiths[ $subWebsite->getId() ] = substr( $beginWith, strlen( $subWebsites[ $subWebsite->getId() ] ) );
						$beginWiths[ $subWebsite->getId() ] = $subWebsite->getId().'-'.$beginWiths[ $subWebsite->getId() ];
					} else {
						$beginWiths[ $subWebsite->getId() ] = $subWebsite->getId().'-';
					}
				}
			}

			asort( $subWebsites );
			foreach( $subWebsites as $id => $name ) {
				$websites[ $id ] = $name;
			}

			$websiteIds = self::getArrayIds( $websites, 0, -1, $beginWith );


			$prevCount = 0;
			$res = array();
			foreach( $websiteIds as $id ) {
				$offset -= $prevCount;
				if( $offset < 0 ) $offset = 0;
				$websiteRes = TwatchDbrDict::getEntries( $twatch->db, TwatchDict::PAGE, $offset, $count, $beginWiths[ $id ] );
				foreach(  $websiteRes as $entryId ) {
					$res[] = $entryId;
					--$count;
					if( $count == 0 ) return $res;
				}
				$prevCount = TwatchDbrDict::getIdsCount( $twatch->db, TwatchDict::PAGE, $beginWiths[ $id ] );
			}
			return $res;


		}

	}

	class EntityVRef extends EntityVSimpleString {
		const STRING_LINK = 1001;

		var $dictId = TwatchDict::REF;


		protected function _getString( $id ) {
			return $this->str;
		}

		public function getLink() {
			return $this->str;
		}

		protected function _getForceLtr() {
			return true;
		}

		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			global $twatch;
			return TwatchDbrDict::getEntries( $twatch->db, TwatchDict::REF, $offset, $count, $beginWith, TwatchEntGeneProcRef::MAX_KEYWORDS );
		}

		public static function getNoneKeywordIds( $offset, $count, $beginWith = null ) {
			global $twatch;
			return TwatchDbrDict::getEntries( $twatch->db, TwatchDict::REF, $offset, $count, $beginWith, TwatchEntGeneProcRef::MAX_KEYWORDS, false, array( 2 => 0 ) );
		}
	}

	class EntityVAgtStr extends EntityVSimpleString {
		var $dictId = TwatchDict::AGENT;

		protected function _getForceLtr() {
			return true;
		}

		protected function _getString( $id ) {
			return $this->str;
		}

		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			global $twatch;
			return TwatchDbrDict::getEntries( $twatch->db, TwatchDict::AGENT, $offset, $count, $beginWith );
		}
	}

	class EntityVGeneric extends EntityVSimpleString {


		public function __construct( $entityId, $id ) {
			global $twatch;
			parent::__construct( $entityId, $id );
			$this->dictId = $twatch->config->get( TwatchConfig::ENTITIES, $entityId )->gene->dictId;
		}

		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			global $twatch;
			$dictId = $twatch->config->get( TwatchConfig::ENTITIES, $entityId )->gene->dictId;
			return TwatchDbrDict::getEntries( $twatch->db, $dictId, $offset, $count, $beginWith );
		}
	}

	class EntityVSearchE extends EntityV {

		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			global $twatch;
			$a = array();
			foreach( $twatch->config->getList( TwatchConfig::SEARCH_ENGINES ) as $searchE ) {
				$a[ $searchE->id ] = $searchE->name;
			}
			asort( $a );

			return self::getArrayIds( $a, $offset, $count, $beginWith );
		}

		public static function getIdsCount( $beginWith = null ) {
			global $twatch;
			$a = array();
			foreach( $twatch->config->getList( TwatchConfig::SEARCH_ENGINES ) as $searchE ) {
				$a[ $searchE->id ] = $searchE->name;
			}
			asort( $a );
			return self::getArrayIdsCount( $a, $beginWith );
		}
	}

	class EntityVUserAgent extends EntityV {


		public function finalizeAttempt1() {
			global $twatch;

			if( $twatch->config->propertyExists( TwatchConfig::USER_AGENTS, $this->id ) ) {
				$this->str = $twatch->config->get( TwatchConfig::USER_AGENTS, $this->id )->name;
			} else {
				$this->str = 'unknown browser id '.$this->id;
			}
			return false;
		}

		public function hasImage() {
			global $twatch;
			if( $twatch->config->propertyExists( TwatchConfig::USER_AGENTS, $this->id ) ) {
				return $twatch->config->get( TwatchConfig::USER_AGENTS, $this->id )->hasImage;
			}
			return false;
		}

		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			global $twatch;
			$a = array();
			foreach( $twatch->config->getList( TwatchConfig::USER_AGENTS ) as $userAgent ) {
				$a[ $userAgent->id ] = $userAgent->name;
			}
			asort( $a );
			return self::getArrayIds( $a, $offset, $count, $beginWith );
		}
	}




	class EntityVTime extends EntityV {
		protected function getIdParam() {
			return $this->id;
		}
		
		public function finalizeAttempt0() {
			return true;
		}
		
		public function getForceLtr() {
			return false;
		}
		
		protected $jsClassName = "EntityVTime";
		function finalizeAttempt1() {
			global $twatch;

			$t = TwatchTime::getTime( $this->id );

			$dt = array();
			$dt[ 'year' ] = $t->getYear();
			$dt[ 'month' ] = $twatch->locale->text( $t->getMonthLong() );
			$dt[ 'day' ] = $t->getDay();
			$dt[ 'hour' ] = $t->getPaddedHour();
			$dt[ 'minute' ] = $t->getPaddedMinute();
			$dt[ 'second' ] = $t->getPaddedSecond();
			$this->str = $twatch->locale->number( $twatch->locale->text( '{year} {month} {day} {hour}:{minute}:{second}', $dt ) );
			return false;
		}
		
		public function getString( $id ) {
			global $twatch;
			return $twatch->locale->text( $this->str );
		}

	}

	class EntityVPath extends EntityV {

		const PATH_START = 1;
		const PATH_UNKNOWN = 2;
		const PATH_END = 3;
		const PATH_UNKNOWN_PAST = 4;
		const PATH_UNKNOWN_DATA = 5;

		protected function getIdParam() {
			return $this->id;
		}
		
		public function finalizeAttempt0() {
			return true;
		}
		
		public function getForceLtr() {
			return false;
		}
		
		public function getString( $id ) {
			global $twatch;
			return $twatch->locale->text( $this->str );
		}
		
		public static $idString = array(
			 self::PATH_START => 'Visit Start'
			,self::PATH_UNKNOWN => 'Unknown Fate'
			,self::PATH_END => 'Visit End'
			,self::PATH_UNKNOWN_PAST => 'Unknown Past'
			,self::PATH_UNKNOWN_DATA => 'Unknown'
		);

		function finalizeAttempt1() {
			if( isset( self::$idString[ $this->id ] ) ) $this->str = self::$idString[ $this->id ];
			return false;
		}
	}

	class EntityVIp extends EntityV {
		public $domain = 'NS';

		public $cou = null;

		protected $jsClassName = "EntityVIp";

		const STRING_IP = 1000;
		const STRING_DOMAIN = 1001;
		const STRING_DOMAIN_IP = 1002;

		protected function _getForceLtr() {
			global $ardeUser;
			return true;
		}

		protected function _getString( $id ) {
			return $this->str;
		}

		public static function getExtraStringIdNames() {
			return array(
				 self::STRING_IP => 'IP'
				,self::STRING_DOMAIN => 'Domain'
				,self::STRING_DOMAIN_IP => 'Domain (IP)'
			);
		}

		function finalizeAttempt1() {
			return new DictRequest( TwatchDict::IP, $this->id );
		}

		function finalizeAttempt2( TwatchDbrDict $dict ) {
			global $ardeUser, $twatch;
			$res = $dict->getResult( TwatchDict::IP, $this->id );

			if( $res ) {
				$this->str = $res->str;
				if( $res->extra == '' ) {
					$this->domain = null;
				} else {

					$this->domain = $res->extra;
				}
				$this->dictRes( $res );

			}

			return false;
		}

		protected function dictRes( $res ) {}

		function jsParams( TwatchEntityView $view, $mode ) {
			global $ardeUser;
			if( !$ardeUser->user->hasPermission( TwatchUserData::VIEW_IPS ) ) {
				$domain = 'false';
				$cou = 'null';
			} else {
				if( $view->stringId == self::STRING_DOMAIN || $view->stringId == self::STRING_DOMAIN_IP ) {
					if( $this->domain === null ) {
						$domain = 'null';
					} else {
						if( $this->domain == 'unresolved' ) {
							$domain = 'false';
						} else {
							$domain = "'".ArdeJs::escape( $this->domain )."'";
						}
					}
				} else {
					$domain = 'false';
				}

				if( $this->cou === null ) {
					$cou = 'null';
				} else {
					$cou = $this->cou->jsObject( new TwatchEntityView( true, true, false, EntityV::STRING_DEFAULT, 0 ), EntityV::JS_MODE_INLINE );
				}
			}
			return parent::jsParams( $view, $mode ).', '.$domain.', '.$view->stringId.', '.$cou;
		}

		function printXml( ArdePrinter $p, $tagName, TwatchEntityView $view, $extraAttrib = '' ) {
			global $ardeUser;
			if( !$ardeUser->user->hasPermission( TwatchUserData::VIEW_IPS ) ) {
				$domain = '';
			} else {
				if( $view->stringId == self::STRING_DOMAIN || $view->stringId == self::STRING_DOMAIN_IP ) {
					if( $this->domain === null ) {
						$domain = ' resolve="true"';
					} else {
						if( $this->domain == 'unresolved' ) {
							$domain = '';
						} else {
							$domain = ' domain="'.ardeXmlEntities( $this->domain ).'"';
						}
					}
				} else {
					$domain = '';
				}
			}
			return parent::printXml( $p, $tagName, $view, $extraAttrib.$domain.' string_id="'.$view->stringId.'" ' );
		}

		protected function printExtraXml( ArdePrinter $p ) {
			global $ardeUser;
			if( !$ardeUser->user->hasPermission( TwatchUserData::VIEW_IPS ) || $this->cou === null ) return;
			$this->cou->printXml( $p, 'country', new TwatchEntityView( true, true, false, EntityV::STRING_DEFAULT, 0 ), '' );
			$p->nl();
		}

		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			global $twatch;
			return TwatchDbrDict::getEntries( $twatch->db, TwatchDict::IP, $offset, $count, $beginWith );
		}
	}

	class EntityVHour extends EntityV {

		const STRING_NUMBER_12_AM = 1000;
		const STRING_NUMBER_12_AM_PAD = 1001;
		const STRING_NUMBER_12 = 1002;
		const STRING_NUMBER_12_PAD = 1003;
		const STRING_NUMBER_24 = 1004;
		const STRING_NUMBER_24_PAD = 1005;
		const STRING_INTERVAL = 1006;

		public static function getExtraStringIdNames() {
			return array(
				 self::STRING_NUMBER_12 => '12 Hours'
				,self::STRING_NUMBER_12_AM => '12 Hours with AM/PM'
				,self::STRING_NUMBER_12_PAD => 'Padded 12 Hours'
				,self::STRING_NUMBER_12_AM_PAD => 'Padded 12 Hours with AM/PM'
				,self::STRING_NUMBER_24 => '24 Hours'
				,self::STRING_NUMBER_24_PAD => 'Padded 24 Hours'
				,self::STRING_INTERVAL => 'Interval'
			);
		}

		function finalizeAttempt1() {
			$this->str = $this->getString( self::STRING_NUMBER_12_AM );
			return false;
		}


		protected function _getString( $id ) {
			if( $this->id < 1 || $this->id > 24 ) return '!invalid hour '.$this->id.'!';
		 	if( $id == self::STRING_NUMBER_24 ) {
				return $this->getNumber( false, false, false );
			} elseif( $id == self::STRING_NUMBER_24_PAD ) {
				return $this->getNumber( false, false, true );
			} elseif( $id == self::STRING_NUMBER_12 ) {
				return $this->getNumber( true, false, false );
			} elseif( $id == self::STRING_NUMBER_12_PAD ) {
				return $this->getNumber( true, false, true );
			} elseif( $id == self::STRING_NUMBER_12_AM ) {
				return $this->getNumber( true, true, false );
			} elseif( $id == self::STRING_NUMBER_12_AM_PAD ) {
				return $this->getNumber( true, true, true );
			} elseif( $id == self::STRING_INTERVAL ) {
				return ( $this->id - 1 ).':00 - '.( ( $this->id == 24 ) ? 0 : $this->id ).':00';
			} else {
				return parent::_getString( $id );
			}
		}

		private function getNumber( $twelve, $am, $pad ) {
			global $twatch;
			$n = $this->id - 1;
			if( $twelve ) if( $n > 12 ) $n -= 12;
			if( $pad && $n < 10 ) $n = '0'.$n;
			if( $am ) {
				if( $this->id < 13 ) $n .= ' '.$twatch->locale->text( 'AM' );
				else $n .= ' '.$twatch->locale->text( 'PM' );
			}
			return (string)$n;
		}

		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			$a = array();
			for( $i = 1; $i < 25; ++$i ) {
				$a[ $i ] = (string) ($i - 1);
			}
			return self::getArrayIds( $a, $offset, $count, $beginWith );
		}
	}


	class EntityVBool extends EntityV {
		const STRING_TRUE_FALSE = 1000;
		const STRING_YES_NO = 1001;
		const STRING_ON_OFF = 1002;

		public static function getExtraStringIdNames() {
			return array(
				 self::STRING_TRUE_FALSE => 'True/False'
				,self::STRING_YES_NO => 'Yes/No'
				,self::STRING_ON_OFF => 'On/Off'
			);
		}

		public function finalizeAttempt1() {
			if( $this->id == TwatchEntGeneBool::TRUE ) {
				$this->str = 'True';
			} elseif( $this->id == TwatchEntGeneBool::FALSE ) {
				$this->str = 'False';
			}
			return false;
		}

		protected function _getString( $id ) {
			if( $id == self::STRING_YES_NO ) {
				if( $this->id == TwatchEntGeneBool::TRUE ) {
					$this->str = 'Yes';
				} elseif( $this->id == TwatchEntGeneBool::FALSE ) {
					$this->str = 'No';
				} else {
					$this->str = '!InvalidBool!';
				}
			} elseif( $id == self::STRING_ON_OFF ) {
				if( $this->id == TwatchEntGeneBool::TRUE ) {
					$this->str = 'On';
				} elseif( $this->id == TwatchEntGeneBool::FALSE ) {
					$this->str = 'Off';
				} else {
					$this->str = '!InvalidBool!';
				}
			} else {
				return parent::_getString( $id );
			}
		}

		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			return self::getArrayIds( array( TwatchEntGeneBool::FALSE => 'False', TwatchEntGeneBool::TRUE => 'True' ), $offset, $count, $beginWith );
		}

	}

	class EntityVError extends EntityV {
		public function finalizeAttempt1() {
			$this->str = 'Unknown entity '.$this->entityId.'-'.$this->id;
			return false;
		}
	}

	class EntityVNull extends EntityV {

		public function finalizeAttempt1() {
			global $twatch;
			if( $twatch->config->propertyExists( TwatchConfig::ENTITIES, $this->entityId ) ) {
				$this->str = $twatch->config->get( TwatchConfig::ENTITIES, $this->entityId )->gene->value;
			}
			return false;
		}

		protected function _getString( $id ) {
			global $twatch;
			if( $id == self::STRING_NO_TITLE || $id == self::STRING_SELECT ) {
				return $twatch->locale->text( 'Exists' );
			}
			return parent::_getString( $id );
		}

		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			return self::getArrayIds( array( TwatchEntGeneNull::EXISTS => 'Exists' ), $offset, $count, $beginWith );
		}

	}

	class EntityVWeekday extends EntityV {
		private static $days = array('','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
		private static $daysShort = array('','Sun','Mon','Tue','Wed','Thu','Fri','Sat');

		function finalizeAttempt1() {
			if( isset( self::$days[ $this->id ] ) ) {
				$this->str = self::$days[ $this->id ];
			}
			return false;
		}

		protected function _getString( $id ) {
			if( !isset( self::$days[ $this->id ] ) ) return parent::getString( $id );
			if( $id == self::STRING_SHORT ) {
				return self::$daysShort[ $this->id ];
			} elseif( $id == self::STRING_LONG ) {
				return self::$days[ $this->id ];
			} else {
				return parent::_getString( $id );
			}
		}

		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			$a = array();
			for( $i = 1; $i < 8; ++$i ) {
				$a[ $i ] = self::$days[ $i ];
			}
			return self::getArrayIds( $a, $offset, $count, $beginWith );
		}
	}

	class EntityVCookie extends EntityV {
		function finalizeAttempt1() {
			global $ardeUser;
				$this->str = $this->id;
			return false;
		}



		public static function getIds( $entityId, $offset, $count, $beginWith = null, $websiteId = null ) {
			return array();
		}
	}

	class TwatchEntityView implements ArdeSerializable {

		const EV_DEF = 1;
		const EV_DEF_IMG = 2;
		const EV_IMG = 3;
		const EV_HOUR_LONG = 1;
		const EV_HOUR_SHORT = 2;
		const EV_WEEKDAY_LONG = 1;
		const EV_WEEKDAY_SHORT = 2;
		const EV_REFS_IMG_TYPE = 1;

		var $showText;
		var $showImage;
		var $link;
		var $stringId;
		var $trimString;
		var $forceLtr;

		function TwatchEntityView( $showText, $showImage, $link, $stringId = EntityV::STRING_DEFAULT, $trimString = 0, $forceLtr = null ) {
			$this->showText = $showText;
			$this->showImage = $showImage;
			$this->link = $link;
			$this->stringId = $stringId;
			$this->trimString = $trimString;
			$this->forceLtr = $forceLtr;
		}


		public static function InitJs( ArdePrinter $p ) {
			global $twatch;
			$strings = new ArdeAppender( ', ' );
			foreach( EntityV::$stringIdNames as $id => $name ) {
				$strings->append( $id.": '".ArdeJs::escape( $name )."'" );
			}
			$p->pl( 'EntityView.strings = {' );
			$p->pl( '	 0 : { '.$strings->s.' }' );
			$p->hold( 1 );
			foreach( $twatch->config->getList( TwatchConfig::ENTITIES ) as $entityId => $entity ) {
				$res = call_user_func( array( $entity->gene->valueClassName, 'getExtraStringIdNames' ) );
				if( $res !== null ) {
					$strings = new ArdeAppender( ', ' );
					foreach( $res as $id => $name ) {
						$strings->append( $id.": '".ArdeJs::escape( $name )."'" );
					}
					$p->pl( ','.$entityId.': { '.$strings->s.' }' );
				}
			}
			$p->rel();
			$p->pl( '};' );
		}

		public function getSerialData() {
			return new ArdeSerialData( 2, array( $this->showText, $this->showImage, $this->link, $this->stringId, $this->trimString, $this->forceLtr ) );
		}

		public function printXml( ArdePrinter $p, $tagName, $extraAttrib = '' ) {
			if( $this->forceLtr === null ) $forceLtr = '';
			else $forceLtr =  ' force_ltr="'.($this->forceLtr?'true':'false').'"';
			$p->pn( '<'.$tagName.' text="'.($this->showText?'true':'false').'" image="'.($this->showImage?'true':'false').'" link="'.($this->link?'true':'false').'" string_id="'.$this->stringId.'"'.$extraAttrib.' trim_string="'.$this->trimString.'"'.$forceLtr.' />' );
		}

		public static function makeSerialObject( ArdeSerialData $d ) {
			if( $d->version == 1 ) $forceLtr = false;
			else $forceLtr = $d->data[5];
			return new self( $d->data[0], $d->data[1], $d->data[2], $d->data[3], $d->data[4], $forceLtr );
		}
		public static function fromParams( $a, $prefix = '' ) {
			$text = ArdeParam::bool( $a, $prefix.'t' );
			$image = ArdeParam::bool( $a, $prefix.'i' );
			$link = ArdeParam::bool( $a, $prefix.'l' );
			$stringId = ArdeParam::str( $a, $prefix.'si' );
			$trimString = ArdeParam::str( $a, $prefix.'ts' );
			if( !isset( $a[ $prefix.'fl' ] ) ) {
				$forceLtr = null;
			} else {
				$forceLtr = ArdeParam::bool( $a, $prefix.'fl' );
			}
			return new self( $text, $image, $link, $stringId, $trimString, $forceLtr );
		}

		public function isEquivalent( self $ev ) {
			if( $this->showText != $ev->showText ) return false;
			if( $this->showImage != $ev->showImage ) return false;
			if( $this->link != $ev->link ) return false;
			if( $this->stringId != $ev->stringId ) return false;
			if( $this->trimString != $ev->trimString ) return false;
			if( $this->forceLtr != $ev->forceLtr ) return false;
			return true;
		}

		function adminJsObject() {
			return 'new EntityView( '.($this->showText?'true':'false').', '.($this->showImage?'true':'false').', '.($this->link?'true':'false').', '.$this->stringId.', '.$this->trimString.', '.( $this->forceLtr===null ? 'null' : ArdeJs::bool( $this->forceLtr ) ).' )';
		}
		function jsObject() {
			return $this->adminJsObject();
		}
	}
	$twatch->applyOverrides( array( 'EntityVIp' => true ) );
?>