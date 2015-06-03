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
    
	require_once dirname(__FILE__).'/ArdeException.php';
	require_once dirname(__FILE__).'/../db/DbArdeErrorWriter.php';
	require_once dirname(__FILE__).'/ArdeSerializer.php';
	
	class ArdeDbErrorReporter implements ArdeErrorReporter {
		private $dbWriter;
		private $secondReporter;
		private $storeRestrictions;
		
		function __construct( ArdeDb $db, $sub, $tableName, ArdeErrorReporter $secondReporter = null, $storeRestrictions = false ) {
			$this->dbWriter = new ArdeDbDbErrorWriter( $db, $sub, $tableName, 100 );
			$this->secondReporter = $secondReporter;
			$this->storeRestrictions = $storeRestrictions;
		}
		
		public function reportError( ArdeException $e ) {
			if( $e->getType() != ArdeException::USER_ERROR || $this->storeRestrictions ) {
				$id = $this->dbWriter->write( ArdeSerializer::dataToString( $e ) );
				$e->safeExtras[] = 'Complete information about this error is logged with id '.$id;
			}
			if( $this->secondReporter !== null ) {
				$this->secondReporter->reportError( $e );
			}
		}
		
		public function getErrorsCount() {
			return $this->dbWriter->getErrorsCount();
		}
		
		public function getErrors() {
			$res = $this->dbWriter->getErrors();
			
			$o = array();
			foreach( $res as $id => $str ) {
				try {
					$o[ $id ] = ArdeSerializer::stringToData( $str );
				} catch( ArdeException $e ) {
					$o[ $id ] = $e;
				}
			}
			return $o;
		}
		
		public function clear() {
			$this->dbWriter->clear();
		}
		
		public function install( $overwrite ) {
			$this->dbWriter->install( $overwrite );
		}
		
		public function uninstall() {
			$this->dbWriter->uninstall();
		}
	}
?>