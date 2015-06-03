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

	require_once $twatch->path( 'data/DataStatsPage.php' );

	$twatch->makeParentClass( 'IndexPage', 'TwatchPage' );

	class IndexPage extends IndexPageParent {
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			global $twatch;
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="'.$twatch->baseUrl( $this->getToRoot(), 'js/Graph.js' ).'"></script>' );
			$p->pl( '<script type="text/javascript" src="js/StatsPage.js"></script>' );
		}
		
		protected function init() {
			global $ardeUser;
			$this->addExtraUserDefData( StatsPage::$defaults );
			
			parent::init();
			
			if( !$ardeUser->user->data->get( TwatchUserData::VIEW_STATS ) ) {
				if( $ardeUser->user->data->get( TwatchUserData::VIEW_LATEST ) ) {
					return $this->redirect( $this->getUrlWithParams( 'latest.php' )->getUrl(), self::REDIRECT_SEE_OTHER );
				} elseif( $ardeUser->user->data->get( TwatchUserData::VIEW_PATH_ANALYSIS ) ) {
					return $this->redirect( $this->getUrlWithParams( 'path_analysis.php' )->getUrl(), self::REDIRECT_SEE_OTHER );
				} else {
					return $this->redirect( $this->getUrlWithParams( 'about.php' )->getUrl(), self::REDIRECT_SEE_OTHER );
				}
				throw new TwatchUserError( 'Not Found' );
			}
		}
		
		protected function getToRoot() {
			return '.';
		}
		
		protected function hasDoublePadBody() {
			return true;
		}
		
		protected function getSelectedTopButton() {
			return 0;
		}
		
		protected function getPageTitle() {
			return 'Web Stats';
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch, $ardeUser, $db;
			require_once $twatch->path( 'lib/EntityV.php' );
			require_once $twatch->path( 'lib/Reader.php' );


			$website = $twatch->config->get( TwatchConfig::WEBSITES, $this->websiteId );

			$historyR = new TwatchHistoryReader( $website->getSub() );
			$statsPages = $ardeUser->user->data->getList( TwatchUserData::STATS_PAGES );

			if( isset( $_GET[ 'i' ] ) ) $id = (int)$_GET[ 'i' ];
			else $id = 1;

			$minYear = 2004;
			$maxYear = $twatch->now->getYear();


			if( isset( $_GET['y'] ) ) {
				$today = false;
				$year = ArdeParam::int( $_GET, 'y' );
				if( $year > $maxYear || $year < $minYear ) throw new TwatchUserError( 'invalid year' );
				$month = ArdeParam::int( $_GET, 'm' );
				$day = ArdeParam::int( $_GET, 'd' );
				if( !TwatchTime::getTime()->isValidDate( $year, $month, $day ) ) throw new TwatchUserError( 'invalid date' );
			} else {
				$today = true;
				$year = $twatch->now->getYear();
				$month = $twatch->now->getMonth();
				$day = $twatch->now->getDay();
			}

			if( !isset( $statsPages[ $id ] ) ) $id = 1;

			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
			$texts = array_merge( array( 'NA', 'Today', 'Go', 'close', 'more', 'Copy to Clipboard', '{something} history', 'Search Latest Visitors', 'reset graph', 'add to graph' ), TwatchTime::$monthsShort );
			$this->initJsPage( $p, $texts );
			$p->rel();
			$p->pl( '/*]]>*/</script>' );
			$p->pl( '<div id="sub_menu">' );
			$p->pl( '	<div class="date"><script type="text/javascript">/*<![CDATA[*/' );

			$p->pl( '		dateSelect = new StatsDateSelect( '.$minYear.', '.$maxYear.', '.$year.', '.$month.', '.$day.' );' );
			$p->pl(	'		dateSelect.insert();' );
			$p->pl( '	/*]]>*/</script></div>' );
			$p->hold( 1 );
			foreach( $statsPages as $s ) {
				if( $s->id == $id ) {
					$p->pn( '<span class="button selected">'.$twatch->locale->text( $s->name ).'</span>' );
				} else {
					$p->pn( '<span class="button"><a href="'.($s->id==1?'./':'?i='.$s->id).'">'.$twatch->locale->text( $s->name ).'</a></span>' );
				}
			}
			$p->relnl();
			$p->pl( '</div>' );


			$statsPage = $statsPages[ $id ];

			$statsPage->init( $year, $month, $day, $today );

			$anySCounterV = false;
			foreach( $statsPage->sCounterVs as &$counterV ) {
				if( !$counterV->isViewable( $ardeUser->user ) ) continue;
				$anySCounterV = true;
				$counterV->request( $historyR );
			}
			
			foreach( $statsPage->lCounterVs as &$counterV ) {
				if( !$counterV->isViewable( $ardeUser->user ) ) continue;
				$counterV->request( $historyR );
			}

			$res = $historyR->rollGet();

			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );

			$p->rel();
			$p->pl( "	ardePreloadFlash( baseUrl+'fl/set_clipboard.swf' );" );
			$p->pl( "	ardePreloadImage( twatchUrl+'img/details.gif' );" );
			$p->pl( "	ardePreloadImage( twatchUrl+'img/graph.gif' );" );
			$p->pl( "	ardePreloadImage( twatchUrl+'img/graph_add.gif' );" );
			$p->pl( "	ardePreloadImage( twatchUrl+'img/filter.gif' );" );
			$p->pl( "	ardePreloadImage( baseUrl+'img/less.gif' );" );
			$p->pl( "	ardePreloadImage( baseUrl+'img/wait.gif' );" );
			$p->pl( '	graphManager = new GraphManager();' );
			$p->pl( '	statsPage = '.$statsPage->jsObject().';' );
			$p->hold( 1 );
			if( $anySCounterV ) {
				$p->pl( "singlesTable = new SinglesTable();" );
				$p->pl( 'singlesTable.insert();' );
				foreach( $statsPage->sCounterVs as &$sCounterV ) {
					if( !$sCounterV->isViewable( $ardeUser->user ) ) continue;
					$sCounterV->getResult( $historyR );
					$p->pl( 'singlesTable.addItem( '.$sCounterV->jsObject().' );' );
				}
			}
			$p->rel();
			$p->pl( '/*]]>*/</script>' );
			if( $statsPage->showComments && isset( $statsPage->periods[ TwatchPeriod::DAY ] ) ) {
				require_once $twatch->path( 'lib/Comments.php' );
				$minCode = 'zzzzzzzzzz';
				$maxCode = '';
				foreach( $statsPage->periods[ TwatchPeriod::DAY ] as $period ) {
					$code = $period->getCode();
					if( $code < $minCode ) $minCode = $code;
					if( $code > $maxCode ) $maxCode = $code;
				}
				$comments = new TwatchComments();

				if( $ardeUser->user->hasPermission( TwatchUserData::VIEW_PRIVATE_COMMENTS ) ) {
					$maxVisibility = TwatchComment::VIS_PRIVATE;
				} else {
					$maxVisibility = TwatchComment::VIS_PUBLIC;
				}
				$comments = $comments->get( $minCode, $maxCode, $maxVisibility );

				if( count( $comments ) ) {
					$width = 100 / $statsPage->width;
					$p->pl( '' );
					$p->pl( '<div class="margin_double">' );
					$p->pl( '<h3 style="margin-bottom:5px">Comments</h3>' );
					$p->pl( '<table class="cute_canvas" cellpadding="0" cellspacing="5" border="0" style="width:100%"><tr>', 1 );
					foreach( $statsPage->periods[ TwatchPeriod::DAY ] as $i => $period ) {

						$p->pn( '<td style="vertical-align:top;width:'.$width.'%">' );
						$code = $period->getCode();

						$p->pl( '<table class="cute no_canvas'.($i == $statsPage->highlightedPeriod[ TwatchPeriod::DAY ] ?' alt':'').'" style="width:100%">', 1 );
						if( isset( $comments[ $code ] ) ) {
							$p->pl( '<thead>' );
							$p->pl( '	<tr><td>'.$period->getName().'</td></tr>' );
							$p->pl( '</thead>' );

							$p->pl( '</tbody>' );
							foreach( $comments[ $code ] as $comment ) {
								$p->pl( '<tr><td style="background:#fff">'.$comment->txt.'</td></tr>' );
							}
							$p->pl( '</tbody>' );
						}
						$p->rel();
						$p->pn( '</table>' );
						$p->pl( '</td>' );
					}
					$p->rel();
					$p->pl( '</tr></table></div>' );
				}
			}
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
			foreach( $statsPage->lCounterVs as &$lCounterV ) {
				if( !$lCounterV->isViewable( $ardeUser->user ) ) continue;
				$lCounterV->getResult( $historyR );

				$p->pm( "\nlCView = ".$lCounterV->jsObject().';' );
				$p->nl();
				$p->pl( 'lCView.insert();' );
				$lCounterV->completeJsObject( $p, 'lCView' );
			}
			$p->rel();
			$p->pl( '/*]]>*/</script>' );
		}

	}

	$twatch->applyOverrides( array( 'IndexPage' => true ) );

	$page = $twatch->makeObject( 'IndexPage' );

	$page->render( $p );


?>
