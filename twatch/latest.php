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

	require_once dirname(__FILE__).'/lib/PageGlobal.php';

	require_once $twatch->path( 'data/DataLatest.php' );

	$twatch->makeParentClass( 'LatestPage', 'TwatchPage' );

	class LatestPage extends LatestPageParent {
		
		protected function init() {
			global $ardeUser;
			$this->addExtraUserDefData( TwatchLatestPage::$defaults );
			
			parent::init();
			
			if( !$ardeUser->user->data->get( TwatchUserData::VIEW_LATEST ) ) {
				throw new TwatchUserError( 'Not Found' );
			}
		}
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="js/Latest.js"></script>' );
		}
		
		protected function getToRoot() { return '.'; }
		
		protected function getPageTitle() { return 'Latest Visitors'; }
		
		protected function getSelectedTopButton() { return 1; }
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch, $ardeBase, $ardeUser;

			require_once $twatch->path( 'lib/EntityV.php' );
			require_once $twatch->path( 'lib/Reader.php' );
			
			$entities = &$twatch->config->getList( TwatchConfig::ENTITIES );
			$latestPage = &$ardeUser->user->data->get( TwatchUserData::LATEST_PAGE );
			$visitorTypes = &$twatch->config->getList( TwatchConfig::VISITOR_TYPES );

			$website = $twatch->config->get( TwatchConfig::WEBSITES, $this->websiteId );

			$sessionR = new TwatchSessionReader( $website->getSub() );

			$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
			$p->hold( 1 );
			$this->initJsPage( $p, array( 'Update', 'VID', 'SID', 'd', 'h', 'm', 's', 'next page', 'prev. page', 'Please select something to search for', 'Search', 'Select', 'loading', 'Online', 'No Visitors', 'Copy to Clipboard', 'Filter Latest Visitors', 'Remove Filter', 'add', 'reset', 'Country', 'Unknown Country', 'Clear', 'already have filter of the same type', 'Sorry, You don\'t have permission', 'next {number}', 'prev {number}', '{number} older request', '{number} newer request' , '{number} older requests', '{number} newer requests', 'Visitors', 'Exists', 'Doesn\'t Exist' ) );
			$p->rel();
			$ents = new ArdeAppender( ', ' );
			foreach( $twatch->config->getList( TwatchConfig::ENTITIES ) as $entity ) {
				$ents->append( $entity->id.": ".$entity->minimalJsObject() );
			}

			$sEntities = array();
			$sEntities[ TwatchEntity::IP ] = true;
			foreach( $twatch->config->getList( TwatchConfig::RDATA_WRITERS ) as $dataWriter ) {
				if( $dataWriter->entityId == TwatchEntity::IP ) continue;
				$sEntities[ $dataWriter->entityId ] = true;
			}
			$sEntities[ TwatchEntity::PAGE ] = true;
			$sEnts = new ArdeAppender( ', ' );
			foreach( $sEntities as $id => $v ) $sEnts->append( $id.':true' );
			
			$p->pl( "	ardePreloadFlash( baseUrl+'fl/set_clipboard.swf' );" );
			$p->pl( "	ardePreloadImage( twatchUrl+'img/filter.gif' );" );
			$p->pl( "	ardePreloadImage( twatchUrl+'img/filter_add.gif' );" );
			$p->pl( "	ardePreloadImage( baseUrl+'img/wait.gif' );" );
			$p->pl( '	entities = { '.$ents->s.' };' );
			$p->pl( '	searchableEntities = { '.$sEnts->s.' };' );
			
			$p->pl( '/*]]>*/</script>' );
			
			$latestPage->init();
			
			if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_ADMIN_IN_LATEST ) ) $forceExcludeVt = null;
			else $forceExcludeVt = TwatchVisitorType::ADMIN;
			
			if( isset( $_GET[ 's' ] ) ) {
				$sessionId = (int)$_GET[ 's' ];
				if( isset( $_GET[ 'rs' ] ) ) {
					$requestOffset = (int)$_GET[ 'rs' ];
					if( $requestOffset < 0 ) $requestOffset = 0;
				} else {
					$requestOffset = 0;
				}
				
				$latestPage->initSingle( $sessionId );
				
				$sessionR->getSession( $sessionId, $forceExcludeVt );
				
				$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
				$p->pl( '	latestPage = '.$latestPage->jsObject().';' );
				$p->pl( '	latestPage.insertHeader();' );
				$p->pl( '/*]]>*/</script>' );
				
				if( isset( $sessionR->sessions[ $sessionId ] ) ) {
					$sessionR->finalizeSession( $sessionId, $latestPage, $requestOffset, $latestPage->requestPerSingleSession );
					$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
					$p->pl( $sessionR->sessions[ $sessionId ]->jsObject( $latestPage ).'.insert();' );
					$p->rel();
					$p->pl( '/*]]>*/</script>' );
				}
				
			} else {
			
				if( isset( $_GET[ 'start' ] ) ) $start = (int)$_GET[ 'start' ];
				else $start = 0;
	
				$entityFilters = array();
				if( isset( $_GET['f'] ) ) {
					$fs = explode( '_', $_GET['f'] );
					for( $i = 0; $i < count( $fs ); $i += 2 ) {
						$entityId = (int)$fs[ $i ];
						if( !isset( $entities[ $entityId ] ) ) throw new TwatchException( 'invalid filter entity id '.$entityId );
						if( isset( $entityFilters[ $entityId ] ) ) throw new TwatchException( 'duplicate entity id '.$entityId );
						if( !isset( $fs[ $i + 1 ] ) ) throw new TwatchException( 'entity value id not sent' );
						if( $fs[ $i + 1 ] == 'e' || $fs[ $i + 1 ] == 'de' ) {
							$entityVId = $fs[ $i + 1 ];
						} else {
							$entityVId = ardeStrToU32( $fs[ $i + 1 ] );
						}
						if( $ardeUser->user->data->get( TwatchUserData::VIEW_ENTITY, $entityId ) != TwatchEntity::VIS_VISIBLE ) {
							throw new TwatchNoPermission( 'Permission Denied. You are not allowed to use a filter of that type.' );
						}
						$entityFilters[ $entityId ] = $entityVId;
					}
				}
	
	
				$visitorTypeFilter = array();
				if( isset( $_GET[ 'vt' ] ) && !empty( $_GET['vt'] ) ) {
					$vts = explode( '_', $_GET['vt'] );
					foreach( $vts as $vt ) {
						$visitorTypeId = (int)$vt;
						if( !isset( $visitorTypes[ $visitorTypeId ] ) ) throw new TwatchException( 'invalid visitor type id '.$visitorTypeId );
						if( in_array( $visitorTypeId, $visitorTypeFilter ) ) throw new TwatchException( 'duplicate visitor type id '.$visitorTypeId );
						$visitorTypeFilter[] = $visitorTypeId;
					}
				} else {
					$visitorTypeFilter = $latestPage->defaultVtSelection;
				}
	
					
				
				
				$res = $sessionR->getLatestSessions( $start, $latestPage->perPage, $entityFilters, $visitorTypeFilter, $forceExcludeVt );
	
				$latestPage->initLatest( $entityFilters, $visitorTypeFilter, $start, $sessionR->more, $sessionR->online );

				$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
				$p->pl( '	latestPage = '.$latestPage->jsObject().';' );
				$p->pl( '	latestPage.insertHeader();' );
				$p->pl( '/*]]>*/</script>' );
				
				if( !count( $sessionR->sessions ) ) {
					$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
					$p->pl( '	latestPage.noSessions();' );
					$p->pl( '/*]]>*/</script>' );
				} else {
					foreach( $sessionR->sessions as $sessionId => &$session ) {
						
						$sessionR->finalizeSession( $sessionId, $latestPage, 0, $latestPage->requestPerSession );
						$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
						$p->pl( $session->jsObject( $latestPage ).'.insert();' );
						$p->rel();
						$p->pl( '/*]]>*/</script>' );
					}
				}
			}
			
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/' );
			$p->pl( '	latestPage.insertFooter();');
			$p->pl( '/*]]>*/</script>');
		}
		
	}

	$twatch->applyOverrides( array( 'LatestPage' => true ) );

	$page = $twatch->makeObject( 'LatestPage' );

	$page->render( $p );

?>