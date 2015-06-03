<?php
	require_once $twatch->path( 'db/DbPassiveDict.php' );

	abstract class TwatchEntityPassiveGene {
		const ERROR_ALREADY_EXISTS = 1;
		const ERROR_BAD_USER_STRING = 2;

		const MODE_NORMAL = 0;
		const MODE_ADD_ONLY = 1;
		const MODE_READ_ONLY = 2;

		const CONTEXT_IMPORT = 1;
		const CONTEXT_EXPLICIT_ADD = 2;
		const CONTEXT_API = 3;

		public $dict;

		public $mode;
		public $context;

		public function __construct( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			$this->dict = $dict;
			$this->mode = $mode;
			$this->context = $context;
		}

		public function getStringEntityVId( $string, TwatchWebsite $website = null  ) {
			throw new TwatchException( 'not implemented' );
		}

	}

	class TwatchEntDictPasvGene extends TwatchEntityPassiveGene {
		protected $dictId;

		public function __construct( $dictId, TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			parent::__construct( $dict, $mode, $context );
			$this->dictId = $dictId;
		}

		public function getStringEntityVId( $string, TwatchWebsite $website = null  ) {
			$string = $this->prepString( $string, $website );
			if( $string === false ) return false;
			return $this->getStringDictId( $string );
		}

		protected function prepString( $string, TwatchWebsite $website = null  ) {
			return $string;
		}

		protected function getStringDictId( $string ) {
			$id = $this->dict->getId( $this->dictId, $string );
			if( $id === false ) {
				if( $this->mode == self::MODE_READ_ONLY ) return false;
				return $this->dict->putString( $this->dictId, $string, array(), '', null, 0 );
			} elseif( $this->mode == self::MODE_ADD_ONLY ) {
				throw new TwatchUserError( 'Already Exists', 1 );
			}
			return $id;
		}


	}

	class TwatchEntIpPasvGene extends TwatchEntDictPasvGene {

		protected function prepString( $string, TwatchWebsite $website = null  ) {
			$ipStr = ardeGetIp( $string );
			if( $ipStr === false ) throw new ArdeUserError( "invalid Ip address '".$string."'", self::ERROR_BAD_USER_STRING );
			return $ipStr;
		}

		protected function getStringDictId( $string ) {
			$id = $this->dict->getId( $this->dictId, $string );
			if( $id === false ) {
				if( $this->mode == self::MODE_READ_ONLY ) return false;
				$ipId = ardeIpToU32( $string );
				return $this->dict->putString( $this->dictId, $string, array(), '', $ipId, 0 );
			} elseif( $this->mode == self::MODE_ADD_ONLY ) {
				throw new TwatchException( 'Already Exists', 1 );
			}
			return $id;
		}

	}

	class TwatchEntPagePasvGene extends TwatchEntDictPasvGene {
		public function __construct( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			parent::__construct( TwatchDict::PAGE, $dict, $mode, $context );
		}
		protected function prepString( $string, TwatchWebsite $website = null ) {
			return $website->getId().'-'.$string;
		}
	}

	class TwatchEntSeKeywordPasvGene extends TwatchEntityPassiveGene {
		public function __construct( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			parent::__construct( $dict, $mode, $context );
		}

		public function getStringEntityVId( $string, TwatchWebsite $website = null  ) {
			if( !preg_match( '/^(.+)\ssearch\sfor\s\[(.+)\]$/i', $string, $matches ) ) return false;
			$seGene = new TwatchEntRefGroupPasvGene( $this->dict, $this->mode, $this->context );
			$seId = $seGene->getStringEntityVId( 'search engine: '.$matches[1], $website );
			if( $seId === false ) return false;

			$id = $this->dict->getId( TwatchDict::SE_KEYWORD, $seId.'-'.$matches[2] );
			if( $id === false ) {
				if( $this->mode == self::MODE_READ_ONLY ) return false;
				return $this->dict->putString( TwatchDict::SE_KEYWORD, $seId.'-'.$matches[2], array( $seId ), '', null, 0 );
			} elseif( $this->mode == self::MODE_ADD_ONLY ) {
				throw new TwatchUserError( 'Already Exists', 1 );
			}
			return $id;

		}

	}

	class TwatchEntProcRefPasvGene extends TwatchEntityPassiveGene {
		public function getStringEntityVId( $string, TwatchWebsite $website = null  ) {
			$seKeyGene = new TwatchEntSeKeywordPasvGene( $this->dict, $this->mode, $this->context );
			$id = $seKeyGene->getStringEntityVId( $string, $website );
			if( $id !== false ) return $id;
			$refGene = new TwatchEntRefPasvGene( $this->dict, $this->mode, $this->context, true );
			return $refGene->getStringEntityVId( $string, $website );
		}
	}

	class TwatchEntRefPasvGene extends TwatchEntityPassiveGene {
		protected $forProcRef;
		public function __construct( TwatchDbPassiveDict $dict, $mode = 0, $context = 0, $forProcRef = false ) {
			parent::__construct( $dict, $mode, $context );
			$this->forProcRef = $forProcRef;
		}

		public function getStringEntityVId( $string, TwatchWebsite $website = null  ) {

			$id = $this->dict->getId( TwatchDict::REF, $string );
			if( $id === false ) {
				if( $this->mode == self::MODE_READ_ONLY ) return false;
				if( $this->forProcRef ) {
					$domain = TwatchEntGeneRefGroup::getDomain( $string );
					if( $domain === null ) {
						$groupId = TwatchEntGeneRefGroup::NONE;
					} else {
						$groupGene = new TwatchEntRefGroupPasvGene( $this->dict, $this->mode, $this->context );
						$groupId = $groupGene->getStringEntityVId( 'domain: '.$domain, $website );
						if( !$groupId ) $groupId = 0;
					}
				} else {
					$groupId = 0;
				}
				return $this->dict->putString( TwatchDict::REF, $string, array( $groupId ), '', null, 0 );
			} elseif( $this->mode == self::MODE_ADD_ONLY ) {
				throw new TwatchUserError( 'Already Exists', 1 );
			}
			return $id;

		}
	}

	class TwatchEntRefGroupPasvGene extends TwatchEntDictPasvGene {
		protected $searchENameIds = array();

		public function __construct( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			global $twatch;
			parent::__construct( TwatchDict::REF_DOMAIN, $dict, $mode, $context );
			foreach( $twatch->config->getList( TwatchConfig::SEARCH_ENGINES ) as $id => $searchEngine ) {
				$this->searchENameIds[ strtolower( $searchEngine->name ) ] = $id;
			}

		}

		public function getStringEntityVId( $string, TwatchWebsite $website = null  ) {
			if( !preg_match( '/^(.+?)\s?(?:\:\s?(.+)|)$/', $string, $matches ) ) {
				return false;
			}
			if( strtolower( $matches[1] ) == 'search engine' ) {
				if( isset( $this->searchENameIds[ strtolower( $matches[2] ) ] ) ) {
					return $this->searchENameIds[ strtolower( $matches[2] ) ];
				} else {
					return false;
				}
			} elseif( strtolower( $matches[1] ) == 'domain' ) {
				return $this->getStringDictId( $matches[2] );
			} elseif( strtolower( $matches[1] ) == 'other' ) {
				return TwatchEntGeneRefGroup::NONE;
			}
			return false;
		}
	}

	class TwatchEntHourPasvGene extends TwatchEntityPassiveGene {
		public function getStringEntityVId( $string, TwatchWebsite $website = null  ) {
			$num = (int)$string;
			if( $num >= 0 && $num < 24 ) {
				return $num + 1;
			}
			return false;
		}
	}

	class TwatchEntWeekdayPasvGene extends TwatchEntityPassiveGene {
		public static $dayIds = array(
			'sunday' => 1, 'monday' => 2, 'tuesday' => 3, 'wednesday' => 4, 'thursday' => 5, 'friday' => 6, 'saturday' => 7
		);

		public function getStringEntityVId( $string, TwatchWebsite $website = null  ) {
			if( isset( self::$dayIds[ strtolower( $string ) ] ) ) {
				return self::$dayIds[ strtolower( $string ) ];
			}
			return false;
		}
	}

	class TwatchEntBoolPasvGene extends TwatchEntityPassiveGene {

		public function getStringEntityVId( $string, TwatchWebsite $website = null  ) {
			$string = strtolower( $string );
			if( $string == 'true' || $string == 'on' || $string == 'yes' ) {
				return TwatchEntGeneBool::TRUE;
			}
			if( $string == 'false' || $string == 'off' || $string == 'no' ) {
				return TwatchEntGeneBool::FALSE;
			}
			return false;
		}
	}

	class TwatchEntCookiePasvGene extends TwatchEntityPassiveGene {

		public function getStringEntityVId( $string, TwatchWebsite $website = null  ) {
			if( preg_match( '/^\d+$/', $string ) ) {
				return (int)$string;
			}
			return false;
		}
	}

	class TwatchEntRefTypePasvGene extends TwatchEntityPassiveGene {

		public static $idStrings = array(
			 'other' => TwatchEntGeneRefType::NONE
			,'search engine' => TwatchEntGeneRefType::SEARCHE
			,'url' => TwatchEntGeneRefType::URL
		);
		public function getStringEntityVId( $string, TwatchWebsite $website = null  ) {
			$string = strtolower( $string );
			if( isset( self::$idStrings[ $string ] ) ) {
				return self::$idStrings[ $string ];
			}
			return false;
		}
	}

	class TwatchEntUserAgentPasvGene extends TwatchEntityPassiveGene {
		protected $userAgentNameIds = array();

		public function __construct( TwatchDbPassiveDict $dict, $mode = 0, $context = 0 ) {
			global $twatch;
			parent::__construct( $dict, $mode, $context );
			foreach( $twatch->config->getList( TwatchConfig::USER_AGENTS ) as $id => $userAgent ) {
				$name = strtolower( $userAgent->name );
				if( preg_match( '/^(.+)\srobot$/', $name, $matches ) ) $name = $matches[1];
				$this->userAgentNameIds[ $name ] = $id;
			}
		}

		public function getStringEntityVId( $string, TwatchWebsite $website = null  ) {
			$string = strtolower( $string );
			if( preg_match( '/^(.+)\srobot$/', $string, $matches ) ) $string = $matches[1];
			if( isset( $this->userAgentNameIds[ $string ] ) ) {
				return $this->userAgentNameIds[ $string ];
			} else {
				return false;
			}
		}
	}

	$twatch->includePosition( 'passive_gene' );
?>