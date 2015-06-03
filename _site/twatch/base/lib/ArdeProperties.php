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

	require_once dirname(__FILE__).'/ArdeSerializer.php';
	require_once dirname(__FILE__).'/../db/DbArdeDataWriter.php';
	
	class ArdeDataWriterInfo {
		public $default = true;
	}
	
	class ArdePropertyChange {
		public $id;
		public $subId;
		public $value;
		public $position;
		public $delete;
		
		public function __construct( $id, $subId, $value, $position = null, $delete = false ) {
			$this->id = $id;
			$this->subId = $subId;
			$this->value = $value;
			$this->position = $position;
			$this->delete = $delete;
		}
		
	}
	
	class ArdePropertySorter {
		private $positions;
		
		public function __construct( $positions ) {
			$this->positions = $positions;
		}
		
		public function cmp( $key1, $key2 ) {
			if ( $this->positions[ $key1 ] == $this->positions[ $key2 ] ) return 0;
    		return ( $this->positions[ $key1 ] < $this->positions[ $key2 ] ) ? -1 : 1;
		}
	}
	
	class ArdePropertyChanges {
		public function hold() {}
		
		public function flush() {}
		
		public function setDelete( $id, $subId ) {} 
		
		public function setValue( $id, $subId, $value ) {}
		
		public function removeValue( $id, $subId ) {}
		
		public function setPosition( $id, $subId, $position ) {}
		
		public function removePosition( $id, $subId ) {}
		
		public function removeIdRange( $start, $end ) {}
		
		public function removeSubIdRange( $start, $end ) {}
	}
	
	abstract class ArdePropertyChangesDb extends ArdePropertyChanges {
		protected $dbWriter;
		
		public function __construct( ArdeDb $db = null, $sub, $tableName, $extraKeys = array() ) {
			if( $db == null ) $this->dbWriter = null;
			else $this->dbWriter = new ArdeDbDataWriter( $db, $sub, $tableName, $extraKeys );
		}

		abstract protected function getExtraKeyValues();
		
		public function setDelete( $id, $subId ) {
			$this->dbWriter->setValue( $id, $subId, '%x', $this->getExtraKeyValues() );
		} 
		
		public function setValue( $id, $subId, $value ) {
			$serializedValue = ArdeSerializer::dataToString( $value );
			$this->dbWriter->setValue( $id, $subId, $serializedValue, $this->getExtraKeyValues() );
		}
		
		public function clearId( $id, $subId = null ) {
			$this->dbWriter->clearId( $id, $subId );
		}
		
		public function removeValue( $id, $subId ) {
			$this->dbWriter->deleteValue( $id, $subId, $this->getExtraKeyValues() );
		}
		
		public function setPosition( $id, $subId, $position ) {
			$this->dbWriter->setPosition( $id, $subId, $position, $this->getExtraKeyValues() );
		}
		
		public function removePosition( $id, $subId ) {
			$this->dbWriter->deletePosition( $id, $subId, $this->getExtraKeyValues() );
		}
		
		public function copyData( $fromKeyValues, $toKeyValues ) {
			$this->dbWriter->copyData( $fromKeyValues, $toKeyValues );
		}
		
		public function clearData( $keyValues ) {
			$this->dbWriter->clearData( $keyValues );
		}
		
		public function removeIdRange( $start, $end ) {
			$this->dbWriter->deleteIdRange( $start, $end );
		}
		
		public function removeSubIdRange( $start, $end ) {
			$this->dbWriter->deleteSubIdRange( $start, $end );
		}
		
		protected $getChangesQueue = array();
		
		public function getChanges( $idsToGet ) {
			$res = $this->dbWriter->get( $idsToGet, $this->getExtraKeyValues() );
			return $this->translateRes( $res );
		}
		
		public function queueGetChanges( $key, $idsToGet ) {
			$this->dbWriter->queueGet( $key, $idsToGet, $this->getExtraKeyValues() );
		}
		
		public function rollGetChanges() {
			$ress = $this->dbWriter->rollGets();
			$out = array();
			foreach( $ress as $key => $res ) {
				$out[ $key ] = $this->translateRes( $res );
			}
			return $out;
		}
		
		protected function translateRes( $res ) {
			$out = array();
			foreach( $res as $r ) {
				$delete = false;
				if( $r->value !== null ) {
					if( $r->value == '%x' ) {
						$delete = true;
						$value = null;
					} else {
						try {
							$value = ArdeSerializer::stringToData( $r->value );
						} catch( ArdeException $e ) {	
							$value = null;
							$m = 'corrupted data in db for '.$r->id.'-'.$r->subId.'.';
							ArdeException::reportError( new ArdeException( $m, 0, $e ) );
						}
					}
				} else {
					$value = null;
				}
				if( $r->position !== null ) {
					$position = $r->position;
				} else {
					$position = null;
				}
				
				$out[] = new ArdePropertyChange( $r->id, $r->subId, $value, $position, $delete );
				
			}
			return $out;
		} 
		
		
		
		
		public function hold() {
			$this->dbWriter->hold();
		}
		
		public function flush() {
			$this->dbWriter->flush();
		}
		
		function install( $overwrite ) {
			$this->dbWriter->install( $overwrite );
		}
		
		function uninstall() {
			$this->dbWriter->uninstall();
		}
	}
	
	abstract class ArdeProperties {
		
		public $data = array();
		
		const LAYER_FIRST = 1;
		
		public $layer = self::LAYER_FIRST;  
		
		public $changes;
		
		public function __construct() {}
		
		abstract public function getLayerStartId( $layer );

		public function getLayerEndId( $layer ) {
			return $this->getLayerStartId( $layer + 1 ) - 1;
		}		
		
		public function getStartId() {
			return $this->getLayerStartId( $this->layer );
		}
		
		public function addDefaults( &$data ) {
			
			foreach( $data as $id => $a ) {
				
				if( !isset( $this->data[ $id ] ) ) {
					$this->data[ $id ] = array();
				}
				
				foreach( $a as $subId => $v ) {
					$this->data[ $id ][ $subId ] = $v;
				}
			}
		}
		
		protected function resetDefaults() {}
		
		public function getIdString( $id ) {
			return $id;
		}
		
		public function advanceLayerTo( $layer ) {
			$this->layer = $layer;
			$defaultData = $this->data;
			$this->reset();
			$this->addDefaults( $defaultData );
		}
		
		public function removeLayerChanges( $layer ) {
			$this->changes->removeIdRange( $this->getLayerStartId( $layer ), $this->getLayerEndId( $layer ) ) ;
			$this->changes->removeSubIdRange( $this->getLayerStartId( $layer ), $this->getLayerEndId( $layer ) );
		}
		
		protected function reset() {
			$this->data = array();
		}
		
		public function applyChanges( $changes ) {
			
			$repositions = array();
			
			foreach( $changes as $change ) {
				
				
				if( !$this->listExists( $change->id ) ) continue;
				
				
				if( $change->delete ) {
					$this->changeRequestRemove( $change->id, $change->subId );
				}  elseif( $change->value !== null ) {
					$this->changeRequestAlter( $change->id, $change->subId, $change->value );
				}
				
				if( $change->position !== null ) {
					$repositions[ $change->id ][ $change->subId ] = $change->position;
				}
			}
			
			foreach( $repositions as $id => $a ) {
				$positions = array();
				$i = 0;
				foreach( $this->data[ $id ] as $subId => $value ) {
					if( isset( $repositions[ $id ][ $subId ] ) ) $positions[ $subId ] = $repositions[ $id ][ $subId ];
					else $positions[ $subId ] = $i;
					++$i;
				}
				$sorter = new ArdePropertySorter( $positions );
				uksort( $this->data[ $id ], array( $sorter, 'cmp' ) );
			}
		}
		
		public function &get( $id, $subId = 0 ) {
			if( !isset( $this->data[ $id ][ $subId ] ) ) throw new TwatchException( 'data not found: '.$this->getIdString( $id ).' - '.$subId );
			return $this->data[ $id ][ $subId ];
		}
		
		public function &getList( $id ) {
			if( !isset( $this->data[ $id ] ) ) throw new TwatchException( 'data not found: '.$this->getIdString( $id ) );
			return $this->data[ $id ];
		}
		
		function propertyExists( $id, $subId = 0 ) {
			return isset( $this->data[$id][$subId] );
		}

		function listExists( $id ) {
			return isset( $this->data[$id] );
		}
		
		protected function changeRequestRemove( $id, $subId ) {
			unset( $this->data[ $id ][ $subId ] );
		}
		
		protected function changeRequestAlter( $id, $subId, $newValue ) {
			$this->data[ $id ][ $subId ] = $newValue;
		}
		
		public function set( $value, $id, $subId = 0 ) {
			$this->changes->hold();
			try {
				if( !isset( $this->data[ $id ][ $subId ] ) ) {
					$this->changes->setPosition( $id, $subId, count( $this->data[$id] ) );
				}
				
				$this->data[ $id ][ $subId ] = $value;
				$this->changes->setValue( $id, $subId, $value );
	
			} catch( Exception $e ) {
				$this->changes->flush();
				throw $e;
			}
			$this->changes->flush();
		}
		
		public function setInternal( $id, $subId = 0 ) {
			if( !isset( $this->data[ $id ][ $subId ] ) ) throw new TwatchException( 'data not found: '.$this->getIdString( $id ).' - '.$subId );
			$this->changes->setValue( $id, $subId, $this->data[ $id ][ $subId ] );
		}
	}
	
	abstract class ArdeAdvancedProperties extends ArdeProperties {
		
		public $defaults = array();
		protected $defaultPos = array();
		
		protected function reset() {
			parent::reset();
			$this->defaults = array();
			$this->defaultPos = array();
		}
		
		public function addDefaults( &$data ) {
			parent::addDefaults( $data );
			
			foreach( $data as $id => $a ) {
				
				if( !isset( $this->defaults[ $id ] ) ) {
					$this->defaults[ $id ] = array();
					$this->defaultPos[ $id ] = array();
				}
				
				$i = 0;
				foreach( $a as $subId => $v ) {
					$this->defaults[ $id ][ $subId ] = $v;
					$this->defaultPos[ $id ][ $subId ] = $i;
					++$i;
				}
					
			}			
		}
		
		public function remove( $id, $subId = 0 ) {

			if( !isset( $this->data[ $id ][ $subId ] ) ) throw new TwatchException( 'data not found: '.$this->getIdString( $id ).' - '.$subId );
			
			$pos = 0;
			reset( $this->data[ $id ] );
			while( key( $this->data[ $id ] ) != $subId ) {
				next( $this->data[ $id ] );
				++$pos;
			}
			
			
			unset( $this->data[ $id ][ $subId ] );
			
			$this->changes->hold();
			
			try {
				if( isset( $this->defaults[ $id ][ $subId ] ) ) {
					$this->removeDefault( $id, $subId );					
				} else {
					$this->changes->removeValue( $id, $subId );
				}
				$this->changes->removePosition( $id, $subId );
				
				while( key( $this->data[ $id ] ) !== null ) {
					$subId = key( $this->data[ $id ] );
					if( isset( $this->defaultPos[ $id ][ $subId ] ) && $this->defaultPos[ $id ][ $subId ] == $pos ) {
						$this->changes->removePosition( $id, $subId, $pos );
					} else {
						$this->changes->setPosition( $id, $subId, $pos );
					}
					next( $this->data[ $id ] );
					++$pos;
				}
			} catch( Exception $e ) {
				$this->changes->flush();
				throw $e;
			}

			$this->changes->flush();
			
		}
		
		protected function removeDefault( $id, $subId ) {
			$this->changes->setDelete( $id, $subId );
		}
		
		function hasDefault( $id, $subid = 0 ) {
			return isset( $this->defaults[$id][$subid] );
		}
		
		function getDefault( $id, $subid = 0 ) {
			if( !isset( $this->defaults[$id][$subid] ) ) throw new ArdeException( 'default value for data '.$id.'-'.$subid.' not found' );
			return $this->defaults[$id][$subid];
		}
		
		public function insertBefore( $value, $id, $subId, $referenceSubId ) {
			if( !isset( $this->data[ $id ][ $referenceSubId ] ) ) throw new TwatchException( 'data not found: '.$this->getIdString( $id ).' - '.$referenceSubId );
			$this->changes->hold();
			try {
				$this->_insertBeforeAndRepos( $value, $id, $subId, $referenceSubId );
				$this->changes->setValue( $id, $subId, $value );
			} catch( Exception $e ) {
				$this->changes->flush();
				throw $e;
			}
			$this->changes->flush();
		}
		
		protected function _insertBeforeAndRepos( $value, $id, $subId, $referenceSubId ) {
	
			$newList = array();
			$pos = 0;
			reset( $this->data[ $id ] );
			
			while( key( $this->data[ $id ] ) != $referenceSubId ) {
				$newList[ key( $this->data[ $id ] ) ] = current( $this->data[ $id ] );
				next( $this->data[ $id ] );
				++$pos;
			}
			
			$newList[ $subId ] = $value;	
			$this->reposition( $id, $subId, $pos );
			++$pos;
			
			while( key( $this->data[ $id ] ) !== null ) {
				$sId = key( $this->data[ $id ] );
				$newList[ $sId ] = current( $this->data[ $id ] );
				$this->reposition( $id, $sId, $pos );
				next( $this->data[ $id ] );
				++$pos;
			}
			$this->data[ $id ] = $newList;

		}
		
		public function insertBeforePosition( $value, $id, $subId, $position ) {
			$referenceSubId = $this->getSubIdAtPosition( $id, $position );
			if( $referenceSubId === null ) return $this->addToBottom( $value, $id, $subId );
			$this->insertBefore( $value, $id, $subId, $referenceSubId );
		}
		
		protected function getSubIdAtPosition( $id, $position ) {
			$i = 0;
			foreach( $this->data[ $id ] as $subId => $v ) {
				if( $i == $position ) return $subId;
				++$i;
			}
			return null;
		}
		
		public function addToBottom( $value, $id, $subId = 0 ) {
			$this->changes->hold();
			try {
				$this->_addToBottomAndRepos( $value, $id, $subId );
				$this->changes->setValue( $id, $subId, $value );
			} catch( Exception $e ) {
				$this->changes->flush();
				throw $e;
			}
			$this->changes->flush();
		}
		
		protected function _addToBottomAndRepos( $value, $id, $subId = 0 ) {
			$this->data[ $id ][ $subId ] = $value;	
			$this->reposition( $id, $subId, count( $this->data[ $id ] ) - 1 );
		}
		
		public function addToTop( $value, $id, $subId = 0 ) {
			$this->changes->hold();
			try {
				$this->_addToTopAndRepos( $value, $id, $subId );
				$this->changes->setValue( $id, $subId, $value );
			} catch( Exception $e ) {
				$this->changes->flush();
				throw $e;
			}
			$this->changes->flush();
		}
		
		protected function _addToTopAndRepos( $value, $id, $subId = 0 ) {	
				
			$newList = array( $subId => $value );
			$this->reposition( $id, $subId, 0 );
			
			$i = 1;
			foreach( $this->data[ $id ] as $subId => $v ) {
				$newList[ $subId ] = $v;
				$this->reposition( $id, $subId, $i );
				++$i;
			}
			
			$this->data[ $id ] = $newList;
		}

		
		
		
		public function moveUp( $id, $subId ) {
			reset( $this->data[ $id ] );
			if( key( $this->data[ $id ] ) == $subId ) return;
			$prevSubId = null;
			$targetPrevSubId = null;
			$targetI = null;
			$i = 0;
			$newList = array();
			foreach( $this->data[ $id ] as $sId => $v ) {
				if( $sId == $subId ) {
					$newList[ $sId ] = $this->data[ $id ][ $sId ];
					$targetPrevSubId = $prevSubId;
					$targetI = $i;
				} else {
					if( $prevSubId !== null ) $newList[ $prevSubId ] = $this->data[ $id ][ $prevSubId ];
					$prevSubId = $sId;
				}	
				++$i;
			}
			$newList[ $prevSubId ] = $this->data[ $id ][ $prevSubId ];
			$this->data[ $id ] = $newList;
			
			$this->changes->hold();
			try {
				$this->reposition( $id, $subId, $targetI - 1 );
				$this->reposition( $id, $targetPrevSubId, $targetI );
			} catch( Exception $e ) {
				$this->changes->flush();
				throw $e;
			}
			$this->changes->flush();
		}
		
		public function moveDown( $id, $subId ) {
			end( $this->data[ $id ] ) ;
			if( key( $this->data[ $id ] ) == $subId ) return;
			$prevSubId = null;
			$targetNextSubId = null;
			$targetI = null;
			$i = 0;
			$newList = array();
			foreach( $this->data[ $id ] as $sId => $v ) {
				if( $prevSubId == $subId && $targetNextSubId === null ) {
					$newList[ $sId ] = $this->data[ $id ][ $sId ];
					$targetNextSubId = $sId;
					$targetI = $i - 1;
				} else {
					if( $prevSubId !== null ) $newList[ $prevSubId ] = $this->data[ $id ][ $prevSubId ];
					$prevSubId = $sId;
				}
				++$i;
			}
			$newList[ $prevSubId ] = $this->data[ $id ][ $prevSubId ];
			$this->data[ $id ] = $newList;
			
			$this->changes->hold();
			try {
				$this->reposition( $id, $subId, $targetI + 1 );
				$this->reposition( $id, $targetNextSubId, $targetI );
			} catch( Exception $e ) {
				$this->changes->flush();
				throw $e;
			}
			$this->changes->flush();
			

		}
		
		protected function reposition( $id, $subId, $newPosition ) {
			if( isset( $this->defaultPos[ $id ][ $subId ] ) && $this->defaultPos[ $id ][ $subId ] == $newPosition ) {
				$this->changes->removePosition( $id, $subId );
			} else {
				$this->changes->setPosition( $id, $subId, $newPosition );
			}
		}
		
		public function getNewSubId( $id ) {
			return max( $this->getHighestSubId( $id ), $this->getLayerStartId( $this->layer ) ) + 1;
		}
		
		protected function getHighestSubId( $id ) {
			if( !isset( $this->data[$id] ) ) throw new TwatchException( 'data list '.$id.' not found' );
			$maxSubId = 0;
			foreach( $this->data[ $id ] as $subId => $v ) {
				if( $subId > $maxSubId ) $maxSubId = $subId;
			}
			return $maxSubId;
		}
		
		public function hold() { $this->changes->hold(); }
		
		public function flush() { $this->changes->flush(); }
	}
	
	abstract class ArdeAdminProperties extends ArdeAdvancedProperties {
		protected $dataInfo = array();
		protected $deletedDefaults = array();
		
		protected function reset() {
			parent::reset();
			$this->dataInfo = array();
			$this->deletedDefaults = array();
		}
		
		public function addDefaults( &$data ) {
			parent::addDefaults( $data );
			
			foreach( $data as $id => $a ) {
				foreach( $a as $subId => $v ) {
					$this->dataInfo[ $id ][ $subId ] = new ArdeDataWriterInfo();
				}		
			}			
		}
		
		protected function removeDefault( $id, $subId ) {
			parent::removeDefault( $id, $subId );
			$this->deletedDefaults[ $id ][ $subId ] = true;
		}
		
		public function restoreDefault( $id, $subId = 0 ) {
			if( !isset( $this->defaults[ $id ][ $subId ] ) ) throw new ArdeException( 'data '.$this->getIdString( $id ).'-'.$subId.' has no default' );
			$this->changes->removeValue( $id, $subId );
			$this->data[$id][$subId] = $this->defaults[$id][$subId];
		}
		
		protected function changeRequestRemove( $id, $subId ) {
			parent::changeRequestRemove( $id, $subId );
			$this->deletedDefaults[ $id ][ $subId ] = true;
		}
		
		protected function changeRequestAlter( $id, $subId, $newValue ) {
			parent::changeRequestAlter( $id, $subId, $newValue );
			if( !isset( $this->dataInfo[ $id ][ $subId ] ) ) {
				$this->dataInfo[ $id ][ $subId ] = new ArdeDataWriterInfo();
			}
			$this->dataInfo[ $id ][ $subId ]->default = false;
		}
		
		public function isDefault( $id, $subId = 0 ) {
			return $this->dataInfo[ $id ][ $subId ]->default;
		} 
		
		function getDeletedDefaults( $id ) {
			$res = array();
			if( isset( $this->deletedDefaults[ $id ] ) ) {
				foreach( $this->deletedDefaults[ $id ] as $subId => $v ) {
					$res[ $subId ] = $this->defaultPos[ $id ][ $subId ];
				}
			}
			asort( $res );
			return $res;
		}
		
		function restoreDeletedDefaults( $id, $position = self::RESTORE_POS_BOTTOM ) {
			$delDefs = $this->getDeletedDefaults( $id );
			$this->changes->hold();
			try {
				foreach( $delDefs as $subId => $pos ) {
					$this->restoreDeletedDefault( $id, $subId, $position );
				}
			} catch( Exception $e ) {
				$this->changes->flush();
				throw $e;
			}
			$this->changes->flush();
		}
		
		const RESTORE_POS_BOTTOM = 1;
		const RESTORE_POS_TOP = 2;
		const RESTORE_POS_INSERT = 3;
		
		function restoreDeletedDefault( $id, $subId, $position = self::RESTORE_POS_BOTTOM ) {
			if( !isset( $this->deletedDefaults[ $id ][ $subId ] ) ) throw new TwatchException( 'data '.$this->getIdString( $id ).'-'.$subId.' is not a deleted default' );
			unset( $this->deletedDefaults[ $id ][ $subId ] );
			$this->changes->hold();
			try {
				if( !count( $this->deletedDefaults[ $id ] ) ) unset( $this->deletedDefaults[ $id ] );
				if( $position == self::RESTORE_POS_BOTTOM ) $this->_addToBottomAndRepos( $this->defaults[ $id ][ $subId ], $id, $subId );
				elseif( $position == self::RESTORE_POS_TOP ) $this->_addToTopAndRepos( $this->defaults[ $id ][ $subId ], $id, $subId );
				else {
					$referenceSubId = $this->getSubIdAtPosition( $id, $this->defaultPos[ $id ][ $subId ] );
					if( $referenceSubId === null ) $this->_addToBottomAndRepos( $this->defaults[ $id ][ $subId ], $id, $subId );
					else $this->_insertBeforeAndRepos( $this->defaults[ $id ][ $subId ], $id, $subId, $referenceSubId );
				}
				$this->changes->removeValue( $id, $subId );
			} catch( Exception $e ) {
				$this->changes->flush();
				throw $e;	
			}
			$this->changes->flush();
		}
		
	}
?>