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
	require_once $ardeBase->path( 'lib/ArdeXml.php' );
	require_once $twatch->path( 'lib/Import.php' );

	loadConfig();
	
	requirePermission( TwatchUserData::ADMINISTRATE );
	
	function performPtDelete( TwatchDbHistory $dbHistory, TwatchDbPassiveDict $dbDict, TwatchWebsite $website, TwatchImportDeleteInfo $delete, $counterId, $periodType, $tss ) {
		if( count( $delete->groups ) ) {
			foreach( $delete->groups as $groupName => $rows ) {
				if( count( $rows ) ) {
					foreach( $rows as $rowName => $nothing ) {
						TwatchCounter::deleteDataS( $dbHistory, $dbDict, $website, $counterId, $groupName, $rowName, $periodType, $tss );
					}
				} else {
					TwatchCounter::deleteDataS( $dbHistory, $dbDict, $website, $counterId, $groupName, null, $periodType, $tss );
				}
			}
		} elseif( count( $delete->rows ) ) {
			foreach( $delete->rows as $rowName => $nothing ) {
				TwatchCounter::deletedataS( $dbHistory, $dbDict, $website, $counterId, null, $rowName, $periodType, $tss );
			}
		} else {
			TwatchCounter::deleteDataS( $dbHistory, $dbDict, $website, $counterId, null, null, $periodType, $tss );
		}
	}

	function performDelete( TwatchDbHistory $dbHistory, TwatchDbPassiveDict $dbDict, TwatchWebsite $website, TwatchImportDeleteInfo $delete, $counterId = 0 ) {
		if( !count( $delete->availability->timestamps ) ) {
			performPtDelete( $dbHistory, $dbDict, $website, $delete, $counterId, null, $delete->tss );
		} else {
			foreach( $delete->availability->timestamps as $periodType => $tss ) {
				if( !count( $tss ) && count( $delete->tss ) ) $tss = $delete->tss;
				performPtDelete( $dbHistory, $dbDict, $website, $delete, $counterId, $periodType, $tss );
			}
		}
	}

	if( !isset( $_POST['a'] ) ) throw new TwatchException( 'Action was not sent' );

	if( $_POST['a'] == 'read' ) {
		try { set_time_limit( 100 ); } catch( Exception $e ) {}
		$fileName = ArdeParam::str( $_POST, 'f' );
		if( !file_exists( $twatch->path( 'store/'.$fileName ) ) ) throw new TwatchException( 'file "'.$fileName.'" doesn\'t exist' );
		$rootElement = ArdeXml::fileRoot(  $twatch->path( 'store/'.$fileName ) );
		$fileInfo = TwatchImportFileInfo::fromXml( $rootElement );
		$fileInfo->name = $fileName;
		if( !$xhtml ) {
			$fileInfo->printXml( $p, 'result' );
			$p->nl();
		}
	} elseif( $_POST['a'] == 'list_dir' ) {
		$files = array();
		$dir = opendir( $twatch->path( 'store' ) );
		while( $file = readdir( $dir ) ) {
			if( $file == '..' || $file == '.' ) continue;
			$files[] = $file;
		}
		if( !$xhtml ) {
			$p->pl( '<result>', 1 );
			foreach( $files as $file ) {
				$p->pl( '<file>'.ardeXmlEntities( $file ).'</file>' );
			}
			$p->rel();
			$p->pl( '</result>' );
		}
	} elseif( $_POST['a'] == 'import' ) {

		$allFromMonth = ArdeParam::bool( $_POST, 'am' );
		try { set_time_limit( 600 ); } catch( Exception $e ) {}
		$fileName = ArdeParam::str( $_POST, 'n' );
		if( !file_exists( $twatch->path( 'store/'.$fileName ) ) ) throw new TwatchException( 'file "'.$fileName.'" doesn\'t exist' );

		$progressChannelId = ArdeParam::int( $_POST, 'ch', 0 );

		$websiteId = ArdeParam::int( $_POST, 'w' );
		if( !$twatch->config->propertyExists( TwatchConfig::WEBSITES, $websiteId ) ) {
			throw new TwatchException( 'website '.$websiteId.' does not exist.' );
		}
		$website = $twatch->config->get( TwatchConfig::WEBSITES, $websiteId );
		if( $website->parent ) {
			throw new TwatchException( 'data cannot be imported into sub-websites.' );
		}

		$counterCount = ArdeParam::int( $_POST, 'cc', 0 );
		$counterMap = array();
		for( $i = 0; $i < $counterCount; ++$i ) {
			$name = ArdeParam::str( $_POST, 'c'.$i.'_n' );
			$counterId = ArdeParam::int( $_POST, 'c'.$i.'_mi' );
			if( !$twatch->config->propertyExists( TwatchConfig::COUNTERS, $counterId ) ) {
				throw new TwatchException( 'counter '.$counterId.' does not exist' );
			}
			if( !$twatch->config->get( TwatchConfig::COUNTERS, $counterId )->allowImport() ) {
				throw new TwatchException( 'counter '.$counterId.' doesn\'t allow import' );
			}
			$counterMap[ $name ] = $counterId;
		}


		$rootElement = ArdeXml::fileRoot(  $twatch->path( 'store/'.$fileName ) );
		$fileInfo = TwatchImportFileInfo::fromXml( $rootElement );

		$dict = new TwatchDbPassiveDict( $twatch->db );

		$dbHistory = new TwatchDbHistory( $twatch->db, $website->getSub() );


		if( $fileInfo->delete !== null ) {
			performDelete( $dbHistory, $dict, $website, $fileInfo->delete );
		}

		require_once $twatch->path( 'lib/Progress.php' );
		$progress = new TwatchProgress( $progressChannelId );
		$progress->start();

		$rowCount = 0;

		foreach( $fileInfo->counters as $impCounter ) {
			if( isset( $counterMap[ $impCounter->name ] ) ) {
				$counterId = $counterMap[ $impCounter->name ];
				$counter = $twatch->config->get( TwatchConfig::COUNTERS, $counterId );
				if( $impCounter->delete !== null ) {

					performDelete( $dbHistory, $dict, $website, $impCounter->delete, $counterId );
				}
				if( $counter->getType() != TwatchCounter::TYPE_SINGLE ) {
					$gene = $twatch->config->get( TwatchConfig::ENTITIES, $counter->entityId )->gene->getPassiveGene( $dict, TwatchEntityPassiveGene::MODE_NORMAL, TwatchEntityPassiveGene::CONTEXT_IMPORT );
					if( $counter->getType() == TwatchCounter::TYPE_GROUPED ) {
						$groupGene = $twatch->config->get( TwatchConfig::ENTITIES, $counter->groupEntityId )->gene->getPassiveGene( $dict, TwatchEntityPassiveGene::MODE_NORMAL, TwatchEntityPassiveGene::CONTEXT_IMPORT );
					}
				}
				$counterAvail = &$twatch->state->get( TwatchState::COUNTERS_AVAIL, $counterId );

				foreach( $impCounter->periodTypes as $periodTypeId => $periodType ) {


					if( isset( $impCounter->availability->timestamps[ $periodTypeId ] ) ) {

						$currentTss = $counterAvail->getTimestamps( $periodTypeId, true );
						$impTss = $impCounter->availability->getTimeStamps( $periodTypeId, true );
						$difference = TwatchCounterAvailability::subtractTss( $currentTss, $impTss );


						$periodEs = array();
						foreach( new ArdeXmlElemIter( $impCounter->element, TwatchPeriod::$typeImportTagNames[ $periodTypeId ] ) as $periodE ) {
							$period = TwatchPeriod::fromImportElement( $periodE );
							$periodEs[ $period->getCode() ] = $periodE;
						}


						$period = TwatchPeriod::makePeriod( $periodTypeId, $impCounter->availability->getStart( $periodTypeId ) );
						$startCode = $period->getCode();
						$stop = TwatchPeriod::makePeriod( $periodTypeId, $impCounter->availability->getStop( $periodTypeId ) );
						$stopCode = $stop->getCode();


						$periodCode = $period->getCode();
						do {
							if( !$period->unionLength( $impTss ) ) {
								$period = $period->offset(1);
								$periodCode = $period->getCode();
								continue;
							}

							$currentLength = $period->unionLength( $currentTss );
							if( $currentLength ) {
								$mergeRatio = $period->unionLength( $difference ) / $currentLength;
							} else {
								$mergeRatio = 0;
							}

							if( $counter->getType() == TwatchCounter::TYPE_SINGLE ) {
								++$rowCount;
								$progress->set( $fileInfo->rowCount == 0 ? 0 : $rowCount / $fileInfo->rowCount );

								if( isset( $periodEs[ $periodCode ] ) ) $count = ArdeXml::intContent( $periodEs[ $periodCode ] );
								else $count = 0;

								$counter->replace( $dbHistory, $period, $count, $mergeRatio );


							} elseif( $counter->getType() == TwatchCounter::TYPE_LIST ) {
								$rows = array();
								if( isset( $periodEs[ $periodCode ] ) ) {
									foreach( new ArdeXmlElemIter( $periodEs[ $periodCode ], 'row' ) as $rowE ) {
										++$rowCount;
										$progress->set( $fileInfo->rowCount == 0 ? 0 : $rowCount / $fileInfo->rowCount );

										$count = ArdeXml::intAttribute( $rowE, 'count' );
										$str = ArdeXml::strContent( $rowE );

										$entityVId = $gene->getStringEntityVId( $str, $website );
										if( $entityVId != false ) {
											$rows[ $entityVId ] = $count;
										}
									}
								}
								$counter->replace( $dbHistory, $period, $rows, $mergeRatio );
							} else {
								$rows = array();
								if( isset( $periodEs[ $periodCode ] ) ) {
									foreach( new ArdeXmlElemIter( $periodEs[ $periodCode ], 'group' ) as $groupE ) {
										$groupId = $groupGene->getStringEntityVId( ArdeXml::strAttribute( $groupE, 'name' ), $website );
										foreach( new ArdeXmlElemIter( $groupE, 'row' ) as $rowE ) {
											++$rowCount;
											$progress->set( $fileInfo->rowCount == 0 ? 0 : $rowCount / $fileInfo->rowCount );

											$count = ArdeXml::intAttribute( $rowE, 'count' );
											$str = ArdeXml::strContent( $rowE );

											$entityVId = $gene->getStringEntityVId( $str, $website );
											if( $entityVId != false ) {
												$rows[ $groupId ][ $entityVId ] = $count;
											}
										}
									}
								}
								$counter->replace( $dbHistory, $period, $rows, $mergeRatio );
							}
							$period = $period->offset(1);
							$periodCode = $period->getCode();
						} while( $periodCode <= $stopCode && $periodCode != $startCode );


						$mergedTss = TwatchCounterAvailability::mergeTss( $counterAvail->getTimestamps( $periodTypeId ), $impTss );

						$counterAvail->timestamps[ $periodTypeId ] = $mergedTss;
						if( $allFromMonth && $periodTypeId == TwatchPeriod::MONTH ) {
							$counterAvail->timestamps[ TwatchPeriod::ALL ] = $mergedTss;
							$q = "INSERT IGNORE INTO ".$twatch->db->tableName( 'h', $website->getSub() )
							." SELECT ".TwatchPeriod::ALL.", '', cid, p1, p2, SUM(c) FROM ".$twatch->db->tableName( 'h', $website->getSub() )
							." WHERE cid = ".$counter->id." AND dtt = ".TwatchPeriod::MONTH." GROUP BY p1, p2";
							$twatch->db->query( $q );
						}

						$twatch->state->setInternal( TwatchState::COUNTERS_AVAIL, $counterId );

					} else {

						foreach( new ArdeXmlElemIter( $impCounter->element, TwatchPeriod::$typeImportTagNames[ $periodTypeId ] ) as $periodE ) {
							$period = TwatchPeriod::fromImportElement( $periodE );
							if( !$period->unionLength( $counterAvail->getTimestamps( $periodTypeId, true ) ) ) {
								continue;
							}



							if( $counter->getType() == TwatchCounter::TYPE_SINGLE ) {
								++$rowCount;
								$progress->set( $fileInfo->rowCount == 0 ? 0 : $rowCount / $fileInfo->rowCount );

								$count = ArdeXml::intContent( $periodE );
								$counter->replace( $dbHistory, $period, $count );


							} elseif( $counter->getType() == TwatchCounter::TYPE_LIST ) {
								$rows = array();
								foreach( new ArdeXmlElemIter( $periodE, 'row' ) as $rowE ) {
									++$rowCount;
									$progress->set( $fileInfo->rowCount == 0 ? 0 : $rowCount / $fileInfo->rowCount );

									$count = ArdeXml::intAttribute( $rowE, 'count' );
									$str = ArdeXml::strContent( $rowE );

									$entityVId = $gene->getStringEntityVId( $str, $website );
									if( $entityVId != false ) {
										$rows[ $entityVId ] = $count;
									}


								}
								$counter->replace( $dbHistory, $period, $rows );
							} else {
								$rows = array();
								foreach( new ArdeXmlElemIter( $periodE, 'group' ) as $groupE ) {
									$groupId = $groupGene->getStringEntityVId( ArdeXml::strAttribute( $groupE, 'name' ), $website );
									foreach( new ArdeXmlElemIter( $groupE, 'row' ) as $rowE ) {
										++$rowCount;
										$progress->set( $fileInfo->rowCount == 0 ? 0 : $rowCount / $fileInfo->rowCount );

										$count = ArdeXml::intAttribute( $rowE, 'count' );
										$str = ArdeXml::strContent( $rowE );

										$entityVId = $gene->getStringEntityVId( $str, $website );
										if( $entityVId != false ) {
											$rows[ $groupId ][ $entityVId ] = $count;
										}
									}
								}
								$counter->replace( $dbHistory, $period, $rows );
							}
						}
					}



				}



			}
		}
		if( !$xhtml ) {
			$p->pl( '<successful />' );
		}
	} else {
		throw new TwatchException( 'unknown action '.$_POST['a'] );
	}

	$p->end();
?>