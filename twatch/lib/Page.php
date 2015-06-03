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

	require_once $twatch->path( 'lib/PassivePage.php' );
	require_once $twatch->path( 'data/DataGlobal.php' );
	require_once $twatch->path( 'lib/ReportGeneral.php' );


	class TwatchPage extends TwatchPassivePage {
		private $conf_data;

		private $perm = true;

		public $adminPage = false;

		public $defaultWebsiteId;
		
		public $websiteId;

		public $website;
		
		public $selectedLocaleId;

		public $beforeOutput = null;

		private $extraDefUserProps = array();
		
		function __construct( $before_header = false ) {
			global $twatch;
			parent::__construct();
			$this->conf_data = array( TwatchConfig::$defaultProperties );
			$this->selected_top_button = 0;
			$this->before_header = false;
		}

		function addExtraConfig( &$data ) {
			$this->conf_data[] = &$data;
		}

		function addExtraUserDefData( &$data ) {
			$this->extraDefUserProps[] = &$data;
		}
		
		public function setLeftButtons() {}

		protected function hasLangCont() { return true; }
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			global $twatch;
			parent::printInHtmlHead( $p );
			$this->printBaseJsIncludes( $p );
			$p->pl( '<script type="text/javascript" src="'.$twatch->url( $this->getToRoot(), 'js/Global.js' ).'"></script>' );
		}
		
		protected function printBaseJsIncludes( ArdePrinter $p ) {
			global $twatch;
			$p->pl( '<script type="text/javascript" src="'.$twatch->baseUrl( $this->getToRoot(), 'js/ArdeClass.js' ).'"></script>' );
			$p->pl( '<script type="text/javascript" src="'.$twatch->baseUrl( $this->getToRoot(), 'js/ArdeRequest.js' ).'"></script>' );
			$p->pl( '<script type="text/javascript" src="'.$twatch->baseUrl( $this->getToRoot(), 'js/ArdeComponent.js' ).'"></script>' );
		}
		

		protected function init() {
			global $twatch, $ardeUser, $ardeBase, $eReporter, $p;

			$twatch->db = new ArdeDb( $twatch->settings );

			$twatch->db->connect();


			$ardeUserProfile = $twatch->settings[ 'user_profile' ];
			require_once $twatch->extPath( 'user', 'lib/Global.php' );
			require_once $twatch->path( 'data/DataUsers.php' );
			
			$twatch->config = new TwatchConfig( $twatch->db );
			foreach( $this->conf_data as $k => $v ) {
				$twatch->config->addDefaults( $this->conf_data[$k] );
			}
			$twatch->config->addDefaults( TwatchConfig::$userRelatedProperties );
			$twatch->config->applyAllChanges();
			
			initUser( $this->extraDefUserProps );

			if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_ERRORS ) ) {
				$p->setMutedErrors(  $twatch->settings[ 'authorized_muted_errors' ] );
				$p->setHideErrors( !$twatch->settings[ 'authorized_show_errors' ] );
			}

			if( ( $twatch->settings[ 'authorized_log_errors' ] && $ardeUser->user->hasPermission( TwatchUserData::VIEW_ERRORS ) ) ||
				( $twatch->settings[ 'unauthorized_log_errors' ] && !$ardeUser->user->hasPermission( TwatchUserData::VIEW_ERRORS ) ) ) {
				ArdeException::setGlobalReporter( new TwatchErrorLogger( ArdeException::getGlobalReporter() ) );
			}

			$this->determineSelectedWebsite();
			$ardeUser->user->data->loadChanges( $this->websiteId );
			
			if( isset( $_GET[ 'lang' ] ) && $twatch->localeExists( $_GET[ 'lang' ] ) ) {
				$this->selectedLocaleId = $_GET[ 'lang' ];
			} else {
				$this->selectedLocaleId = $ardeUser->user->data->get( TwatchUserData::DEFAULT_LANG );
			}
			$this->loadLocale();
			
			$this->setLeftButtons();

			$twatch->state = new TwatchState( $twatch->db );
			$twatch->state->addDefaults( TwatchState::$defaultProperties );
			$twatch->state->addDefaults( TwatchState::$extraDefaults );
			$twatch->state->applyAllChanges();


			$twatch->callFunction( 'twatchSetAppTime' );



			if( !$ardeUser->user->hasPermission( TwatchUserData::VIEW_REPORTS ) || 
				( $this->adminPage && ( !$ardeUser->user->hasPermission( TwatchUserData::VIEW_ADMIN ) && !$ardeUser->user->hasPermission( TwatchUserData::CONFIG ) ) ) ) {
				$url = new ArdeUrlWriter( 'login.php' );
				$url->setParam( 'back', ardeRequestUri() )->setParam( 'profile', $twatch->settings[ 'user_profile' ], 'default' );
				if( $ardeUser->user->id == ArdeUser::USER_PUBLIC ) {
					ardeRedirect( $twatch->extUrl( $this->getToRoot(), 'user', $url->getUrl() ) );
					return false;
				} else {
					throw new TwatchUserError( 'You do not have permission.' );
				}
			}


			$this->subTitle = $twatch->locale->text( $this->subTitle );

		}
		
		
		
		protected function determineSelectedWebsite() {
			global $twatch, $ardeUser;
			
			$this->defaultWebsiteId = twatchGetDefaultWebiteId();
			
			$this->websiteId = twatchGetSelectedWebsiteId( $this->defaultWebsiteId, $this->adminPage );
			
			if( $this->websiteId ) {
				$this->website = $twatch->config->get( TwatchConfig::WEBSITES, $this->websiteId );
			} else {
				$this->website = null;
			}
			
		}
		
		protected function getTitle() {
			global $twatch;
			return $twatch->locale->text( $this->website->name ).' - '.$twatch->locale->text( $this->getPageTitle() );
		}
		
		protected function getPageTitle() {
			return '';
		} 
		
		protected function getXmlLangCode() {
			global $twatch;
			return $twatch->locale->xmlLangCode;
		}
		
		protected function hasList() {
			global $ardeUser;
			return $ardeUser->user->hasPermission( TwatchUserData::VIEW_REPORTS );
		}
		
		protected function printInList( ArdePrinter $p ) {
			global $ardeUser, $twatch, $twatchProfile;
			if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_REPORTS ) ) {
				$profiles = $twatch->getProfiles();
				$hiddenProfiles = $ardeUser->user->data->get( TwatchUserData::HIDDEN_PROFILES );
				foreach( $profiles as $key => $name ) {
					if( isset( $hiddenProfiles[ $name ] ) ) unset( $profiles[ $key ] );
				}
				if( count( $profiles ) > 1 ) {
					$p->pl( $twatch->locale->text( 'Profile' ).': <select id="profile_select">', 1 );
					$url = ArdeUrlWriter::getCurrentRelative();
					foreach( $profiles as $id => $name ) {
						$url->setParam( 'profile', $id, 'default' );
						$p->pl( '<option value="'.$url->getUrl().'" '.( $id == $twatch->profile ? 'selected="selected"' : '' ).'>'.$twatch->locale->text( $name ).'</option>' );
					}
					$p->rel();
					$p->pl( '</select> ' );
					$p->pl( '<script type="text/javascript">activateLinkSelect( "profile_select" );</script>' );
					$p->pl( '&nbsp;' );
				}
				$p->pl( $twatch->locale->text( 'Website' ).': <select id="website_select">', 1 );
				$this->printWebsiteSelectOptions( $p );
				$p->rel();
				$p->pl( '</select>' );
				$p->pl( '<script type="text/javascript">activateLinkSelect( "website_select" );</script>' );
			}
		}
		
		protected function printWebsiteSelectOptions( ArdePrinter $p ) {
			global $twatch, $ardeUser;
			$url = ArdeUrlWriter::getCurrentRelative();
			foreach( $twatch->config->getList( TwatchConfig::WEBSITES ) as $id => $website ) {
				if( $website->parent ) continue;
				if( !$ardeUser->user->hasPermission( TwatchUserData::VIEW_WEBSITE, $id ) ) continue;
				$url->setParam( 'website', $website->getId(), $this->getDefaultWebsiteId() );
				$p->pl( '<option value="'.$url->getUrl().'" '.( $website->getId() == $this->websiteId ? 'selected="selected"' : '' ).'>'.$twatch->locale->text( $website->name ).'</option>' );
			}
		}
		
		protected function setUrlParams( ArdeUrlWriter $url, $adminButtons = false ) {
			global $twatch, $ardeUser;
			$url->setParam( 'lang', $this->selectedLocaleId, $ardeUser->user->data->get( TwatchUserData::DEFAULT_LANG ) );
			if( $this->adminPage == $adminButtons ) {
				$url->setParam( 'website', $this->websiteId, $this->getDefaultWebsiteId() );
			} else {
				$url->removeParam( 'website' );
			}
			$url->setParam( 'profile', $twatch->profile, 'default' );
		}
		
		protected function getUrlWithParams( $location ) {
			$url = new ArdeUrlWriter( $location );
			$this->setUrlParams( $url );
			return $url;
		}
		
		protected function getTopButtons() {
			global $ardeUser, $twatch;
			
			$topButtons = array();
			
			if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_REPORTS ) ) {
				
				$url = new ArdeUrlWriter();
				
				$this->setUrlParams( $url );
				
				if( $ardeUser->user->data->get( TwatchUserData::VIEW_STATS ) ) {
					$url->setAddress( $twatch->url( $this->getToRoot(), '' ) );
					$topButtons[ 0 ] = array( $twatch->locale->text( 'Stats' ), $url->getUrl() );
				}

				if( $ardeUser->user->data->get( TwatchUserData::VIEW_LATEST ) ) {
					$url->setAddress( $twatch->url( $this->getToRoot(), 'latest.php' ) );
					$topButtons[ 1 ] = array( $twatch->locale->text( 'Latest Visitors' ), $url->getUrl() );
				}

				if( $ardeUser->user->data->get( TwatchUserData::VIEW_PATH_ANALYSIS ) ) {
					$pathAnalyzerInstalled = $twatch->state->get( TwatchState::PATH_ANALYZER_INSTALLED );
					
					if( $pathAnalyzerInstalled ) {
						$url->setAddress( $twatch->url( $this->getToRoot(), 'path_analysis.php' ) );
						$topButtons[ 2 ] = array( $twatch->locale->text( 'Path Analysis' ), $url->getUrl() );
					}
				}
				
				if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_ADMIN ) || $ardeUser->user->hasPermission( TwatchUserData::CONFIG ) ) {
					$this->setUrlParams( $url, true );
					$url->setAddress( $twatch->url( $this->getToRoot(), 'admin/' ) );
					$topButtons[ 3 ] = array( $twatch->locale->text( $ardeUser->user->hasPermission( TwatchUserData::VIEW_ADMIN ) ? 'Administrate' : 'Configure' ), $url->getUrl() );
				}
				$url->setAddress( $twatch->url( $this->getToRoot(), 'about.php' ) );
				$topButtons[ 4 ] = array( $twatch->locale->text( 'About' ), $url->getUrl() );

				ksort( $topButtons );
			}
			
			return $topButtons;
		}
		
		protected function isRightToLeft() {
			global $twatch;
			return $twatch->locale->rightToLeft;
		}
		
		protected function printHeader( ArdePrinter $p ) {
			global $twatch, $ardeUser, $twatchProfile;
			

			if( !$ardeUser->user->hasPermission( TwatchUserData::VIEW_REPORTS ) ) {
				throw new TwatchRestriction( 'no permission' );
			}

			if( $twatch->settings[ 'disable_output_buffering' ] ) {
				while ( ob_get_level() > 0 ) {
					try { ob_end_flush(); } catch( Exception $e ) {}
				}
				try{ ob_implicit_flush(); } catch( Exception $e ) {}
			}
			
			

			$p->crossPrinter->forceHold();
			parent::printHeader( $p );
			$p->crossPrinter->forceFlush();
			
			if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_REPORTS ) ) {
				$p->pn( '<div class="infobar"'.($this->hasDoublePadBody()?' style="margin-right:-15px"':'').'><b>'.$twatch->locale->text( 'Now' ).':</b> ' );
				$tzName = $twatch->config->get( TwatchConfig::TIME_ZONE_NAME );

				$dt = array();
				$dt[ 'year' ] = $twatch->now->getYear();
				$dt[ 'month' ] = $twatch->locale->text( $twatch->now->getMonthLong() );
				$dt[ 'day' ] = $twatch->now->getDay();
				$dt[ 'hour' ] = $twatch->now->getPaddedHour();
				$dt[ 'minute' ] = $twatch->now->getPaddedMinute();
				$dt[ 'second' ] = $twatch->now->getPaddedSecond();
				$dt[ 'tzname' ] = $twatch->locale->text( $tzName );

				$p->pn( '<span class="b_txt">'.$twatch->locale->number( $twatch->locale->text( '{year} {month} {day} | {hour}:{minute}:{second} | {tzname}', $dt ) ).'</span> <b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$twatch->locale->text( 'Logger Status' ).':</b> ' );
				$p->pn( '<span class="b_txt">'.$twatch->locale->text( 'Working' ) );

				if( $ardeUser->user->hasPermission( TwatchUserData::ADMINISTRATE ) ) {
					$p->pn( ' ( ' );
					$errorReporter = new TwatchErrorLogger();
					$errorsCount = $errorReporter->getErrorsCount();
					if( $errorsCount == 0 ) {
						$p->pn( '<span class="good light">'.$twatch->locale->text( 'No Errors' ).'</span>' );
					} else {
						$url = new ArdeUrlWriter( 'admin/errors.php' );
						$url->setParam( 'profile', $twatch->profile, 'default' );
						$p->pn( '<a href="'.$twatch->url( $this->getToRoot(), $url->getUrl() ).'"><span class="critical">'.$errorsCount.' Error'.( $errorsCount == 1 ? '' : 's' ).'</span></a>' );
					}
					$p->pn( ' )' );
				}
				$p->pn( '</span>' );
				$p->pn( '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'.$twatch->locale->text( 'User' ).':</b> '.$ardeUser->user->name );

				if( $ardeUser->user->id != ArdeUser::USER_PUBLIC ) {
					$url = new ArdeUrlWriter( 'logout.php' );
					$url->setParam( 'lang', $this->selectedLocaleId, $ardeUser->user->data->get( TwatchUserData::DEFAULT_LANG ) );
					$url->setParam( 'profile', $twatch->settings[ 'user_profile' ], 'default' );
					$p->pn( ' (<a href="'.$twatch->extUrl( $this->getToRoot(), 'user', $url->getUrl() ).'">'.$twatch->locale->text( 'Logout' ).'</a>)' );
				} else {
					$url = new ArdeUrlWriter( 'login.php' );
					$url->setParam( 'lang', $this->selectedLocaleId, $ardeUser->user->data->get( TwatchUserData::DEFAULT_LANG ) );
					$url->setParam( 'back', ardeRequestUri() )->setParam( 'profile', $twatch->settings[ 'user_profile' ], 'default' );
					$p->pn( ' (<a href="'.$twatch->extUrl( $this->getToRoot(), 'user', $url->getUrl() ).'">'.$twatch->locale->text( 'Login' ).'</a>)' );
				}

				if( $ardeUser->user->hasPermission( TwatchUserData::ADMINISTRATE ) ) {
					if( isset( $_COOKIE[ $twatch->settings[ 'cookie_prefix' ].'_admin' ] ) &&
						$twatch->config->get( TwatchConfig::ADMIN_COOKIE )->isAdminCookie( $_COOKIE[ $twatch->settings[ 'cookie_prefix' ].'_admin' ] ) ) {
						$p->pn( ' <span style="font-size:.8em"><span style="font-weight:normal" class="good">'.$twatch->locale->text( 'Has Admin Cookie' ).'</span></span>' );
					} else {
						$url = new ArdeUrlWriter( 'admin/admin_cookie.php' );
						$url->setParam( 'action', 'set' )->setParam( 'back', ardeRequestUri() )->setParam( 'profile', $twatch->profile, 'default' );
						$p->pn( ' <span style="font-size:.8em">(<a title="'.$twatch->locale->text( 'Click to set the admin cookie in your browser' ).'" href="'.$twatch->url( $this->getToRoot(), $url->getUrl() ).'">'.$twatch->locale->text( 'Has No Admin Cookie' ).'</a>)</span>' );
					}
				}


				$p->pl( '</div>' );
				
			}
		}


		protected function printFooter( ArdePrinter $p ) {
			global $twatch;
			
			
			parent::printFooter( $p );
			
			if( $twatch->locale->translatorName != '' ) {
				if( $twatch->locale->translatorLink != '' ) {
					$name = '<a href="'.$twatch->locale->translatorLink.'">'.$twatch->locale->translatorName.'</a>';
				} else {
					$name = $twatch->locale->translatorName;
				}
				$p->pl( '	<div lang="en-US" xml:lang="en-US" id="translator">Translated to '.$twatch->locale->name.' by '.$name.'</div>' );
			}
		}
		
		
		protected function loadLocale() {
			global $twatch;


			$twatch->loadLocale( $this->selectedLocaleId );

		}

		protected function printInLangCont( ArdePrinter $p ) {
			global $twatch, $ardeUser;
			$p->pl( 'Language: <select id="lang_select">' );
			$url = ArdeUrlWriter::getCurrentRelative();
			$defaultId = $ardeUser->user->data->get( TwatchUserData::DEFAULT_LANG );
			foreach( $twatch->getLocaleIds() as $id ) {
				$url->setParam( 'lang', $id, $defaultId );
				$p->pl( '<option value="'.$url->getUrl().'"'.($id == $this->selectedLocaleId?' selected="selected"':'').'>'.$id.'</option>' );
			}
			$p->pl( '</select>' );
			$p->pl( '<script type="text/javascript">activateLinkSelect( "lang_select" );</script>' );

		}

		protected function getDefaultWebsiteId() {
			global $ardeUser;
			return $ardeUser->user->data->get( TwatchUserData::DEFAULT_WEBSITE );
		}
		
		public function initJsPage( ArdePrinter $p, $texts = array() ) {
			global $twatch, $ardeUser;
			$p->pl( 'websiteId = '.$this->websiteId.';' );
			$p->pl( 'websiteName = '.ArdeJs::string( $this->websiteId ? $this->website->name : 'All Websites' ).';' );
			$p->pl( 'defaultWebsiteId = '.$this->getDefaultWebsiteId().';' );
			$p->pl( "baseUrl = '".$twatch->baseUrl( $this->getToRoot(), '/' )."';" );
			$p->pl( "twatchUrl = '".$twatch->url( $this->getToRoot(), '/' )."';" );
			$p->pl( 'userUrl = '.ArdeJs::string( $twatch->extUrl( $this->getToRoot(), 'user', '/' ) ).';' );
			$p->pl( "twatchProfile = '".ArdeJs::escape($twatch->profile)."';" );
			$p->pl( 'ardeLocale = '.$twatch->locale->jsObject( $texts, $ardeUser->user->data->get( TwatchUserData::DEFAULT_LANG ) ).";" );
		}

		
	}

	$twatch->applyOverrides( array( 'TwatchPage' => true ) );

?>