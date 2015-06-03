
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
    
function EntitiesHolder() {
	this.ArdeComponent( 'div' );
	
	this.restoreButton = new ArdeRequestButton( 'Restore Deleted Defaults' );
	this.restoreButton.setStandardCallbacks( this, 'restore' );
	this.append( ardeE( 'div' ).cls( 'group' ).append( ardeE( 'p' ).append( this.restoreButton ) ) );
	
	this.diagButton = new ArdeRequestButton( 'Diagnostic Information' );
	this.diagButton.button.cls( 'passive' );
	
	this.closeButton = new ArdeButton( 'Close' ).cls( 'passive' );
	this.closeButton.setFloat( 'right' ).setDisplay( false );
	
	var div = ardeE( 'div' ).cls( 'group' ).append( ardeE( 'p' ).append( this.closeButton ).append( this.diagButton ) ).appendTo( this );
	this.extraPane = new ArdeComponent( 'div' ).appendTo( div );

	var self = this;
	
	this.diagButton.onclick = function () { self.diagClicked(); };
	this.diagButton.afterResultReceived = function ( result ) { self.diagReceived( result ); };
	
	this.closeButton.element.onclick = function() {
		self.extraPane.clear();
		self.closeButton.setDisplay( false );
	};
	this.newEntities = [];

	for( var i in EntitiesHolder.newEntityConstructors ) {
		var select = new ArdeComponent( 'select' );
		for( var j in EntitiesHolder.newEntityConstructors ) {
			var option = ardeE( 'option' ).append( ardeT( EntitiesHolder.newEntityConstructors[j].title ) );
			if( i == j ) option.n.selected = true;
			select.append( option );
		}
		select.element.onchange = function ( iArg, selectArg ) {
			return function () {
				if( selectArg.element.selectedIndex != iArg ) {
					for( var i in self.newEntities ) {
						
						self.newEntities[i].setDisplay( selectArg.element.selectedIndex == i );
					}
					selectArg.element.selectedIndex = iArg;
				}
			};
		} ( i, select );
		var newEntity = new EntitiesHolder.newEntityConstructors[i]( select );
		newEntity.setDisplay( i == 0 );
		newEntity.index = i;
		newEntity.ardeList = this;
		newEntity.appendTo( this );
		this.newEntities.push( newEntity );
	}
	this.ArdeComponentList( new ArdeComponent( 'div' ).appendTo( this ) );
}

EntitiesHolder.newEntityConstructors = [
	 NewStringEntity
	,NewBoolEntity
	,NewNullEntity
];

EntitiesHolder.prototype.diagClicked = function () {
	this.diagButton.request( twatchFullUrl( 'rec/rec_entities.php' ), 'a=get_diag', EntitiesDiagInfo );
};

EntitiesHolder.prototype.diagReceived = function ( diag ) {
	this.extraPane.clear();
	this.extraPane.append( diag );
	this.closeButton.setDisplay( true );
};

EntitiesHolder.prototype.restoreClicked = function() {
	this.restoreButton.request( twatchFullUrl( 'rec/rec_entities.php' ), 'a=restore_deleted', EntitiesList );
};

EntitiesHolder.prototype.restoreConfirmed = function ( entitiesList ) {
	for( var i in entitiesList.a ) {
		this.insertItemBeforePosition( entitiesList.a[i], entitiesList.p[i] );
	}
};

ArdeClass.extend( EntitiesHolder, ArdeComponent );
ArdeClass.extend( EntitiesHolder, ArdeComponentList );

function EntitiesList() {
	this.a = [];
	this.p = [];
}

EntitiesList.fromXml = function ( element ) {
	var entityEs = new ArdeXmlElemIter( element, 'entity' );
	var o = new EntitiesList();
	while( entityEs.current ) {
		o.a.push( Entity.fromXml( entityEs.current ) );
		o.p.push( ArdeXml.intAttribute( entityEs.current, 'pos' ) );
		entityEs.next();
	}
	return o;
};

function EntitiesDiagInfo( sizes, totalSize ) {
	this.ArdeComponent( 'div' );

	var totalRows = 0; 
	for( var i in sizes ) {
		totalRows += sizes[i];
	}
	
	var t = new ArdeTable().cls( 'std' ).appendTo( this );
	t.append( ardeE( 'thead' ).append( ardeE( 'tr' ).append( ardeE( 'td' ).append( ardeT( 'Entity Name' ) ) ).append( ardeE( 'td' ).append( ardeT( 'Rows' ) ) ).append( ardeE( 'td' ).append( ardeT( 'Size' ) ) ) ) );
	var tb = ardeE( 'tbody' ).appendTo( t );
	
	for( var name in sizes ) {
		var tr = ardeE( 'tr' ).appendTo( tb );
		if( totalRows == 0 ) var size = '0 B';
		else var size = ardeByteSize( Math.round( ( sizes[name] / totalRows ) * totalSize ) );
		tr.append( ardeE( 'td' ).append( ardeT( name ) ) );
		tr.append( ardeE( 'td' ).append( ardeT( sizes[ name ] ) ) );
		tr.append( ardeE( 'td' ).append( ardeT( size ) ) );
	}
	
	var tr = ardeE( 'tr' ).appendTo( tb ).cls( 'special' );
	tr.append( ardeE( 'td' ).append( ardeT( 'total' ) ) );
	tr.append( ardeE( 'td' ).append( ardeT( totalRows ) ) );
	tr.append( ardeE( 'td' ).append( ardeT( ardeByteSize( totalSize ) ) ) );
	
}

EntitiesDiagInfo.fromXml = function ( element ) {
	var totalSize = ArdeXml.intAttribute( element, 'total_size' );
	var sizes = {};
	var sizeEs = new ArdeXmlElemIter( ArdeXml.element( element, 'sizes' ), 'dict' );
	while( sizeEs.current ) {
		var name = ArdeXml.attribute( sizeEs.current, 'name' );
		var count = ArdeXml.intAttribute( sizeEs.current, 'rows' );
		sizes[ name ] = count;
		sizeEs.next();
	}
	
	return new EntitiesDiagInfo( sizes, totalSize );
};

ArdeClass.extend( EntitiesDiagInfo, ArdeComponent );

function Entity( id, name, visitorTitle, gene, state, newTypeSelect, visibility ) {
	this.id = id;
	this.name = name;
	this.visitorTitle = visitorTitle;
	this.gene = gene;
	this.state = state;
	this.visibility = visibility;
	
	this.ArdeComponent( 'div' );
	if( !ArdeClass.instanceOf( this, NewEntity ) ) {
		this.cls( 'block' );
	
		var float = new ArdeComponent( 'div' ).setFloat( 'right' ).appendTo( this );
		this.startButton = new ArdeRequestButton( 'Activate' ); 
		this.offP = new ArdeComponent( 'p' ).append( ardeE( 'span' ).cls( 'critical' ).append( ardeT( 'Inactive' ) ) ).append( ardeT( ' ' ) ).append( this.startButton );
		this.stopButton = new ArdeRequestButton( 'Deactivate' ); 
		this.onP = new ArdeComponent( 'p' ).append( ardeE( 'span' ).cls( 'good' ).append( ardeT( 'Active' ) ) ).append( ardeT( ' ' ) ).append( this.stopButton );
		
		this.startButton.setStandardCallbacks( this, 'start' );
		this.stopButton.setStandardCallbacks( this, 'stop' );
		
		this.offP.setDisplay( !this.state.on ).appendTo( float );
		this.onP.setDisplay( this.state.on ).appendTo( float );
		
		var d = ardeE( 'div' ).appendTo( this );
		this.titleText = ardeT( this.name ).appendTo( new ArdeComponent( 'h2' ).setDisplayMode( 'inline-block' ).appendTo( d ) );

		var type = null;
		if( this.gene.className == 'TwatchEntGeneBool' ) type =  'Boolean';
		else if( this.gene.className == 'TwatchEntGeneNull' ) type = 'Null';
		else if( this.gene.className == 'TwatchEntGeneGeneric' ) type = 'String';
		if( type !== null ) {
			d.append( ardeT( ardeNbsp(5)+'type: ' ) ).append( ardeE( 'span' ).cls( 'fixed' ).append( ardeT( type ) ) );
		}

	} else {
		this.cls( 'special_block' );
		var d = ardeE( 'div' ).appendTo( this );
		this.titleText = ardeT( 'New Entity' ).appendTo( new ArdeComponent( 'h2' ).setDisplayMode( 'inline-block' ).appendTo( d ) );
		d.append( ardeT( ardeNbsp(5)+'type: ' ) ).append( newTypeSelect );
	}
	
	var topTb = ardeE( 'tbody' ).appendTo( new ArdeTable().appendTo( this ) );
	
	var topTr = ardeE( 'tr' ).appendTo( topTb );

	var tb = ardeE( 'tbody' ).appendTo( new ArdeTable().cls( 'form' ).appendTo( ardeE( 'td' ).style( 'verticalAlign', 'top' ).appendTo( topTr ) ) );
	
	var tr = ardeE( 'tr' ).appendTo( tb ).append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'name:' ) ) );
	this.nameInput = new ArdeInput( this.name ).attr( 'size', '25' ).appendTo( ardeE( 'td' ).cls( 'tail' ).appendTo( tr ) );
	
	tr = ardeE( 'tr' ).appendTo( tb ).append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'visitor title:' ) ) );
	this.visitorTitleInput = new ArdeInput( this.visitorTitle ).attr( 'size', '30' ).appendTo( ardeE( 'td' ).cls( 'tail' ).appendTo( tr ) );
	
	var td = ardeE( 'td' ).style( 'verticalAlign', 'top' ).appendTo( topTr );
	for( var i in this.gene.dicts ) {
		td.append( this.gene.dicts[i] );
	}
	
	
	
	topTr = ardeE( 'tr' ).appendTo( topTb );
	
	
	topTr.append( ardeE( 'td' ).append( this.gene ) );
	
	if( !ArdeClass.instanceOf( this, NewEntity ) ) {

		this.visBox = new ArdeComponent( 'div' ).cls( 'group' ).appendTo( ardeE( 'td' ).appendTo( topTr ) );
		
		this.setVisibility( this.visibility );
	}
	if( !ArdeClass.instanceOf( this, NewEntity ) ) {
		this.applyButton = new ArdeRequestButton( 'Apply Changes' );
		this.restoreButton = new ArdeRequestButton( 'Restore Defaults' );
		this.deleteButton = new ArdeRequestButton( 'Delete', 'Are you sure this will delete anything related to this entity in database' ).setCritical( true );
		this.append( ardeE( 'p' ).append( this.applyButton ).append( this.restoreButton ).append( this.deleteButton ) );
		
		this.applyButton.setStandardCallbacks( this, 'apply' );
		this.restoreButton.setStandardCallbacks( this, 'restore' );
		this.deleteButton.setStandardCallbacks( this, 'delete' );
		
	} else {
		this.addButton = new ArdeRequestButton( 'Add Entity' );
		this.append( ardeE( 'p' ).append( this.addButton ) );
		
		this.addButton.setStandardCallbacks( this, 'add' );
	}
	
}

Entity.prototype.setVisibility = function ( visibility ) {
	this.visibility = visibility;
	this.visBox.clear();
	this.visSelect = new SelectWithDefault( [ Entity.VIS_VISIBLE, Entity.VIS_SHOW_AS_HIDDEN, Entity.VIS_HIDDEN ], [ 'Visible', 'Show as "hidden"', 'Hidden' ], this.visibility, 'Default' );
	
	this.visApplyButton = new ArdeRequestButton( 'Apply' );
	this.visApplyButton.setStandardCallbacks( this, 'visApply' );
	
	this.visBox.append( ardeE( 'p' ).append( this.visSelect ).append( ardeT( ' to ' ) ).append( selectedUser.getName() ).append( ardeT( ' ' ) ).append( this.visApplyButton ) ) ;
}

Entity.VIS_VISIBLE = 1;
Entity.VIS_SHOW_AS_HIDDEN = 2;
Entity.VIS_HIDDEN = 3;

Entity.fromXml = function ( element ) {
	var id = ArdeXml.intAttribute( element, 'id' );
	var name = ArdeXml.strElement( element, 'name' );
	var visitorTitle = ArdeXml.strElement( element, 'visitor_title' );
	var gene = EntityGene.fromXml( ArdeXml.element( element, 'gene' ) );
	var state = EntityState.fromXml( ArdeXml.element( element, 'state' ) );
	return new Entity( id, name, visitorTitle, gene, state, null, new ValueWithDefault( Entity.VIS_VISIBLE, true, Entity.VIS_VISIBLE ) );
};

Entity.prototype.visApplyClicked = function () {
	this.visApplyButton.request( twatchFullUrl( 'rec/rec_entities.php' ), 'a=set_vis&i='+this.id+'&'+this.visSelect.getParams( 'v_' ) );
}

Entity.prototype.startClicked = function () {
	this.startButton.request( twatchFullUrl( 'rec/rec_entities.php' ), 'a=start&i='+this.id );
};

Entity.prototype.startConfirmed = function ( result ) {
	this.setOn( true );
	this.stopButton.ok();
};

Entity.prototype.stopClicked = function () {
	this.stopButton.request( twatchFullUrl( 'rec/rec_entities.php' ), 'a=stop&i='+this.id );
};

Entity.prototype.stopConfirmed = function ( result ) {
	this.setOn( false );
	this.startButton.ok();
};

Entity.prototype.setOn = function( on ) {
	this.state.on = on;
	this.onP.setDisplay( on );
	this.offP.setDisplay( !on );
};

Entity.prototype.check = function () {
	if( this.nameInput.element.value == '' ) return ardeAlert( 'enter a name please' );
	if( !this.gene.check() ) return false;
	return true;
};

Entity.prototype.getParams = function () {
	if( this instanceof NewEntity ) {
		id = '';
	} else {
		id = 'i='+this.id+'&';
	}
	var d = '';
	var dp = '';
	for( var i in this.gene.dicts ) {
		d += (i==0?'':'+')+i;
		dp += '&'+this.gene.dicts[i].getParams( 'd'+i+'_' );
	}
	
	return id+'n='+ardeEscape( this.nameInput.element.value )+'&vt='+ardeEscape( this.visitorTitleInput.element.value )+'&'+this.gene.getParams( 'g_' )+'&d='+d+dp;;
};

Entity.prototype.applyClicked = function () {
	this.applyButton.request( twatchFullUrl( 'rec/rec_entities.php' ), 'a=change&'+this.getParams() );
};

Entity.prototype.applyConfirmed = function ( result ) {
	this.titleText.n.nodeValue = this.nameInput.element.value;
};

Entity.prototype.deleteClicked = function () {
	this.deleteButton.request( twatchFullUrl( 'rec/rec_entities.php' ), 'a=delete&i='+this.id );
};

Entity.prototype.deleteConfirmed = function ( result ) {
	this.ardeList.removeItem( this );
};

Entity.prototype.restoreClicked = function () {
	this.restoreButton.request( twatchFullUrl( 'rec/rec_entities.php' ), 'a=restore&i='+this.id, Entity );
};

Entity.prototype.restoreConfirmed = function ( entity ) {
	entity.setVisibility( this.visibility );
	this.ardeList.replaceItem( this, entity );
	entity.restoreButton.ok();
};

ArdeClass.extend( Entity, ArdeComponent );

function EntityState( on ) {
	this.on = on;
};

EntityState.fromXml = function ( element ) {
	return new EntityState( ArdeXml.boolAttribute( element, 'on' ) );
};

function NewEntity() {
	
}

NewEntity.prototype.clear = function () {
	this.nameInput.element.value = '';
	this.visitorTitleInput.element.value = '';
	this.gene.clear();
};

NewEntity.prototype.addClicked = function () {
	if( !this.check() ) return false;
	this.addButton.request( twatchFullUrl( 'rec/rec_entities.php' ), 'a=add&'+this.getParams(), Entity );
};

NewEntity.prototype.addConfirmed = function ( entity ) {
	this.ardeList.insertFirstItem( entity );
	this.clear();
};

ArdeClass.extend( NewEntity, Entity );

function NewStringEntity( typeSelect ) {
	this.Entity( null, '', '', new EntityGeneInput( 'TwatchEntGeneGeneric', [ new Dict( null, 'value', 'values', true, 7, false, true ) ], '' ), null, typeSelect );
}
NewStringEntity.title = 'String';
ArdeClass.extend( NewStringEntity, NewEntity );

function NewBoolEntity( typeSelect ) {
	this.Entity( null, '', '', new EntityGeneInput( 'TwatchEntGeneBool', [], '' ), null, typeSelect );
}
NewBoolEntity.title = 'Boolean';
ArdeClass.extend( NewBoolEntity, NewEntity );

function NewNullEntity( typeSelect ) {
	this.Entity( null, '', '', new EntityGeneNull( 'TwatchEntGeneNull', [], '', '' ), null, typeSelect );
}
NewNullEntity.title = 'Null';
ArdeClass.extend( NewNullEntity, NewEntity );

function EntityGene( className, dicts ) {
	this.className = className;
	this.dicts = dicts;
	this.ArdeComponent( 'div' );
}

EntityGene.fromXml = function ( element ) {
	var className = ArdeXml.attribute( element, 'class_name' );
	var jsClassName = ArdeXml.attribute( element, 'js_class_name' );
	var dictEs = new ArdeXmlElemIter( ArdeXml.element( element, 'dicts' ), 'dict' );
	var dicts = [];
	while( dictEs.current ) {
		dicts.push( Dict.fromXml( dictEs.current ) );
		dictEs.next();
	}
	if( jsClassName == 'EntityGene' ) return new EntityGene( className, dicts );
	else if( jsClassName == 'EntityGeneInput' ) return EntityGeneInput.fromXml( element, className, dicts );
	else if( jsClassName == 'EntityGeneNull' ) return EntityGeneNull.fromXml( element, className, dicts );
	else throw new ArdeException( 'EntityGene.fromXml', 'invalid gene className' );
};

EntityGene.prototype.check = function () { return true; };

EntityGene.prototype.getParams = function ( prefix ) {
	
	return prefix+'cn='+this.className;
};

ArdeClass.extend( EntityGene, ArdeComponent );

function EntityGeneInput( className, dicts, inputKey ) {
	this.inputKey = inputKey;

	this.EntityGene( className, dicts );
	
	this.inputTb = ardeE( 'tbody' ).appendTo( new ArdeTable().cls( 'form' ).appendTo( this ) );
	var tr = ardeE( 'tr' ).appendTo( this.inputTb ).append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'data key:' ) ) );
	this.inputKeyInput = new ArdeInput( this.inputKey ).attr( 'size', '10' );
	tr.append( ardeE( 'td' ).cls( 'tail' ).append( this.inputKeyInput ) );
}



EntityGeneInput.prototype.clear = function () {
	this.inputKeyInput.element.value = '';
};

EntityGeneInput.prototype.check = function () {
	if( this.inputKeyInput.element.value == '' ) return ardeAlert( 'enter the data key please' );
	return true;
};

EntityGeneInput.fromXml = function ( element, className, dicts ) {
	var inputKey = ArdeXml.attribute( element, 'input_key' );
	return new EntityGeneInput( className, dicts, inputKey );
};

EntityGeneInput.prototype.getParams = function ( prefix ) {
	return this.EntityGene_getParams( prefix )+'&'+prefix+'ik='+ardeEscape( this.inputKeyInput.element.value );
};

ArdeClass.extend( EntityGeneInput, EntityGene );

function EntityGeneNull( className, dicts, inputKey, value ) {
	this.EntityGeneInput( className, dicts, inputKey );

	this.value = value;
	var tr = ardeE( 'tr' ).appendTo( this.inputTb ).append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'value:' ) ) );
	this.valueInput = new ArdeInput( this.value ).attr( 'size', '30' );
	tr.append( ardeE( 'td' ).cls( 'tail' ).append( this.valueInput ) );
}
EntityGeneNull.prototype.clear = function () {
	this.EntityGeneInput_clear();
	this.valueInput.element.value = '';
};

EntityGeneNull.prototype.check = function () {
	if( !this.EntityGeneInput_check() ) return false;
	if( this.valueInput.element.value == '' ) return ardeAlert( 'enter a value please' );
	return true;
};

EntityGeneNull.fromXml = function ( element, className, dicts ) {
	var inputKey = ArdeXml.attribute( element, 'input_key' );
	var value = ArdeXml.attribute( element, 'value' );
	return new EntityGeneNull( className, dicts, inputKey, value );
};

EntityGeneNull.prototype.getParams = function ( prefix ) {
	return this.EntityGeneInput_getParams( prefix )+'&'+prefix+'v='+ardeEscape( this.valueInput.element.value );
};
ArdeClass.extend( EntityGeneNull, EntityGeneInput );

function Dict( id, singleName, pluralName, allowCleanup, cleanupDays, allowExplicitAdd, isNew ) {
	this.id = id;
	this.singleName = singleName;
	this.pluralName = pluralName;
	this.allowCleanup = allowCleanup;
	this.cleanupDays = cleanupDays;
	this.allowExplicitAdd = allowExplicitAdd;
	
	if( typeof isNew == 'undefined' ) isNew = false;
	
	this.ArdeComponent( 'div' );
	this.cls( 'group' );
	
	if( this.allowCleanup ) {
		this.cleanupDaysInput = new ArdeInput( this.cleanupDays ).attr( 'size', '2' );
		this.append( ardeE( 'p' ).append( ardeT( 'Complete '+this.pluralName+' cleanup cycle: ' ) ).append( this.cleanupDaysInput ).append( ardeT( ' days' ) ) );
		if( !isNew ) {
			this.cleanupButton = new ArdeRequestButton( 'Cleanup Now' );
			this.cleanupButton.setStandardCallbacks( this, 'cleanup' );
			this.resetCleanupButton = new ArdeRequestButton( 'Reset Cleanup' );
			this.resetCleanupButton.setStandardCallbacks( this, 'resetCleanup' );
			this.append( ardeE( 'p' ).append( this.cleanupButton ).append( ardeT( ' ' ) ).append( this.resetCleanupButton ) );
		}
	}
	if( this.allowExplicitAdd ) {
		this.strInput = new ArdeInput( '' );
		this.addButton = new ArdeRequestButton( 'Add '+this.singleName );
		this.append( ardeE( 'p' ).append( this.strInput ).append( this.addButton ) );
		this.entrySelect = new ArdeActiveSelect( null, 10 );
		this.deleteButton = new ArdeRequestButton( 'Delete '+this.singleName );
		this.append( ardeE( 'p' ).append( this.entrySelect ).append( this.deleteButton ) );
		var self = this;
		self.entrySelect.request = function ( offset, count ) { self.requestEntries( offset, count ); };
		self.entrySelect.processResult = function ( result ) { self.processEntries( result ); };
	}	
}

Dict.prototype.cleanupClicked = function () {
	this.cleanupButton.request( twatchFullUrl( 'rec/rec_dicts.php' ), 'a=cleanup&i='+this.id );
}

Dict.prototype.resetCleanupClicked = function () {
	this.resetCleanupButton.request( twatchFullUrl( 'rec/rec_dicts.php' ), 'a=reset_cleanup&i='+this.id );
}

Dict.prototype.getParams = function ( prefix ) {
	if( this.isNew ) {
		var id = '';
	} else {
		var id = prefix+'i='+this.id+'&';
	}
	return id+prefix+'cd='+ardeEscape( this.cleanupDaysInput.element.value );
}

Dict.prototype.requestEntries = function ( offset, count ) {
	this.entrySelect.requester.request( twatchFullUrl( 'rec/rec_dicts.php' ), 'a=get_entries&i='+this.id+'&c='+count+'&o='+offset, ardeXmlObjectListClass( DictEntry, 'entry', true ) );
};

Dict.prototype.processEntries = function( entriesList ) {
	
	this.entrySelect.resultsReceived( entriesList.a, entriesList.more );
};



Dict.fromXml = function ( element ) {
	var id = ArdeXml.intAttribute( element, 'id' );
	var singleName = ArdeXml.strElement( element, 'single_name' );
	var pluralName = ArdeXml.strElement( element, 'plural_name' );
	var allowCleanup = ArdeXml.boolAttribute( element, 'allow_cleanup' );
	var cleanupDays = ArdeXml.intAttribute( element, 'cleanup_days' );
	var allowExplicitAdd = ArdeXml.boolAttribute( element, 'explicit_add' );
	
	return new Dict( id, singleName, pluralName, allowCleanup, cleanupDays, allowExplicitAdd );
};

ArdeClass.extend( Dict, ArdeComponent );

function DictEntry( id, str ) {
	this.id = id;
	this.str = str;
	this.ArdeComponent( 'div' );
	this.append( ardeT( this.str ) );
}

DictEntry.fromXml = function ( element ) {
	var id = ArdeXml.intAttribute( element, 'id' );
	var str = ArdeXml.strContent( element );
	return new DictEntry( id, str );
};

DictEntry.prototype.setMouseOverHighlight = function( mouseOverHighlight ) {
	if( mouseOverHighlight ) {
		this.element.style.background = '#f00';
	} else {
		this.element.style.background = '#fff';
	}
};

ArdeClass.extend( DictEntry, ArdeComponent );



