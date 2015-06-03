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

	require_once $ardeCountry->path( 'lib/Country.php' );
	
	class ArdeCountryInstaller {
		public function install( ArdePrinter $p, $source, $overwrite ) {
			global $ardeCountry;

			
			
			$p->pn( '<p>Installing ip-to-country configuration table... ' );
			
			require_once $ardeCountry->path( 'data/DataGlobal.php' );
			
			$ardeCountry->config = new ArdeCountryConfig( $ardeCountry->db );
			$ardeCountry->config->install( $overwrite );
			$ardeCountry->config->addDefaults( ArdeCountryConfig::$defaultProperties );
			$ardeCountry->config->applyAllChanges();
			$ardeCountry->config->set( $ardeCountry->makeInstanceId(), ArdeCountryConfig::INSTANCE_ID );
			$source = (int)$source;
			if( !isset( ArdeCountryApp::$sourceNames[ $source ] ) ) {
				throw new ArdeUserError( 'invalid database source' );
			}
			$ardeCountry->config->set( $source, ArdeCountryConfig::DB_SOURCE );
			$p->pl( '<span class="good">successful</span></p>' );
			
			if( $source == ArdeCountryApp::SOURCE_SOFTWARE77 ) $fileName = 'IpToCountry.csv';
			else $fileName = 'ip-to-country.csv';
			
			$filePath = $ardeCountry->path( $fileName );
			
			$p->pn( '<p>looking for '.$fileName.' file... ' );
			if( !file_exists( $filePath ) ) {
				throw new ArdeException( 'ip-to-country file does not exist' );
			}
			$p->pl( '<span class="good">exists</span></p>' );

			$p->pn( '<p>creating ip-to-country table... ' );
			$dbCountry = new ArdeDbCountry( $ardeCountry->db );
			$dbCountry->install( $overwrite );
			$p->pl( '<span class="good">successful</span></p>' );

			$p->pn( '<p>opening ip-to-country file... ' );
			
			if( !( $f = fopen( $filePath,'rb' ) ) ) throw new ArdeException( 'error opening the ip-to-country file' );
			if( !( $sz = filesize( $filePath ) ) ) throw new ArdeException( "can't get file size" );
			$sz = ( int )( $sz / 1024 );
			$p->pl( '<span class="good">successful</span></p>' );

			$p->pl( '<p>inserting file data into database... </p>' );
			$lno = 0;

			$nextSizeReport = $reportSizeSteps = (int)( $sz / 5 );

			while( !feof( $f ) ) {
				if( !( $ln = fgets( $f, 4096 ) ) ) break;

				$currentSize = (int)( ftell( $f ) / 1024 );

				if( $currentSize > $nextSizeReport ) {
					$p->pl( '<p>'.$currentSize.' of '.$sz.' KBytes</p>' );
					$nextSizeReport += $reportSizeSteps;
				}

				++$lno;
				while( strlen( $ln ) && ( ord( $ln[ strlen( $ln ) - 1 ] ) == 10 || ord( $ln[ strlen( $ln ) - 1 ] ) == 13 ) ) {
					$ln = substr( $ln, 0, -1 );
				}

				if( $ln[0] == '#' ) continue;
				
				if( ! preg_match_all( '/\"([^\"]+)\"(?:\,|$)/', $ln, $matches ) )  {
					ArdeException::reportError( new ArdeWarning( "error in file line ".$lno.": line ignored" ) );
					continue;
				}
				if( count( $matches ) != 2 || count( $matches[1] ) < ( $source==ArdeCountryApp::SOURCE_SOFTWARE77 ? 5 : 3 ) ) {
					ArdeException::reportError( new ArdeWarning( "error in file line ".$lno.": line ignored" ) );
					continue;
				}
				$ipFrom = (double)$matches[1][0];
				$ipTo = (double)$matches[1][1];
				if( $source==ArdeCountryApp::SOURCE_SOFTWARE77 ) {
					$code = strtolower( $matches[1][4] );
				} else {
					$code = strtolower( $matches[1][2] );
				}
				if( $code == 'zz' || $code == 'ap' ) continue;
				$id = ArdeCountry::getCodeId( $code );
				if( $id === null ) {
					if( $lno == 1 && $code=='00' ) continue;
					ArdeException::reportError( new ArdeWarning( "error in file line ".$lno.": line ignored, unknown country code (".$code.")" ) );
					continue;
				} elseif( $id == ArdeCountry::GB ) {
					$id = ArdeCountry::UK;
				}
				$dbCountry->addRow( $ipFrom, $ipTo, $id );


			}
			if((int)( ftell( $f ) / 1024 ) != $sz ) {
				$p->pl( '<p>'.$sz.' of '.$sz.' KBytes</p>' );
			}
			$dbCountry->flushRows();

			$p->pl('<p><span class="good">ip-to-country installed successfully</span></p>');
		}

		public function uninstall( ArdePrinter $p ) {
			global $ardeCountry;
			$p->pn( '<p>removing ip to country table... ' );
			$dbCountry = new ArdeDbCountry( $ardeCountry->db );
			$dbCountry->uninstall();
			$p->pl( '<span class="good">successful</span></p>' );
			
			$p->pn( '<p>removing ip to country config table... ' );
			$ardeCountry->config = new ArdeCountryConfig( $ardeCountry->db );
			$ardeCountry->config->uninstall();
			$p->pl( '<span class="good">successful</span></p>' );
		}
	}
?>