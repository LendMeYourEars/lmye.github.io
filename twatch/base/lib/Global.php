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
    
	
	abstract class ArdeApp {
		
		public $settings = array();
		public $profile;
		
		
		public function loadSettings( $profile ) {
			$settings = &$this->settings;
			include( $this->path( 'profiles/'.$profile.'/settings.php' ) );
			$this->profile = $profile;
		}
		
		public function getProfiles() {
			$o = array();
			$dir = opendir( $this->path( 'profiles' ) );
			while( $file = readdir( $dir ) ) {
				$settings = array();
				if( $file == '.' || $file == '..' || $file[0] == '_' ) continue;
				include( $this->path( 'profiles/'.$file.'/settings.php' ) );
				if( isset( $settings[ 'profile_name' ] ) ) {
					if( $settings[ 'profile_name' ] == 'Default' && $file != 'default' )  $o[ $file ] = $file;
					else $o[ $file ] = $settings[ 'profile_name' ];
				} else {
					$o[ $file ] = $file;
				}
			}
			return $o;
		}
		
		public function profileName() {
			if( isset( $this->settings[ 'profile_name' ] ) ) return $this->settings[ 'profile_name' ];
			return $this->profile;
		}
		
		abstract public function path( $target );
		
		public function makeInstanceId() {
			return abs( crc32( md5( ArdeTime::getMicrotime().mt_rand( 0, 0x7fffffff ).$this->settings[ 'salt' ].$this->name.$this->profile ) ) );
		}
		
	}
	
	class ArdeBaseApp extends ArdeApp {
		
		public $version = '0.1';
		
		public function appPath( $appName, $target ) {
			return dirname(__FILE__).'/../'.$this->settings[ 'paths' ][ $appName ].'/'.$target;
		}
		
		public function path( $target ) {
			return dirname(__FILE__).'/../'.$target;
		}
		
		public function instantiateApp( $appName, $appProfile = 'default' ) {
			require_once( $this->appPath( $appName, 'lib/Global.php' ) );
		}	
	}
	
	abstract class ArdeNoneBaseApp extends ArdeApp {
		public $name;
		
		public $base;
		
		public function __construct( ArdeBaseApp $base ) {
			$this->base = $base;
			$this->settings = $base->settings;
		}
		
		
		
		public function url( $toRoot, $target ) {
			return ardeSlashConcat( $toRoot, $target );
		}
		
		public function baseUrl( $toRoot, $target ) {
			return $this->extUrl( $toRoot, 'base', $target );
		}
		
		
		public function extUrl( $toRoot, $extName, $target ) {
			if( isset( $this->settings[ $extName.'_url' ] ) ) {
				$address = $this->settings[ $extName.'_url' ];
			} else {
				$address = $this->settings[ 'to_'.$extName ];
			}

			if( preg_match( '/^http\:\/\//', $address ) ) {
				return $address.'/'.$target;
			} else {
				return ardeSlashConcat( $toRoot, $address, $target );
			}

		}
		
		public function path( $target ) {
			ardeSlashConcat( dirname(__FILE__).'/..', $target );
		}
		
		public static function getExtPath( $rootPath, $toExt, $target ) {
			if( preg_match( '/^(\w\:|\/)/', $toExt ) ) {
				return $toExt.'/'.$target;
			} else {
				return ardeSlashConcat( $rootPath, $toExt, $target );
			}
		}
		
		public function extPath( $extName, $target ) {
			return self::getExtPath( dirname(__FILE__).'/..', $this->settings[ 'to_'.$extName ], $target );
			
		}

	}
	
	class ArdeLocale {
		const DIGIT_ENGLISH = 1;
		const DIGIT_PERSIAN = 2;
		const DIGIT_ARABIC = 3;
		
		public $revision = '0.0';
		
		public $name = 'English';
		
		public $translatorName = '';
		public $translatorLink = '';
		
		public $rightToLeft = false;
		public $texts = array();
		public $digits = self::DIGIT_ENGLISH;
		
		public $id = 'English';
		
		public $xmlLangCode = 'en-US';
		
		public function text( $text, $replacements = null ) {
			if( isset ( $this->texts[ $text ] ) && $this->texts[ $text ] != '' ) {
				$res = $this->texts[ $text ];
			} else {
				$res = $text;
			}
			
			if( $replacements !== null ) {
				foreach( $replacements as $key => $value ) {
					$index = strpos( $res, '{'.$key.'}' );
					if( $index >= 0 ) {
						$res = substr( $res, 0, $index ).$value.substr( $res, $index + strlen( $key ) + 2 );
					}
				}
			}
			
			return $this->number( $res );
			
		}
		
		public function number( $n ) {
			if( $this->digits == self::DIGIT_ENGLISH ) return $n;
			
			$n=(string)$n;
	
			$o = '';
			for( $i = 0; $i < strlen($n); ++$i ) {
				if( ord($n[$i]) >= 0x30 && ord($n[$i]) <= 0x39 ) {
					if( $this->digits == self::DIGIT_PERSIAN ) {
						$o .= ardeUcs4ToUtf8( 0x6f0 + $n[$i] );
					} else {
						$o .= ardeUcs4ToUtf8( 0x660 + $n[$i] );
					}
				} else {
					$o .= $n[$i];
				}
			}
	
			return $o;
		}
		
		public function jsObject( $texts = array(), $defaultId ) {
			$textsA = new ArdeAppender( ', ' );
			foreach( $texts as $text ) {
				if( isset( $this->texts[ $text ] ) && $this->texts[ $text ] != '' ) {
					$textsA->append( "'".ArdeJs::escape( $text )."': '".ArdeJs::escape( $this->texts[ $text ] )."'" );
				}
			}
			return "new ArdeLocale( '".$this->id."', '".$defaultId."', ".ArdeJs::bool( $this->rightToLeft ).', '.$this->digits.', { '.$textsA->s.' } )';
		}
		
		
	}
	
	class ArdeLocaleReplacer {
		private $replacements;

		public function __construct( $replacements ) {
			$this->replacements = $replacements;
		}
		
		public function replace( $matches ) {
			if( isset( $this->replacements[ $matches[1] ] ) ) {
				return $this->replacements[ $matches[1] ];
			} else {
				return $matches[0];
			}
		}
	}
	
	abstract class ArdeAppWithPlugins extends ArdeNoneBaseApp {
		public $plugins = array();
		
		public $classNames = array();
		
		public $functionNames = array();
		
		public $functionOriginals = array();
		
		public $locale;
		
		public $defaultLocale;
		
		public function __construct( ArdeBaseApp $base ) {
			parent::__construct( $base );
			$this->setDefaultLocale();
			$this->locale = $this->defaultLocale;
		}
		
		protected function setDefaultLocale() {
			$this->defaultLocale = new ArdeLocale();
		}
		
		public function localeExists( $name ) {
			if( $name == 'template' ) return false;
			if( $name == $this->defaultLocale->id ) return true;
			return file_exists( $this->path( 'locale/'.$name.'.php' ) );
		}
		
		public function loadLocale( $name ) {
			if( $name == $this->defaultLocale->id ) {
				$this->locale = $this->defaultLocale;
				return;
			}
			$locale = new ArdeLocale();
			$locale->id = $name;
			include $this->path( 'locale/'.$name.'.php' );
			
			if( file_exists( $this->path( 'profiles/'.$this->profile.'/extend'.$name.'.php' ) ) ) {
				include $this->path( 'profiles/'.$this->profile.'/extend'.$name.'.php' );
				if( isset( $texts ) ) {
					foreach( $texts as $key => $value ) {
						$locale->texts[ $key ] = $value;
					}
				}
			}
			
			$this->locale = $locale;
			
		}
		
		public function getLocaleIds() {
			$dir = opendir( $this->path( 'locale' ) );
			$o = array();
			while( $f = readdir( $dir ) ) {
				if( preg_match( '/^(.+)\.php$/', $f, $matches ) ) {
					if( $matches[1] == 'template' ) continue;
					$o[] = $matches[1];
				}
			}
			$o[] = $this->defaultLocale->id;
			sort( $o );
			return $o;
		}
		
		protected function getPluginClassName() {
			return 'ArdePlugin';
		}
		
		public function loadPlugins() {
			$pluginsDirPath = 'profiles/'.$this->profile.'/plugins';
			$dir = opendir( $this->path( $pluginsDirPath ) );
			while( $fileName = readdir( $dir ) ) {
				if( $fileName == '.' || $fileName == '..' || $fileName[0] == '_' ) continue;
				if( is_dir( $this->path( $pluginsDirPath.'/'.$fileName ) ) ) {
					if( !preg_match( '/^(\d+)_/', $fileName, $matches ) ) {
						trigger_error( "plugin folder name should be in id_name format '".$fileName."' is not acceptable, ignored.", E_USER_WARNING );
						continue;
					}
					
					$pluginId = (int)$matches[1];
					
					if( $pluginId < 1 ) {
						trigger_error( 'plugin id can not be less than 1', E_USER_WARNING );
						continue;
					}
					if( $pluginId > 255 ) {
						trigger_error( 'plugin id can not be greater than 255', E_USER_WARNING );
						continue;
					}
					
					if( isset( $this->plugins[ $pluginId ] ) ) {
						trigger_error( 'there are two plugins with the same id '.$pluginId.', second one ignored', E_USER_WARNING );
						continue;
					}

					$pluginObject = $this->getPluginObject( $this->path( $pluginsDirPath.'/'.$fileName.'/Main.php' ) );
					if( $pluginObject !== null ) {
						$pluginObject->id = $pluginId;
						$pluginObject->app = $this;
						$pluginObject->profile = $this->profile;
						$pluginObject->folderName = $fileName;
						$pluginObject->folder = $this->path( $pluginsDirPath.'/'.$fileName.'/' );
						$pluginObject->init();
						$this->plugins[ $pluginId ] =  $pluginObject;
					}
				}
			}
			ksort( $this->plugins );
		}
		
		public function getPluginObject( $mainFile ) {
			include $mainFile;
			
			if( !isset( $pluginObject ) ) {
				trigger_error( "plugin doesn't define \$pluginObject in ".$mainFile, E_USER_WARNING );
				return null;
			}
			$className = $this->getPluginClassName();
			if( !$pluginObject instanceof $className ) {
				trigger_error( "\$pluginObject is not an instance of ArdePlugin in ".$mainFile, E_USER_WARNING );
				return null;
			}
			
			return $pluginObject;
			
		}
		
		
		public function applyOverrides( $originalNames ) {
			$files = array();
			foreach( $this->plugins as $plugin ) {
				foreach( $plugin->getOverrides() as $originalName => $override ) {
					if( !isset( $originalNames[ $originalName ] ) ) continue;
					$files[ $override->file ][ $originalName ] = $override;
				}
			}
			foreach( $files as $file => $overrides ) {
				foreach( $overrides as $originalName => $override ) {
					
					if( $override instanceof ArdeClassOverride ) {
						$this->makeParentClass( $override->newName, $originalName );
						
					}
				}
				include_once $file;
				
				foreach( $overrides as $originalName => $override ) {
					if( $override instanceof ArdeClassOverride ) {
						$this->classNames[ $originalName ] = $override->newName;
					} else {
						if( isset( $this->functionNames[ $originalName ] ) ) {
							$this->functionOriginals[ $override->newName ] = $this->functionNames[ $originalName ];
						} else {
							$this->functionOriginals[ $override->newName ] = $originalName;
						}
						$this->functionNames[ $originalName ] = $override->newName;
					}
				}
				
			}
		}
	
		
		public function makeParentClass( $derrivedName, $originalParentName ) {
			$s = 'class '.$derrivedName.'Parent extends '.$this->className( $originalParentName ).' {}';
			eval( $s );
		}
		
		public function includePosition( $position ) {
			foreach( $this->plugins as $plugin ) {
				$path = $plugin->getInclude( $position ); 
				if( $path !== null ) require_once( $path );
			}
		}

		public function className( $name ) {
			if( !isset( $this->classNames[ $name ] ) ) {
				return $name;
			}
			return $this->classNames[ $name ]; 
		}
		
		public function functionName( $name ) {
			if( !isset( $this->functionNames[ $name ] ) ) {
				return $name;
			}
			return $this->functionNames[ $name ];
		}
		
		public function makeObject( $className ) {	
			$cls = new ReflectionClass( $this->className( $className ) );
			$args = array_slice( func_get_args(), 1 );
			if( method_exists( $cls, 'newInstanceArgs' ) ) {
				return $cls->newInstanceArgs( $args );
			} else {
				return call_user_func_array( array( $cls, 'newInstance' ), $args );
			}
		}
		
		public function callStatic( $className, $methodName ) {
			$className = $this->className( $className );
			$args = array_slice( func_get_args(), 2 );
			return call_user_func_array( array( $className, $methodName ), $args );
		}
		
		public function callFunction( $functionName ) {
			$functionName = $this->functionName( $functionName );
			$args = array_slice( func_get_args(), 1 );
			return call_user_func_array( $functionName, $args );
		}
		
		public function callOriginalFunction( $functionName ) {
			$functionName = $this->functionOriginals[ $functionName ];
			$args = array_slice( func_get_args(), 1 );
			return call_user_func_array( $functionName, $args );
		}
	}
	
	class ArdePlugin {
		
		public $folder;
		public $folderName;
		public $app;
		public $profile;
		
		public $id;
		
		public static $object = null;
		
		public function init() {}
		
		public function getName() {
			return 'Anonymous';
		}
		
		public function getVersion() {
			return '0.0';
		}
		
		public function getOverrides() {
			return array();
		}
		
		
		
		public function needsInstall() {
			return false;
		}
		
		public function needsUninstall() {
			return false;
		}
		
		public function install( ArdePrinter $p ) {
			$p->pl( '<div class="block" style="text-align:center;font-weight:bold"><p><span class="fixed">Nothing to do.</span></p></div>' );
			
		}
		
		public function hasInstallForm() { return false; }
		
		public function printInInstallForm( ArdePrinter $p ) {}
		
		public function hasUninstallForm() { return false; }
		
		public function printInUninstallForm( ArdePrinter $p ) {}
		
		public function uninstall( ArdePrinter $p ) {}
		
		public function update( ArdePrinter $p ) {
			$p->pl( '<div class="block" style="text-align:center;font-weight:bold"><p><span class="fixed">Nothing to do.</span></p></div>' );
		}
		
		public function getInclude( $position ) {
			return null;
		}
		
		public function path( $target ) {
			return $this->folder.$target;
		}
		
		public function url( $toRoot, $target ) {
			return $this->app->url( $toRoot, 'profiles/'.$this->profile.'/plugins/'.$this->folderName.'/'.$target );
		}
	}
	
	class ArdeOverride {
		public $newName;
		public $file;
		
		public function __construct( $newName, $file ) {
			$this->newName = $newName;
			$this->file = $file;
		}
		
		
	}
	
	class ArdeClassOverride extends ArdeOverride {}

	class ArdeFunctionOverride extends ArdeOverride {}
	
	function ardeSlashConcat() {
		$s = '';
		foreach( func_get_args() as $arg ) {
			if( $arg != '' ) {
				if( $s != '' && $s != '/' && $arg != '/' ) {
					$s .= '/';
				}
				if( $s != '/' || $arg != '/' ) $s .= $arg;
			}
		}
		return $s;
	}
	
	global $ardeBase, $ardeBaseProfile;
	$ardeBase = new ArdeBaseApp();
	if( !isset( $ardeBaseProfile ) ) $ardeBaseProfile = 'default';
	$ardeBase->loadSettings( $ardeBaseProfile );
?>