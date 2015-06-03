
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
    
function VisitorTypesHolder() {
	
	this.ArdeComponent( 'div' );
	
	this.resetButton = new ArdeRequestButton( 'Reset All Identifiers', 'are you sure? this will remove any custom identifier you added to any visitor type' ).setCritical( true );
	this.resetButton.setStandardCallbacks( this, 'reset' );
	this.append( ardeE( 'div' ).cls( 'group' ).append( ardeE( 'p' ).append( this.resetButton ) ) );
	
	this.ArdeComponentList( new ArdeComponent( 'div' ).appendTo( this ) );
	
}

VisitorTypesHolder.prototype.resetClicked = function () {
	this.resetButton.request( twatchFullUrl( 'rec/rec_visitor_types.php' ), 'a=reset', ardeXmlObjectListClass( VisitorType, 'visitor_type' ) );
}

VisitorTypesHolder.prototype.resetConfirmed = function ( visitorTypes ) {
	var va = {};
	for( var i in visitorTypes.a ) {
		va[ visitorTypes.a[i].id ] = visitorTypes.a[i];
	}
	for( var i in this.items ) {
		this.replaceItem( this.items[i], va[ this.items[i].id ] );
	}
}

ArdeClass.extend( VisitorTypesHolder, ArdeComponent );
ArdeClass.extend( VisitorTypesHolder, ArdeComponentList );

function VisitorType( id, name, when, identifiers ) {
	this.id = id;
	this.name = name;
	this.when = when;
	this.identifiers = identifiers;
	
	this.ArdeComponent( 'div' );
	
	if( this instanceof NewVisitorType ) {
		this.cls( 'special_block' );
		this.titleText = ardeT( 'New Visitor' ).appendTo( ardeE( 'h2' ).appendTo( this ) );
	} else {
		this.cls( 'block' );
		this.titleText = ardeT( this.name ).appendTo( ardeE( 'h2' ).appendTo( this ) );
	}
	
	var tb = ardeE( 'tbody' ).appendTo( new ArdeTable().cls( 'form' ).appendTo( this ) );
	
	var tr = ardeE( 'tr' ).appendTo( tb ).append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'name:' ) ) );
	this.nameInput = new ArdeInput( this.name );
	tr.append( ardeE( 'td' ).cls( 'tail' ).append( this.nameInput ) );

	if( this.id != VisitorType.NORMAL ) {
		var div = ardeE( 'div' ).cls( 'group' ).appendTo( this );
		var p = ardeE( 'p' ).append( ardeE( 'b' ).append( ardeT( 'when: ' ) ) ).appendTo( div );
		this.whenInput = new TwatchExpressionInput( this.when, 'Identifier match only' ).appendTo( p );
	}

	var p = ardeE( 'p' ).appendTo( this );
	this.applyButton = new ArdeRequestButton( 'Apply Change' );
	this.restoreButton = new ArdeRequestButton( 'Restore Default' );
	this.applyButton.setStandardCallbacks( this, 'apply' );
	this.restoreButton.setStandardCallbacks( this, 'restore' );
	p.append( this.applyButton ).append( this.restoreButton );

	if( this.id != VisitorType.NORMAL ) {
		this.idsList = new IdentifierList( this, this.identifiers ).appendTo( this );
	}
	
}

VisitorType.NORMAL = 1;

VisitorType.fromXml = function ( element ) {
	var id = ArdeXml.intAttribute( element, 'id' );
	var name = ArdeXml.attribute( element, 'name' );
	var when = ArdeExpression.fromXml( ArdeXml.element( element, 'when' ) );
	var identifiers = [];
	var idEs = new ArdeXmlElemIter( ArdeXml.element( element, 'identifiers' ), 'id' );
	while( idEs.current ) {
		identifiers.push( Identifier.fromXml( idEs.current ) );
		idEs.next();
	}
	return new VisitorType( id, name, when, identifiers );
};

VisitorType.prototype.applyClicked = function () {
	this.applyButton.request( twatchFullUrl( 'rec/rec_visitor_types.php' ), 'a=change&'+this.getParams() );
};

VisitorType.prototype.applyConfirmed = function ( result ) {
	this.titleText.n.nodeValue = this.nameInput.element.value;
};

VisitorType.prototype.restoreClicked = function () {
	this.restoreButton.request( twatchFullUrl( 'rec/rec_visitor_types.php' ), 'a=restore&i='+this.id, VisitorType );
};

VisitorType.prototype.restoreConfirmed = function ( visitorType ) {
	this.ardeList.replaceItem( this, visitorType );
	visitorType.restoreButton.ok();
};

VisitorType.prototype.getParams = function() {
	if( this instanceof NewVisitorType ) {
		id = '';
	} else {
		id = 'i='+this.id+'&';
	}
	
	if( this.id != VisitorType.NORMAL ) {
		w = '&w='+ardeEscape( this.whenInput.getParam() );
	} else {
		w = '';
	}
	return id+'n='+ardeEscape( this.nameInput.element.value )+w;
};

VisitorType.prototype.getId = function( entityId, entityVId ) {
	if( this.id == 1 ) return null;
	for( var i in this.idsList.items ) {
		if( this.idsList.items[i].entityV.entityId == entityId && this.idsList.items[i].entityV.id == entityVId ) {
			return this.idsList.items[i];
		}
	}
	return null;
};

ArdeClass.extend( VisitorType, ArdeComponent );

function NewVisitorType() {
	this.VisitorType( null, '' );
}
ArdeClass.extend( NewVisitorType, VisitorType );

function IdentifierList ( visitorType, identifiers ) {
	this.visitorType = visitorType;
	
	this.ArdeComponent( 'div' );
	this.cls( 'group' );
	this.append( ardeE( 'p' ).append( ardeE( 'b' ).append( ardeT( 'Identifiers:' ) ) ) );
	
	this.ArdeComponentList( new ArdeComponent( 'span' ).appendTo( this ), ArdeComponentList.SELECT_SINGLE );
	
	
	for( var i in identifiers ) {
		this.addItem( identifiers[i] );
	}

	this.addSpan = new ArdeComponent( 'span' ).setDisplayMode( 'inline-block' ).appendTo( this );
	this.addSpan.style( 'marginLeft', '20px' ).style( 'marginTop', '10px' );
	
	this.entitySelect = new ArdeSelect();
	for( var i in entities ) {
		ardeE( 'option' ).append( ardeT( entities[i].name ) ).attr( 'value', i ).appendTo( this.entitySelect );
	}
	this.entityValueSelect = new ArdeActiveSelect( null, 10 );
	this.addButton = new ArdeRequestButton( 'Add' );
	this.removeButton = new ArdeRequestButton( 'Remove Selected' ).setDisplay( false );
	this.addButton.setStandardCallbacks( this, 'add' );
	this.removeButton.setStandardCallbacks( this, 'remove' );
	this.addSpan.append( this.entitySelect ).append( this.entityValueSelect ).append( this.addButton ).append( this.removeButton );
	
	this.entityValueSelect.setStandardCallbacks( this, 'entityValues' );
	

	var self = this;
	this.entitySelect.element.onchange = function () {
		self.entityValueSelect.retract();
		self.entityValueSelect.setValue( null );
		self.entityValueSelect.showAddButton( entities[ self.entitySelect.selectedOption().value ].allowExplicitAdd );
	};
	
	this.entitySelect.element.onchange();
}

IdentifierList.prototype.entityValuesAddClicked = function( str ) {
	this.entityValueSelect.addButton.request( twatchFullUrl( twatchUrl+'rec/rec_entity_values.php' ), 'a=add&ei='+this.entitySelect.selectedOption().value+'&s='+ardeEscape( str ), EntityV );
}

IdentifierList.prototype.entityValuesRequested = function( offset, count, beginWith ) {
	if( beginWith == '' ) b = '';
	else b = '&b='+ardeEscape( beginWith );
	this.entityValueSelect.requester.request( twatchFullUrl( twatchUrl+'rec/rec_entity_values.php' ),
		'a=get_values&i='+this.entitySelect.selectedOption().value+'&o='+offset+'&c='+count+b+'&w='+websiteId,
		ardeXmlObjectListClass( EntityV, 'entity_v', true, false )
	);
};

IdentifierList.prototype.entityValuesReceived = function( result ) {
	this.entityValueSelect.resultsReceived( result.a, result.more );
};

IdentifierList.prototype.addClicked = function() {
	if( this.entityValueSelect.getSelected() == null ) return alert( 'select a value please' );
	var entityId = this.entitySelect.selectedOption().value;
	var entityVId = this.entityValueSelect.getSelected().id;
	sameId = this.visitorType.getId( entityId, entityVId );
	if( sameId != null ) return alert( 'already exists' );
	this.addButton.request( twatchFullUrl( 'rec/rec_visitor_types.php' ), 'a=add_id&i='+this.visitorType.id+'&ei='+entityId+'&evi='+entityVId, Identifier );
};

IdentifierList.prototype.addConfirmed = function ( identifier ) {
	this.addItem( identifier );
	for( var i in this.visitorType.ardeList.items ) {
		if( this.visitorType.ardeList.items[i] != this.visitorType ) {
			sameId = this.visitorType.ardeList.items[i].getId( identifier.entityV.entityId, identifier.entityV.id );
			if( sameId != null ) {
				this.entityValueSelect.setSelected( null );
				return this.visitorType.ardeList.items[i].idsList.removeItem( sameId );
			}
		}
	}
	this.entityValueSelect.setSelected( null );
};

IdentifierList.prototype.removeClicked = function () {
	selected = this.getSelected();
	this.removeButton.request( twatchFullUrl( 'rec/rec_visitor_types.php' ), 'a=remove_id&ei='+selected.entityV.entityId+'&evi='+selected.entityV.id );
};

IdentifierList.prototype.removeConfirmed = function ( result ) {
	this.removeItem( this.getSelected() );
	this.removeButton.setDisplay( false );
};

ArdeClass.extend( IdentifierList, ArdeComponent );
ArdeClass.extend( IdentifierList, ArdeComponentList );

function Identifier( entityV ) {
	this.ArdeItem( 'span' );
	this.entityV = entityV;
	this.setDisplayMode( 'inline-block' );
	this.append( ardeT( entities[ this.entityV.entityId ].name+': ' ) );
	this.append( this.entityV );
	this.setClickable( true );
	this.highlightOnMouseOver( true );
	var self = this;
	this.element.onclick = function () {
		self.ardeList.removeButton.setDisplay( !self.ardeSelected );
		self.setSelected( !self.ardeSelected );
	}
	
	this.style( 'background', '#fff' );
	this.style( 'marginLeft', '10px' );
	this.style( 'marginTop', '10px' );
	this.style( 'padding', '2px 10px' );
	
	
}

Identifier.fromXml = function( element ) {
	return new Identifier( InlineEntityV.fromXml( element ) );
}

Identifier.prototype.setMouseOverHighlight = function( mouseOverHighlight ) {
	if( mouseOverHighlight ) {
		this.element.style.background = '#f88';
		this.element.style.color = '#000';
	} else {
		this.element.style.background = '#fff';
		this.element.style.color = '#000';
	}
	return this;
};

Identifier.prototype.setSelectedHighlight = function( selectedHighlight ) {
	if( selectedHighlight ) {
		this.element.style.background = '#a00';
		this.element.style.color = '#fff';
	} else {
		this.element.style.background = '#fff';
		this.element.style.color = '#000';
	}
	return this;
};

Identifier.prototype._setSelected = function ( selected ) {
	this.setSelectedHighlight( selected );
	this.highlightOnMouseOver( !selected );
	this.ArdeItem__setSelected( selected );
}
ArdeClass.extend( Identifier, ArdeItem );





