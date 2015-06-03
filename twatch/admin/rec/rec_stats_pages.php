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
    
	$adminConfig = true;
	require_once dirname(__FILE__).'/../../lib/RecGlobalHead.php';
	require_once $twatch->path( 'lib/StatsPage.php' );
	require_once $twatch->path( 'data/DataStatsPage.php' );
	require_once $twatch->path( 'lib/AdminGeneral.php' );
	
	loadConfig();

	$selectedUser = getSelectedUser( true, StatsPage::$defaults );
	
	requireConfigPermission( $selectedUser );
	
	$statsPages = &$selectedUser->data->getList( TwatchUserData::STATS_PAGES );
	$counters = &$twatch->config->getList( TwatchConfig::COUNTERS );
	
	function statsPageFromParams( $new ) {
		global $statsPages;
		if( !$new ) {
			$id = ArdeParam::int( $_POST, 'i' );
			if( !isset( $statsPages[ $id ] ) ) throw new TwatchException( 'stats page with id '.$id.' not found' );
		} else {
			$id = null;
		}
		$name = $_POST[ 'n' ];
		$width = ArdeParam::int( $_POST, 'w' );
		if( $width < 1 ) throw new TwatchException( 'invalid width' );
		
		$sCIds = ArdeParam::intArr( $_POST, 'sc', ' ' );
		$lCIds = ArdeParam::intArr( $_POST, 'lc', ' ' );
		
		$sCounterVs = array();
		foreach( $sCIds as $sCId ) {
			$cView = cViewFromParams( false, false, false, 's'.$sCId.'_' );
			$sCounterVs[ $cView->id ] = $cView;
		}
		
		$lCounterVs = array();
		foreach( $lCIds as $lCId ) {
			$cView = cViewFromParams( true, false, false, 'l'.$lCId.'_' );
			$lCounterVs[ $cView->id ] = $cView;
		}
		if( !$new ) {
			$periodTypes = $statsPages[ $id ]->periodTypes;
		} else {
			$periodTypes = array( TwatchPeriod::DAY, TwatchPeriod::MONTH, TwatchPeriod::ALL );
		}
		$o = new StatsPage( $id, $name, $width, $periodTypes );
		$o->sCounterVs = $sCounterVs;
		$o->lCounterVs = $lCounterVs;
		return $o;
	}
	
	function cViewFromParams( $list, $sub, $new, $prefix = '' ) {
		global $statsPageId, $statsPages, $counters, $twatch, $selectedUser;
		
		if( $prefix != '' ) $extra = ' for '.$prefix;
		else $extra = ''; 
		
		if( $sub === false ) {
			if( !isset( $_POST[ $prefix.'si' ] ) ) throw new TwatchException( 'stats page id not specified'.$extra );
			$statsPageId = (int)$_POST[ $prefix.'si' ];
			if( !isset( $statsPages[ $statsPageId ] ) ) throw new TwatchException( 'stats page with id '.$statsPageId.' not found'.$extra );
			$statsPage = $statsPages[ $statsPageId ];
		}
		
		if( !isset( $_POST[ $prefix.'t' ] ) ) throw new TwatchException( 'title not specified'.$extra );
		$title = $_POST[ $prefix.'t' ];
		
		if( !$new ) {
			if( !isset( $_POST[ $prefix.'i' ] ) ) throw new TwatchException( 'id not specified'.$extra );
			$id = (int)$_POST[ $prefix.'i' ];
			if( $list ) {
				if( !isset( $statsPage->lCounterVs[ $id ] ) ) throw new TwatchException( 'list counter view with id '.$id.' not found in stats page '.$statsPageId.$extra );
				$oldCView = $statsPage->lCounterVs[ $id ];
			} else {
				if( !isset( $statsPage->sCounterVs[ $id ] ) ) throw new TwatchException( 'single counter view with id '.$id.' not found in stats page '.$statsPageId.$extra );
				$oldCView = $statsPage->sCounterVs[ $id ];
			}
		} elseif( $sub !== false ) {
			$id = $sub;
		} else {
			$id = 0;
		}
		
		if( !$new )	{
			$numberTitle = $oldCView->numberTitle;
		} else {
			$numberTitle = '#';
		} 
		
		if( !isset( $_POST[ $prefix.'ci' ] ) ) throw new TwatchException( 'counter id not specified'.$extra );
		$counterId = (int)$_POST[ $prefix.'ci' ];
		if( !isset( $counters[ $counterId ] ) || !$counters[ $counterId ]->isViewable( $selectedUser ) ) throw new TwatchException( 'invalid counter id '.$counterId.$extra );

		if( $list && !( $counters[ $counterId ] instanceof TwatchListCounter ) ) throw new TwatchException( 'list counter views can only show list counters'.$extra );
		if( !$list && ( $counters[ $counterId ] instanceof TwatchListCounter ) ) throw new TwatchException( 'single counter view can only show single counters'.$extra );
		if( $counters[ $counterId ] instanceof TwatchGroupedCounter && $sub === false ) {
			if( !isset( $_POST[ $prefix.'g' ] ) ) throw new TwatchException( 'group not specified'.$extra );
			$group = (int)$_POST[ $prefix.'g' ];
			if( $group <= 0 ) throw new TwatchException( 'invalid group '.$group );
		} else {
			$group = 0;
		}
		
		if( !isset( $_POST[ $prefix.'pt' ] ) ) throw new TwatchException( 'period types not specified'.$extra );
		$pt = $_POST[ $prefix.'pt' ];
		if( $pt == '' ) throw new TwatchException( 'at least one period type should be selected'.$extra );
		$pt = explode( ' ', $pt );
		$periodTypes = array();
		foreach( $pt as $p ) {
			$periodType = (int)$p;
			if( !isset( TwatchPeriod::$typeStrings[ $periodType ] ) ) throw new TwatchException( 'invalid period type '.$periodType.$extra );
			$periodTypes[] = $periodType; 
		}
		
		if( !isset( $_POST[ $prefix.'d' ] ) ) throw new TwatchException( 'divide by not specified'.$extra );
		$divBy = (int)$_POST[ $prefix.'d' ];
		if( !isset( CounterView::$divByStrings[ $divBy ] ) ) throw new TwatchException( 'invalid divide by id '.$divBy.$extra );
		if( $divBy == CounterView::DIV_VALUE ) {
			if( !isset( $_POST[ $prefix.'dv' ] ) ) throw new TwatchException( 'divide by value id not specified'.$extra );
			$divByValueId = (int)$_POST[ $prefix.'dv' ];
			if( $list || $new ) {
				if( !isset( $statsPage->sCounterViews[ $divByValueId ] ) ) throw new TwatchException( 'no single counter view with id '.$divByValueId.' found in stats page '.$statsPageId.$extra );
			} else {
				foreach( $statsPage->sCounterVs as $sId => $sCounterView ) {
					if( $sId == $id ) throw new TwatchException( 'no single counter view with id '.$divByValueId.' found before the one with id '.$id.' in stats page '.$statsPageId.$extra );
					if( $sId == $divByValueId ) break;
				}
			}
		} else {
			$divByValueId = null;
		}
		
		if( !isset( $_POST[ $prefix.'l' ] ) ) throw new TwatchException( 'limit not specified'.$extra );
		$divLimit = (double)$_POST[ $prefix.'l' ];
		if( $divLimit < 0 ) throw new TwatchException( 'invalid limit '.$divLimit.$extra );
		
		if( !isset( $_POST[ $prefix.'r' ] ) ) throw new TwatchException( 'round not specified'.$extra );
		$round = (int)$_POST[ $prefix.'r' ];
		if( $round < -1 ) throw new TwatchException( 'invalid round '.$round.$extra );

		if( $list ) {
			$percentRound = ArdeParam::int( $_POST, $prefix.'pr', -1 );
			$rows =ArdeParam::int( $_POST, $prefix.'rw', 0 );
			$entityView = TwatchEntityView::fromParams( $_POST, $prefix.'ev_' );
			
			$entityId = $counters[ $counterId ]->entityId;
			if( $twatch->config->propertyExists( TwatchConfig::ENTITIES, $entityId ) ) {
				$set = $twatch->config->get( TwatchConfig::ENTITIES, $entityId )->gene->getSet();
				if( $set !== false ) {
					$startFrom = ArdeParam::int( $_POST, $prefix.'sf', 1 );
				} else {
					$startFrom = 1;
				}
			} else {
				$startFrom = 1;
			}
		}

		if( !$list ) {
			return new SingleCounterView( $id, $title, $numberTitle, $counterId, $periodTypes, $divBy, $divByValueId, $divLimit, $round );
		} else {
			if( $sub === false ) {
				$o = new ListCounterView( $id, $title, $numberTitle, $counterId, $periodTypes, $entityView, $rows, $group, $divBy, $divByValueId, $divLimit, $round, $percentRound, $startFrom );
			} else {
				$o = new SubCounterView( $id, $title, $numberTitle, $counterId, $periodTypes, $entityView, $rows, $group, $divBy, $divByValueId, $divLimit, $round, $percentRound, $startFrom );
			}
			$subCount = ArdeParam::int( $_POST, $prefix.'subc' );
			for( $i = 0; $i < $subCount; ++$i ) {
				$o->subs[ $i ] = cViewFromParams( true, $i, true, $prefix.'s'.$i.'_' );
				if( $counters[ $o->subs[ $i ]->counterId ]->groupEntityId != $counters[ $o->counterId ]->entityId ) throw new TwatchUserError( 'invalid counter id for '.$prefix.'s'.$i.'_' ); 
			} 
			if( !$new ) {
				$o->graphView = $oldCView->graphView;
			}
			return $o;
		}
		
	}
	
	
	if( !isset( $_POST['a'] )) throw new TwatchException( 'Action was not sent' );
	
	if( $_POST['a'] == 'set_vis' ) {

		BoolWithDefAction::fromParams( TwatchUserData::VIEW_STATS, 0, $_POST )->run( $selectedUser->data );
		
		successful( $p );
		
	} elseif( $_POST['a'] == 'add' ) {
		
		$statsPage = statsPageFromParams( true );

		$statsPage->id = $selectedUser->data->getNewSubId( TwatchUserData::STATS_PAGES );
		$selectedUser->data->set( $statsPage, TwatchUserData::STATS_PAGES, $statsPage->id );
		if( !$xhtml ) {
			$statsPage->printXml( $p, 'result' );
			$p->nl();
		}
		
	} elseif( $_POST['a'] == 'delete' ) {
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( $id == 1 ) throw new TwatchUserError( "you can't delete the default stats page yet" );
		if( !isset( $statsPages[ $id ] ) ) throw new TwatchException( 'stats page with id '.$id.' not found' );
		$selectedUser->data->remove( TwatchUserData::STATS_PAGES, $id );
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'change' ) {
		
		$statsPage = statsPageFromParams( false );
		if( $statsPage->isEquivalent( $statsPages[ $statsPage->id ] ) ) throw new TwatchUserError( 'nothing to change' );
		$statsPages[ $statsPage->id ] = $statsPage;
		$selectedUser->data->setInternal( TwatchUserData::STATS_PAGES, $statsPage->id );
		if( !$xhtml ) {
			$p->pl( '<successful />' );
		}
	
	} elseif( $_POST['a'] == 'change_scview' ) {
		
		$cView = cViewFromParams( false, false, false );
		$statsPage = &$statsPages[ $statsPageId ];
		if( $statsPage->sCounterVs[ $cView->id ]->isEquivalent( $cView ) ) {
			throw new TwatchUserError( 'nothing to change' );
		}
		$statsPage->sCounterVs[ $cView->id ] = $cView;
		$selectedUser->data->setInternal( TwatchUserData::STATS_PAGES, $statsPageId );
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'change_lcview' ) {
		
		$cView = cViewFromParams( true, false, false );
		
		
		$statsPage = &$statsPages[ $statsPageId ];
		if( $statsPage->lCounterVs[ $cView->id ]->isEquivalent( $cView ) ) {
			throw new TwatchUserError( 'nothing to change' );
		}
		$statsPage->lCounterVs[ $cView->id ] = $cView;
		$selectedUser->data->setInternal( TwatchUserData::STATS_PAGES, $statsPageId );
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'delete_scview' ) {
		
		$statsPageId = ArdeParam::int( $_POST, 'si' );
		if( !isset( $statsPages[ $statsPageId ] ) ) throw new TwatchException( 'stats page with id '.$statsPageId.' not found' );
		$statsPage = &$statsPages[ $statsPageId ];
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $statsPage->sCounterVs[ $id ] ) ) throw new TwatchException( 'stats page '.$statsPageId.' does not have a single counter view with id '.$id );
		
		unset( $statsPage->sCounterVs[ $id ] );
		$selectedUser->data->setInternal( TwatchUserData::STATS_PAGES, $statsPageId );
		
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'delete_lcview' ) {
		
		$statsPageId = ArdeParam::int( $_POST, 'si' );
		if( !isset( $statsPages[ $statsPageId ] ) ) throw new TwatchException( 'stats page with id '.$statsPageId.' not found' );
		$statsPage = &$statsPages[ $statsPageId ];
		
		$id = ArdeParam::int( $_POST, 'i' );
		if( !isset( $statsPage->lCounterVs[ $id ] ) ) throw new TwatchException( 'stats page '.$statsPageId.' does not have a list counter view with id '.$id );
		
		unset( $statsPage->lCounterVs[ $id ] );
		$selectedUser->data->setInternal( TwatchUserData::STATS_PAGES, $statsPageId );
		
		if( !$xhtml ) $p->pl( '<successful />' );
		
	} elseif( $_POST['a'] == 'add_scview' ) {
		
		$cView = cViewFromParams( false, false, true );
		$statsPage = &$statsPages[ $statsPageId ];
		$cView->id = $statsPage->newSCounterViewId();
		$statsPage->sCounterVs[ $cView->id ] = $cView;
		$selectedUser->data->setInternal( TwatchUserData::STATS_PAGES, $statsPageId );
		if( !$xhtml ) {
			$cView->printXml( $p, 'result', ' stats_page_id="'.$statsPageId.'" ' );
			$p->nl();
		}
		
	} elseif( $_POST['a'] == 'add_lcview' ) {
		
		$cView = cViewFromParams( true, false, true );
		$statsPage = &$statsPages[ $statsPageId ];
		$cView->id = $statsPage->newLCounterViewId();
		$statsPage->lCounterVs[ $cView->id ] = $cView;
		$selectedUser->data->setInternal( TwatchUserData::STATS_PAGES, $statsPageId );
		if( !$xhtml ) {
			$cView->printXml( $p, 'result', ' stats_page_id="'.$statsPageId.'" ' );
			$p->nl();
		}
		
	} elseif( $_POST['a'] == 'restore' ) {
		$statsPageId = ArdeParam::int( $_POST, 'i' );
		if( !isset( $statsPages[ $statsPageId ] ) ) throw new TwatchException( 'stats page with id '.$statsPageId.' not found' );
		$statsPage = $statsPages[ $statsPageId ];
		$defStatsPage = $selectedUser->data->getDefault( TwatchUserData::STATS_PAGES, $statsPageId );
		$notFound = new ArdeAppender( ', ' );
		foreach( $defStatsPage->sCounterVs as $cView ) {
			if( !isset( $counters[ $cView->counterId ] ) ) $notFound->append( $cView->title );
		}
		foreach( $defStatsPage->lCounterVs as $cView ) {
			if( !isset( $counters[ $cView->counterId ] ) ) $notFound->append( $cView->title );
		}
		if( $notFound->c != 0 ) throw new TwatchUserError( "you can't restore default stats page because some counters referenced in this stats page are deleted, restore those counters and try again. counter views with missing counters are: ".$notFound->s );

		$selectedUser->data->restoreDefault( TwatchUserData::STATS_PAGES, $statsPageId );
		if( !$xhtml ) {
			$statsPages[ $statsPageId ]->printXml( $p, 'result' );
			$p->nl();
		}
	} else {
		throw new TwatchException( 'unknown action '.$_POST['a'] );
	}

	if( $xhtml ) {
		$p->rel();
		$p->pl( '</body>' );
	}
	
	$p->end();
?>
