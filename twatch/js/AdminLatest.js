
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
    
function AdminLatest( del ) {
	
	this.del = del;
	
	this.ArdeComponent( 'div' );
	this.cls( 'block' );
	
	tb = ardeE( 'tbody' ).appendTo( new ArdeTable().cls( 'form' ).appendTo( this ) );
	tr = ardeE( 'tr' ).append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'delete sessions: ' ) ) ).appendTo( tb );
	this.delInput = new ArdeInput( this.del ).attr( 'size', '2' );
	tr.append( ardeE( 'td' ).cls( 'tail' ).append( this.delInput ).append( ardeT( ' days old and older.' ) ) );
	
	this.applyButton = new ArdeRequestButton( 'Apply Changes' );
	this.restoreButton = new ArdeRequestButton( 'Restore Defaults' );
	this.cleanupButton = new ArdeRequestButton( 'Cleanup Now' );
	this.resetButton = new ArdeRequestButton( 'Reset', 'Are you sure? This will delete any Latest Visitors related data currently in database.' ).setCritical( true );
	this.append( ardeE( 'p' ).append( this.applyButton ).append( this.restoreButton ).append( this.cleanupButton ).append( this.resetButton ) );
	
	var self = this;
	
	this.applyButton.setStandardCallbacks( this, 'apply' );
	this.restoreButton.setStandardCallbacks( this, 'restore' );
	this.cleanupButton.setStandardCallbacks( this, 'cleanup' );
	this.resetButton.setStandardCallbacks( this, 'reset' );
}

AdminLatest.fromXml = function ( element ) {
	return new AdminLatest( ArdeXml.intAttribute( element, 'delete' ) );
};

AdminLatest.prototype.getParams = function () {
	return 'd='+ardeEscape( this.delInput.element.value );
};

AdminLatest.prototype.cleanupClicked = function () {
	this.cleanupButton.request( twatchFullUrl( 'rec/rec_latest.php' ), 'a=cleanup' );
};

AdminLatest.prototype.resetClicked = function () {
	this.resetButton.request( twatchFullUrl( 'rec/rec_latest.php' ), 'a=reset' );
};

AdminLatest.prototype.applyClicked = function () {
	this.applyButton.request( twatchFullUrl( 'rec/rec_latest.php' ), 'a=change_latest&'+this.getParams() );
};

AdminLatest.prototype.applyConfirmed = function ( result ) {};

AdminLatest.prototype.restoreClicked = function () {
	this.restoreButton.request( twatchFullUrl( 'rec/rec_latest.php' ), 'a=restore_latest', AdminLatest );
};

AdminLatest.prototype.restoreConfirmed = function ( latest ) {
	this.replace( latest );
	latest.restoreButton.ok();
};

ArdeClass.extend( AdminLatest, ArdeComponent );


function AdminLatestPage( perPage, priItems, secItems, vTypeSel, visibility  ) {
	
	this.perPage = perPage;
	
	this.priItems = priItems;
	this.secItems = secItems;
	
	this.vTypeSel = vTypeSel;
	
	
	this.ArdeComponent( 'div' );
	
	this.visSelect = new BinarySelector( visibility, 'Visible', 'Hidden', 'Default' );
	this.visApplyButton = new ArdeRequestButton( 'Apply' );
	this.visApplyButton.setStandardCallbacks( this, 'visApply' );
	this.append( ardeE( 'p' ).append( ardeT( 'Latest Visitors Page Visibility: ' ) ).append( this.visSelect ).append( ardeT( ' ' ) ).append( this.visApplyButton ) );

	this.mainBlock = new ArdeComponent( 'div' ).cls( 'block' ).appendTo( this );
	
	var tb = ardeE( 'tbody' ).appendTo( new ArdeTable().cls( 'form' ).appendTo( this.mainBlock ) );
	var tr = ardeE( 'tr' ).append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'sessions per page:' ) ) ).appendTo( tb );
	this.perPageInput = new ArdeInput( this.perPage ).attr( 'size', '2' );
	tr.append( ardeE( 'td' ).cls( 'tail' ).append( this.perPageInput ) );
	
	var div = ardeE( 'div' ).cls( 'margin_std' ).appendTo( this.mainBlock );
	div.append( ardeT( 'Default Visitor Type Selection: ' ) );
	
	this.vTypeChecks = [];

	for( var i in vTypes ) {
		var label = ardeE( 'label' ).appendTo( div );
		this.vTypeChecks[ i ] = new ArdeCheckBox( typeof this.vTypeSel[ vTypes[i].id ] != 'undefined' );
		this.vTypeChecks[ i ].value = vTypes[i].id;
		label.append( this.vTypeChecks[ i ] );
		
		var vTypeIcon = new ArdeImg( twatchUrl+'img/vtype_s/'+vTypes[ i ].id+'.gif', null, 22, 22 );
		vTypeIcon.style( 'verticalAlign', '-4px' );
		
		label.append( vTypeIcon ).append( ardeT( vTypes[i].name + ardeNbsp( 3 ) ) );
	}
	
	
	this.mainBlock.append( ardeE( 'h3' ).append( ardeT( 'Primary Items' ) ) );
	var d = ardeE( 'div' ).cls( 'indent_pad' ).appendTo( this.mainBlock );
	this.priItemsList = new ArdeCompListComp( 'div' ).appendTo( d );
	for( var i in this.priItems ) {
		this.priItemsList.addItem( this.priItems[i] );
	}
	this.newPriItem = new NewLatestPageItem( this.priItemsList ).appendTo( d );
	
	this.mainBlock.append( ardeE( 'h3' ).append( ardeT( 'Secondary Items' ) ) );
	d = ardeE( 'div' ).cls( 'indent_pad' ).appendTo( this.mainBlock );
	this.secItemsList = new ArdeCompListComp( 'div' ).appendTo( d );
	for( var i in this.secItems ) {
		this.secItemsList.addItem( this.secItems[i] );
	}
	this.newSecItem = new NewLatestPageItem( this.secItemsList ).appendTo( d );
	
	this.applyButton = new ArdeRequestButton( 'Apply Changes' );
	this.restoreButton = new ArdeRequestButton( 'Restore Defaults' );
	this.mainBlock.append( ardeE( 'p' ).append( this.applyButton ).append( this.restoreButton ) );
	
	var self = this;

	this.applyButton.setStandardCallbacks( this, 'apply' );
	this.restoreButton.setStandardCallbacks( this, 'restore' );
	
	
}

AdminLatestPage.prototype.visApplyClicked = function () {
	this.visApplyButton.request( twatchFullUrl( 'rec/rec_latest.php' ), 'a=set_vis&'+this.visSelect.getParams() );
};

AdminLatestPage.fromXml = function ( element ) {
	var perPage = ArdeXml.intAttribute( element, 'per_page' );
	
	var pItemEs = new ArdeXmlElemIter( ArdeXml.element( element, 'pri_items' ), 'item' );
	var priItems = [];
	while( pItemEs.current ) {
		priItems.push( LatestPageItem.fromXml( pItemEs.current ) );
		pItemEs.next();
	}
	
	var sItemEs = new ArdeXmlElemIter( ArdeXml.element( element, 'sec_items' ), 'item' );
	var secItems = [];
	while( sItemEs.current ) {
		secItems.push( LatestPageItem.fromXml( sItemEs.current ) );
		sItemEs.next();
	}
	
	var vtSelEs = new ArdeXmlElemIter( ArdeXml.element( element, 'vt_selection' ), 'vt' );
	var vtSels = {};
	while( vtSelEs.current ) {
		vtSels[ ArdeXml.intAttribute( vtSelEs.current, 'id' ) ] = true;
		vtSelEs.next();
	}
	
	return new AdminLatestPage( perPage, priItems, secItems, vtSels );
};

AdminLatestPage.prototype.getParams = function () {
	var items = '';
	for( var i in this.priItemsList.items ) {
		items += '&'+this.priItemsList.items[i].getParams( 'pi'+i+'_' );
	}
	for( var i in this.secItemsList.items ) {
		items += '&'+this.secItemsList.items[i].getParams( 'si'+i+'_' );
	}
	var vtSel = new ArdeAppender( '_' );
	for( var i in this.vTypeChecks ) {
		if( this.vTypeChecks[i].element.checked ) vtSel.append( this.vTypeChecks[i].value );
	}
	return 'pp='+ardeEscape( this.perPageInput.element.value )+'&vts='+vtSel.s+('&pic='+this.priItemsList.items.length)+('&sic='+this.secItemsList.items.length)+items;
};

AdminLatestPage.prototype.check = function () {
	var found = false;
	for( var i in this.vTypeChecks ) if( this.vTypeChecks[i].element.checked ) found = true;
	if( !found ) return ardeAlert( 'Please choose at least one visitor type.' );
	return true;
}

AdminLatestPage.prototype.applyClicked = function () {
	if( !this.check() ) return;
	this.applyButton.request( twatchFullUrl( 'rec/rec_latest.php' ), 'a=change_page&'+this.getParams() );
};

AdminLatestPage.prototype.applyConfirmed = function ( result ) {};

AdminLatestPage.prototype.restoreClicked = function () {
	this.restoreButton.request( twatchFullUrl( 'rec/rec_latest.php' ), 'a=restore_page', AdminLatestPage );
};

AdminLatestPage.prototype.restoreConfirmed = function ( latestPage ) {
	this.replace( latestPage );
	latestPage.restoreButton.ok();
};

ArdeClass.extend( AdminLatestPage, ArdeComponent );



function LatestPageItem( entityId, entityView, lookup, title, notFound ) {
	
	this.entityId = entityId;
	this.entityView = entityView;
	this.lookup = lookup;
	this.title = title;
	this.notFound = notFound;
	
	this.ArdeComponent( 'div' );
	this.cls( 'pad_half' );
	
	if( this instanceof NewLatestPageItem ) {
		this.addClass( 'special_sub_block' );
	} else {
		this.addClass( 'sub_block' );
	}
	
	var p = ardeE( 'p' ).cls( 'margin_half' ).appendTo( this );
	
	this.entitySelect = new ArdeSelect();
	this.invalidEntityOption = null;
	
	if( this instanceof NewLatestPageItem ) {
		this.entitySelect.addDummyOption( 'Select...' );
	} else {
		var found = false;
		for( var i in itemEntities ) {
			if( i == this.entityId ) {
				found = true;
			}
		}
		if( !found ) {
			this.invalidEntityOption = new ArdeOption( '', '' );
			this.entitySelect.append( this.invalidEntityOption );
			this.entitySelect.style( 'background', '#800' ).style( 'color', '#fff' );
		}
	}
	for( var i in itemEntities ) {
		var option = ardeE( 'option' ).append( ardeT( itemEntities[i] ) ).attr( 'value', i );
		this.entitySelect.append( option );
		if( i == this.entityId ) {
			option.n.selected = true;
		}
	}
	
	p.append( ardeT( 'entity: ' ) ).append( this.entitySelect );
	
	this.lookupSelect = new ArdeSelect();
	for( var i in LatestPageItem.lookupStrings ) {
		var option = ardeE( 'option' ).append( ardeT( LatestPageItem.lookupStrings[i] ) ).attr( 'value', i );
		this.lookupSelect.append( option );
		if( i == this.lookup ) option.n.selected = true;
	}
	p.append( ardeT( ' lookup: ' ) ).append( this.lookupSelect );
	
	var title = this.title == null ? '' : this.title;
	this.titleInput = new ArdeInput( title ).attr( 'size', '15' );
	p.append( ardeT( ' title: ' ) ).append( this.titleInput );
	
	var notFound = this.notFound == null ? '' : this.notFound;
	this.notFoundInput = new ArdeInput( notFound ).attr( 'size', '25' );
	p.append( ardeT( ' not found: ' ) ).append( this.notFoundInput );
	
	this.entityView.setEntityId( this.entityId );
	p.append( ardeE( 'br' ) ).append( this.entityView );
	
	var self = this;
	
	this.entitySelect.element.onchange = function () {
		self.entitySelectChanged();
	};
	
	if( this instanceof NewLatestPageItem ) {
		
		this.addButton = new ArdeButton( 'Add' ).cls( 'passive' );
		this.addButton.element.onclick = function () {
			if( !self.check() ) return;
			self.ardeList.addItem( self.getItem() );
			self.clear();
		};
		
		p.append( ardeT( ardeNbsp(10) ) ).append( this.addButton );
		
	} else {
		
		this.upButton = new ArdeButton( 'Up' ).cls( 'passive' );
		this.downButton = new ArdeButton( 'Down' ).cls( 'passive' );
		this.deleteButton = new ArdeButton( 'Remove' ).cls( 'passive' );
		
		this.upButton.element.onclick = function () { self.ardeList.moveItemUp( self ); };
		this.downButton.element.onclick = function () { self.ardeList.moveItemDown( self ); };
		this.deleteButton.element.onclick = function () { self.ardeList.removeItem( self ); };
		
		p.append( ardeT( ardeNbsp(10) ) ).append( this.upButton ).append( ardeT( ' ' ) ).append( this.downButton ).append( ardeT( ' ' ) ).append( this.deleteButton );
	}
	
	
	
	
	
	
}

LatestPageItem.LOOKUP_FIRST=0;
LatestPageItem.LOOKUP_LAST=1;

LatestPageItem.lookupStrings = {};
LatestPageItem.lookupStrings[ LatestPageItem.LOOKUP_FIRST ] = 'first';
LatestPageItem.lookupStrings[ LatestPageItem.LOOKUP_LAST ] = 'last';

LatestPageItem.fromXml = function ( element ) {
	var entityId = ArdeXml.intAttribute( element, 'entity_id' );
	var entityView = EntityView.fromXml( ArdeXml.element( element, 'entity_view' ) );
	var lookup = ArdeXml.intAttribute( element, 'lookup' );
	var title = ArdeXml.strElement( element, 'title', null );
	var notFound = ArdeXml.strElement( element, 'not_found', null );
	return new LatestPageItem( entityId, entityView, lookup, title, notFound );
};

LatestPageItem.prototype.getParams = function ( prefix ) {
	return prefix+'ei='+this.entitySelect.selectedOption().value+'&'+this.entityView.getParams( prefix+'ev_' )+'&'+prefix+'l='+this.lookupSelect.selectedOption().value+
			'&'+prefix+'t='+ardeEscape( this.titleInput.element.value )+'&'+prefix+'nf='+ardeEscape( this.notFoundInput.element.value );
};

LatestPageItem.prototype.entitySelectChanged = function () {
	if( this.invalidEntityOption !== null ) {
		if( this.entitySelect.element.selectedIndex == 0 ) return;
		this.invalidEntityOption.remove();
		this.entitySelect.style( 'background', '#fff' ).style( 'color', '#000' );
	}
	
	var selectedOption = this.entitySelect.selectedOption();
	
	
	
	if( selectedOption === null ) id = 0;
	else id = selectedOption.value;
	this.entityView.setEntityId( id );
};

ArdeClass.extend( LatestPageItem, ArdeComponent );

function NewLatestPageItem( ardeList ) {
	this.ardeList = ardeList;
	this.LatestPageItem( null, new EntityView( true, false, false ), LatestPageItem.LOOKUP_LAST, '', '' );
};

NewLatestPageItem.prototype.check = function () {
	if( this.entitySelect.selectedOption() == null ) {
		alert( 'select a data please' );
		return false;
	}
	return true;
};

NewLatestPageItem.prototype.clear = function () {
	this.entitySelect.element.selectedIndex = 0;
	this.titleInput.element.value = '';
	this.notFoundInput.element.value = '';
	this.entityView.replace( new EntityView( true, false, false ) );
};

NewLatestPageItem.prototype.getItem = function () {
	return new LatestPageItem( this.entitySelect.selectedOption().value, this.entityView.inputClone(), this.lookupSelect.selectedOption().value, this.titleInput.element.value, this.notFoundInput.element.value );
};

ArdeClass.extend( NewLatestPageItem, LatestPageItem );

function DataWritersHolder() {
	
	this.ArdeComponent( 'div' );
	
	var p = ardeE( 'p' ).appendTo( ardeE( 'div' ).cls( 'group' ).appendTo( this ) );
	this.restoreButton = new ArdeRequestButton( 'Restore Deleted Defaults' );
	p.append( this.restoreButton );
	
	this.newDataWriter = new NewDataWriter( this ).appendTo( this );
	
	this.ArdeComponentList( new ArdeComponent( 'div' ).appendTo( this ) );
	
	var self = this;
	
	this.restoreButton.setStandardCallbacks( this, 'restore' );
}

DataWritersHolder.prototype.restoreClicked = function() {
	this.restoreButton.request( twatchFullUrl( 'rec/rec_data_writers.php' ), 'a=restore_deleted', DataWritersList );
};

DataWritersHolder.prototype.restoreConfirmed = function ( dataWritersList ) {
	for( var i in dataWritersList.a ) {
		this.insertItemBeforePosition( dataWritersList.a[i], dataWritersList.p[i] );
	}
};

ArdeClass.extend( DataWritersHolder, ArdeComponent );
ArdeClass.extend( DataWritersHolder, ArdeComponentList );

function DataWritersList( a, p ) {
	this.a = a;
	this.p = p;
}
DataWritersList.fromXml = function ( element ) {
	var a = []; 
	var p = [];
	var dataWriterEs = new ArdeXmlElemIter( element, 'data_writer' );
	while( dataWriterEs.current ) {
		a.push( DataWriter.fromXml( dataWriterEs.current ) );
		p.push( ArdeXml.intAttribute( dataWriterEs.current, 'pos' ) );
		dataWriterEs.next();
	}
	return new DataWritersList( a, p );
};

function DataWriter( id, entityId, when ) {
	this.id = id;
	this.entityId = entityId;
	this.when = when;
	
	this.ArdeComponent( 'div' );
	
	var d = ardeE( 'div' ).appendTo( this );
	
	if( this instanceof NewDataWriter ) {
		d.cls( 'special_block' );
		d.append( ardeE( 'h2' ).append( ardeT( 'New Data Writer' ) ) );
	} else {
		d.cls( 'block' );
	}
	
	var p = new ardeE( 'p' ).appendTo( d );
	
	this.entitySelect = new ArdeSelect();
	
	if( this instanceof NewDataWriter ) {
		this.entitySelect.addDummyOption( 'Select...' );
	}
	
	for( var i in entities ) {
		var option = ardeE( 'option' ).append( ardeT( entities[i] ) ).attr( 'value', i );
		this.entitySelect.append( option );
		if( i == this.entityId ) option.n.selected = true;
	}
	p.append( ardeT( 'entity: ' ) ).append( this.entitySelect );
	
	p = ardeE( 'p' ).cls( 'margin_half' ).appendTo( ardeE( 'div' ).cls( 'group pad_half' ).appendTo( d ) );
	this.whenInput = new TwatchExpressionInput( this.when );
	p.append( ardeT( ' when: ' ) ).append( this.whenInput );
	
	p = new ardeE( 'p' ).appendTo( d );

	if( this instanceof NewDataWriter ) {
		this.addButton = new ArdeRequestButton( 'Add Data Writer' );
		p.append( this.addButton );
		
		this.addButton.setStandardCallbacks( this, 'add' );
	} else {
		this.applyButton = new ArdeRequestButton( 'Apply Changes' );
		this.deleteButton = new ArdeRequestButton( 'Delete' );
		this.deleteButton.showOk = false;
		p.append( this.applyButton ).append( this.deleteButton );
		
		this.applyButton.setStandardCallbacks( this, 'apply' );
		this.deleteButton.setStandardCallbacks( this, 'delete' );

	}
}

DataWriter.prototype.getParams = function () {
	if( !( this instanceof NewDataWriter ) ) {
		id = 'i='+this.id+'&';
	} else {
		id = '';
	}
	return id+'ei='+this.entitySelect.selectedOption().value+'&w='+ardeEscape( this.whenInput.getParam() );
};

DataWriter.fromXml = function ( element ) {
	var id = ArdeXml.intAttribute( element, 'id' );
	var entityId = ArdeXml.intAttribute( element, 'entity_id' );
	var when = ArdeExpression.fromXml( ArdeXml.element( element, 'when' ) );
	return new DataWriter( id, entityId, when );
};

DataWriter.prototype.applyClicked = function () {
	this.applyButton.request( twatchFullUrl( 'rec/rec_data_writers.php' ), 'a=change&'+this.getParams() );
};

DataWriter.prototype.deleteClicked = function () {
	this.deleteButton.request( twatchFullUrl( 'rec/rec_data_writers.php' ), 'a=delete&i='+this.id );
};

DataWriter.prototype.deleteConfirmed = function ( result ) {
	var self = this;
	this.shrink( 500, function () { self.ardeList.removeItem( self ); } );
};

ArdeClass.extend( DataWriter, ArdeComponent );

function NewDataWriter( ardeList ) {
	this.ardeList = ardeList;
	this.DataWriter( null, 0, new ArdeExpression([]) );
}

NewDataWriter.prototype.check = function () {
	if( this.entitySelect.selectedOption() == null ) {
		alert( 'please select a data' );
		return false;
	}
	return true;
};

NewDataWriter.prototype.addClicked = function () {
	if( !this.check() ) return;
	this.addButton.request( twatchFullUrl( 'rec/rec_data_writers.php' ), 'a=add&'+this.getParams(), DataWriter );
};

NewDataWriter.prototype.clear = function () {
	this.entitySelect.element.selectedIndex = 0;
};

NewDataWriter.prototype.addConfirmed = function ( dataWriter ) {
	this.ardeList.insertFirstItem( dataWriter );
	this.clear();
};

ArdeClass.extend( NewDataWriter, DataWriter );



