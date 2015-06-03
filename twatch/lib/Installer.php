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
    
	require_once $ardeBase->path( 'db/DbArdeErrorWriter.php' );
	require_once $twatch->path( 'lib/Common.php' );
	require_once $twatch->path( 'lib/Comments.php' );
	require_once $twatch->path( 'data/DataGlobal.php' );
	require_once $twatch->path( 'lib/Progress.php' );
	
	class TwatchInstaller {
		
		public function installEnv( ArdePrinter $p, $keepConfig = true, $overwrite = false, $timeZone = null, $nowAlreadySet = false ) {
			global $twatch;
			
			$p->pn( '<p>installing dummy table... ' );
			$twatch->db->installDummyTable( '', 'du', 50, $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>installing errors log... ' );
			$errorReporter = new TwatchErrorLogger();
			$errorReporter->install( $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );
			
			if( !$keepConfig ) {
				$p->pn( '<p>installing configuration... ' );
				$twatch->config->install( $overwrite );
				$twatch->config->addDefaults( TwatchConfig::$defaultProperties );
				$def = array( TwatchConfig::PLUGIN_VERSIONS => array() );
				$twatch->config->addDefaults( $def );
				$twatch->config->applyAllChanges();
				$twatch->config->set( $twatch->makeInstanceId(), TwatchConfig::INSTANCE_ID );
				$p->pl( '<span class="good">successful</span></p>' );
			}
			
			if( $timeZone != null ) {
				$p->pn( '<p>setting time zone... ' );
				$origTz = TwatchTimeZone::fromConfig();
				if( !$timeZone->isEquivalent( $origTz ) ) {
					$timeZone->applyToConfig();
				}
				$p->pl( '<span class="good">successful</span></p>' );
			}
			if( !$nowAlreadySet ) {
				$twatch->now = new TwatchTime( time() + $twatch->config->get( TwatchConfig::TIME_DIFFERENCE ) );
			}
			
			
			$p->pn( '<p>making cookie keys... ' );
			TwatchCookieKeys::upgradeKeys();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>making the admin cookie secret... ' );
			TwatchAdminCookie::upgradeSecret();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>installing state... ' );
			$twatch->state->install( $overwrite );
			$twatch->state->addDefaults( TwatchState::$defaultProperties );
			$twatch->state->addDefaults( TwatchState::$extraDefaults );
			$twatch->state->applyAllChanges();
			
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>installing task manager... ' );
			$taskm = new TwatchTaskManager();
			$taskm->install( $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>making first daily task... ' );
			$dtask = new TwatchMakeDailyTasks( $twatch->now->dayOffset(1)->getDayStart() );
			$dtask->due = $twatch->now->dayOffset(1)->getDayStart();
			$taskm->addTasks( array( $dtask ) );
			$p->pl( '<span class="good">successful</span></p>' );

			$p->pn( '<p>making first monthly task... ' );
			$mTask = new TwatchMakeMonthlyTasks( $twatch->now->monthOffset(1)->getMonthStart() );
			$mTask->due = $twatch->now->monthOffset(1)->getMonthStart();
			$taskm->queueTasks( array( $mTask ) );
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>installing user data... ' );
			$userData = new TwatchUserData( null, null );
			$userData->install( $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pl( '<p>setting version... ' );
			$twatch->config->set( $twatch->version, TwatchConfig::VERSION );
			$p->pl( '<span class="good">successful</span></p>' );
		}
		
		public function uninstallEnv( ArdePrinter $p, $keepConfig = true ) {
			global $twatch;
			
			$p->pn( '<p>uninstalling user data... ' );
			$userData = new TwatchUserData( null, null );
			$userData->uninstall();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>uninstalling task manager... ' );
			$taskm = new TwatchTaskManager();
			$taskm->uninstall();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>removing app from user manager... ' );
			$ardeUsers = new ArdeUsers();
			$ardeUsers->removeApp( $twatch->config->get( TwatchConfig::INSTANCE_ID ) );
			$p->pl( '<span class="good">successful</span></p>' );
			
			if( !$keepConfig ) {
				$p->pn( '<p>uninstalling config... ' );
				$twatch->config->uninstall();
				$p->pl( '<span class="good">successful</span></p>' );
			}
			
			$p->pn( '<p>uninstalling state... ' );
			$twatch->state->uninstall();
			$p->pl( '<span class="good">successful</span></p>' );
					
			$p->pn( '<p>uninstalling errors log... ' );
			$errorReporter = new TwatchErrorLogger();
			$errorReporter->uninstall();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>uninstalling dummy table... ' );
			$twatch->db->uninstallDummyTable( '', 'du' );
			$p->pl( '<span class="good">successful</span></p>' );
			
		}
		
		public function install( ArdePrinter $p, $keepConfig = true, $overwrite = false, $startCounters = true, TwatchTimeZone $timeZone = null ) {
			global $twatch;
			
			$this->installEnv( $p, $keepConfig, $overwrite, $timeZone );
			
			
			
			$p->pn( '<p>installing dictionaries foundation... ' );
			TwatchDict::installBase( $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>installing entities... ' );
			TwatchEntity::fullInstall( $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>installing data reference counter... ' );
			TwatchEntityVRefCounter::install( $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>installing counters... ' );
			TwatchCounter::fullInstall( $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>installing visitor types... ' );
			TwatchVisitorType::fullInstall( $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>installing latest visitors... ' );
			TwatchLatest::install( $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>installing path analyzer... ' );
			TwatchPathAnalyzer::fullInstall( $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>installing comments... ' );
			$comments = new TwatchComments();
			$comments->install( $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );
			

			if( $startCounters ) {
				$p->pn( '<p>starting counters... ' );
				TwatchCounter::startAll();
				$p->pl( '<span class="good">successful</span></p>' );
			}
			
			$p->pn( '<p>installing progress bar... ' );
			$progress = new TwatchProgress();
			$progress->install( $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );
			
			foreach( $twatch->plugins as $plugin ) {
				if( $plugin->needsInstall() ) {
					$p->pn( '<p>installing "'.$plugin->getName().'" plugin' );
					$plugin->afterTwatchInstall( $p );
					$twatch->config->set( $plugin->getVersion(), TwatchConfig::PLUGIN_VERSIONS, $twatch->config->getStartId() + $plugin->id );
					$p->pl( '<span class="good">successful</span></p>' );
				}
			}
		}
		
		public function uninstall( ArdePrinter $p, $keepConfig = true ) {
			
			
			
			$p->pn( '<p>uninstalling progress bar... ' );
			$progress = new TwatchProgress();
			$progress->uninstall();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>uninstalling comments... ' );
			$comments = new TwatchComments();
			$comments->uninstall();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>uninstalling path analyzer... ' );
			TwatchPathAnalyzer::fullBaseUninstall();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>uninstalling latest visitors... ' );
			TwatchLatest::uninstall();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>uninstalling visitor types... ' );
			TwatchVisitorType::fullUninstall();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>uninstalling counters... ' );
			TwatchCounter::uninstallBase();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>uninstalling data reference counter... ' );
			TwatchEntityVRefCounter::uninstall();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>uninstalling dictionaries... ' );
			TwatchDict::uninstallBase();
			$p->pl( '<span class="good">successful</span></p>' );
			
			
			
			$this->uninstallEnv( $p, $keepConfig );	
			
		}
	}
?>