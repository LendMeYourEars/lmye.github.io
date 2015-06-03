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
    
	require_once dirname(__FILE__).'/../lib/PageGlobal.php';
	require_once $twatch->path( 'lib/AdminPage.php' );
	
	$twatch->makeParentClass( 'AdminEntitiesPage', 'TwatchAdminPage' );
	
	class AdminEntitiesPage extends AdminEntitiesPageParent {
		
		protected function getToRoot() { return '..'; }
		
		protected function getTitle() { return 'Admin: Entities'; }
		
		protected function getSelectedLeftButton() { return 'entities'; }
		
		protected function printInHtmlHead( ArdePrinter $p ) {
			parent::printInHtmlHead( $p );
			$p->pl( '<script type="text/javascript" src="../js/Entity.js"></script>' );
		}
		
		protected function printBody( ArdePrinter $p ) {
			global $twatch;
			
			$entities = &$twatch->config->getList( TwatchConfig::ENTITIES );
			
			$p->pl( '<h1>Entities</h1>' );
			$p->pl( '<script type="text/javascript">/*<![CDATA[*/', 1 );
			$this->initJsPage( $p );
			$p->rel();
			$p->pl( '	entitiesHolder = new EntitiesHolder();' );
			$p->pl( '	entitiesHolder.insert();', 0 );
			
			foreach( $entities as $id => $entity ) {
				$vis = 'new ValueWithDefault( '.$this->selectedUser->data->get( TwatchUserData::VIEW_ENTITY, $id )
				.', '.ArdeJs::bool( $this->selectedUser->data->isDefault( TwatchUserData::VIEW_ENTITY, $id ) )
				.', '.$this->selectedUser->data->getDefault( TwatchUserData::VIEW_ENTITY, $id ).' )';
				$p->pl( 'entitiesHolder.addItem( '.$entity->jsObject( $vis ).' );' );
			}
			$p->pl( '/*]]>*/</script>' );
			
		}
	}
	
	$twatch->applyOverrides( array( 'AdminEntitiesPage' => true ) );
	
	$page = $twatch->makeObject( 'AdminEntitiesPage' );
	$page->render( $p );
?>