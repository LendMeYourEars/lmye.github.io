
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
    
function CountersHolder( when ) {

	this.ArdeComponent( 'div' );
	
	this.startButton = new ArdeRequestButton( 'Start All' );
	this.stopButton = new ArdeRequestButton( 'Stop All' );
	this.resetButton = new ArdeRequestButton( 'Reset All', 'this will delete any counter data currently in database' ).setCritical( true );
	this.restoreButton = new ArdeRequestButton( 'Restore Deleted Defaults' );
	
	this.append( ardeE( 'div' ).cls( 'group' ).append( ardeE( 'p' ).append( this.startButton ).append( this.stopButton ).append( this.restoreButton ).append( this.resetButton ) ) );
	
	this.whenInput = new ArdeExpressionInput( when );
	var div = ardeE( 'div' ).cls( 'block' ).appendTo( this );
	div.append( ardeE( 'p' ).append( ardeE( 'b' ).append( ardeT( 'All Counters Count When: ' ) ) ).append( this.whenInput ) );
	this.applyButton = new ArdeRequestButton( 'Apply Change' );
	this.applyButton.setStandardCallbacks( this, 'apply' );
	div.append( ardeE( 'p' ).append( this.applyButton ) );
	
	this.diagButton = new ArdeRequestButton( 'Diagnostic Information' );
	this.diagButton.button.cls( 'passive' );
	
	this.closeButton = new ArdeButton( 'Close' ).cls( 'passive' );
	this.closeButton.setFloat( 'right' ).setDisplay( false );

	
	div = ardeE( 'div' ).cls( 'group' ).append( ardeE( 'p' ).append( this.closeButton ).append( this.diagButton ) ).appendTo( this );
	this.extraPane = new ArdeComponent( 'div' ).appendTo( div );
	
	this.newCounters = {};
	
	this.newCounters[ SingleCounter.typeName ] = new NewSingleCounter();
	this.newCounters[ ListCounter.typeName ] = new NewListCounter().setDisplay( false );
	this.newCounters[ GroupedCounter.typeName ] = new NewGroupedCounter().setDisplay( false );
	
	for( var i in this.newCounters ) {
		this.newCounters[i].holder = this;
		this.append( this.newCounters[i] );
	}
	
	this.ArdeComponentList( new ArdeComponent( 'div' ).appendTo( this ) );
	
	var self = this;
	this.restoreButton.onclick = function () { self.restoreClicked(); };
	this.restoreButton.afterResultReceived = function ( result ) { self.restoreConfirmed( result ); };
	
	this.startButton.onclick = function () { self.startClicked(); };
	this.startButton.afterResultReceived = function ( result ) { self.startConfirmed( result ); };
	
	this.stopButton.onclick = function () { self.stopClicked(); };
	this.stopButton.afterResultReceived = function ( result ) { self.stopConfirmed( result ); };
	
	this.resetButton.onclick = function () { self.resetClicked(); };
	
	this.diagButton.onclick = function () { self.diagClicked(); };
	this.diagButton.afterResultReceived = function ( result ) { self.diagReceived( result ); };
	
	this.closeButton.element.onclick = function() {
		self.extraPane.clear();
		self.closeButton.setDisplay( false );
	};
}


CountersHolder.prototype.applyClicked = function () {
	this.applyButton.request( twatchFullUrl( 'rec/rec_counters.php' ), 'a=change_when&w='+ardeEscape( this.whenInput.getParam() ) );
}

CountersHolder.prototype.diagClicked = function () {
	this.diagButton.request( twatchFullUrl('rec/rec_counters.php' ), 'a=get_diag', CountersDiagInfo );
};

CountersHolder.prototype.diagReceived = function ( diag ) {
	this.extraPane.clear();
	this.extraPane.append( diag );
	this.closeButton.setDisplay( true );
};



CountersHolder.prototype.resetClicked = function () {
	this.resetButton.request( twatchFullUrl( 'rec/rec_counters.php' ), 'a=reset_all' );
};

CountersHolder.prototype.restoreClicked = function () {
	this.restoreButton.request( twatchFullUrl( 'rec/rec_counters.php' ), 'a=restore_deleted', CountersList );
};

CountersHolder.prototype.restoreConfirmed = function ( countersList ) {
	for( var i in countersList.a ) {
		this.insertItemBeforePosition( countersList.a[i], countersList.p[i] );
	}
};

CountersHolder.prototype.startClicked = function () {
	this.startButton.request( twatchFullUrl( 'rec/rec_counters.php' ), 'a=start_all' );
};

CountersHolder.prototype.startConfirmed = function ( result ) {
	for( var i in this.items ) {
		this.items[i].setOn( true );
	}
};

CountersHolder.prototype.stopClicked = function () {
	this.stopButton.request( twatchFullUrl( 'rec/rec_counters.php' ), 'a=stop_all' );
};

CountersHolder.prototype.stopConfirmed = function ( result ) {
	for( var i in this.items ) {
		this.items[i].setOn( false );
	}
};


function CountersDiagInfo( sizes, totalSize ) {
	this.ArdeComponent( 'div' );

	var totalRows = 0; 
	for( var i in sizes ) {
		totalRows += sizes[i];
	}
	
	var t = new ArdeTable().cls( 'std' ).appendTo( this );
	t.append( ardeE( 'thead' ).append( ardeE( 'tr' ).append( ardeE( 'td' ).append( ardeT( 'Counter Name' ) ) ).append( ardeE( 'td' ).append( ardeT( 'Rows' ) ) ).append( ardeE( 'td' ).append( ardeT( 'Size' ) ) ) ) );
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

CountersDiagInfo.fromXml = function ( element ) {
	var totalSize = ArdeXml.intAttribute( element, 'total_size' );
	var sizes = {};
	var sizeEs = new ArdeXmlElemIter( ArdeXml.element( element, 'sizes' ), 'counter' );
	while( sizeEs.current ) {
		var name = ArdeXml.attribute( sizeEs.current, 'name' );
		var count = ArdeXml.intAttribute( sizeEs.current, 'rows' );
		sizes[ name ] = count;
		sizeEs.next();
	}
	
	return new CountersDiagInfo( sizes, totalSize );
};

ArdeClass.extend( CountersDiagInfo, ArdeComponent );

function CountersList() {
	this.a = [];
	this.p = [];
}

CountersList.fromXml = function ( element ) {
	var counterEs = new ArdeXmlElemIter( element, 'counter' );
	var o = new CountersList();
	while( counterEs.current ) {
		o.a.push( Counter.fromXml( counterEs.current ) );
		o.p.push( ArdeXml.intAttribute( counterEs.current, 'pos' ) );
		counterEs.next();
	}
	return o;
};

CountersHolder.prototype.addCounter = function ( counter ) {
	this.addItem( counter );
};

CountersHolder.prototype.removeCounter = function ( counter ) {
	this.removeItem( counter );
};

CountersHolder.prototype.replaceCounter = function( counter, replacement ) {
	replacement.holder = this;
	counter.holder = null;
	this.replaceItem( counter, replacement );
};

CountersHolder.prototype.showNew = function ( typeName ) {
	for( var i in this.newCounters ) {
		this.newCounters[i].setDisplay( i == typeName );
		if( i == typeName ) {
			this.newCounters[i].resetTypeSelect();
		}
	}
};

ArdeClass.extend( CountersHolder, ArdeComponent );
ArdeClass.extend( CountersHolder, ArdeComponentList );

function Counter( id, name, periodTypes, when, del, on, perm ) {
	this.id = id;
	this.name = name;
	this.periodTypes = periodTypes;
	this.on = on;
	this.perm = perm;
	this.when = when;
	this.del = del;
	
	this.ArdeComponent( 'div' );
	this.cls( 'block' );
	if( !ArdeClass.instanceOf( this, NewCounter ) ) {
		var float = new ArdeComponent( 'div' ).setFloat( 'right' ).appendTo( this );
		this.startButton = new ArdeRequestButton( 'Start' ); 
		this.offP = new ArdeComponent( 'p' ).append( ardeE( 'span' ).cls( 'critical' ).append( ardeT( 'Stopped' ) ) ).append( ardeT( ' ' ) ).append( this.startButton );
		this.stopButton = new ArdeRequestButton( 'Stop' ); 
		this.onP = new ArdeComponent( 'p' ).append( ardeE( 'span' ).cls( 'good' ).append( ardeT( 'Running' ) ) ).append( ardeT( ' ' ) ).append( this.stopButton );
		
		this.offP.setDisplay( !this.on ).appendTo( float );
		this.onP.setDisplay( this.on ).appendTo( float );
		
		var self = this;
		this.startButton.onclick = function () { self.startClicked(); };
		this.startButton.afterResultReceived = function ( result ) { self.startConfirmed( result ); };
		
		this.stopButton.onclick = function () { self.stopClicked(); };
		this.stopButton.afterResultReceived = function ( result ) { self.stopConfirmed( result ); };
	}
	
	this.titleText = ardeT( ArdeClass.instanceOf( this, NewCounter )?'New Counter':this.name );
	this.append( ardeE( 'h2' ).append( this.titleText ) );
	
	var topTr = ardeE( 'tr' ).appendTo( ardeE( 'tbody').appendTo( new ArdeTable().appendTo( this ) ) );
	
	var tb = ardeE( 'tbody' ).appendTo( new ArdeTable().cls( 'form' ).appendTo( ardeE( 'td' ).style( 'verticalAlign', 'top' ).appendTo( topTr ) ) );
	var tr = ardeE( 'tr' ).append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'type:' ) ) ).appendTo( tb );
	var td = ardeE( 'td' ).cls( 'tail' ).appendTo( tr );
	if( ArdeClass.instanceOf( this, NewCounter ) ) {
		this.typeSelect = new ArdeSelect();
		this.typeSelect.append( ardeE( 'option' ).attr( 'value', SingleCounter.typeName ).append( ardeT( SingleCounter.typeName ) ) );
		this.typeSelect.append( ardeE( 'option' ).attr( 'value', ListCounter.typeName ).append( ardeT( ListCounter.typeName ) ) );
		this.typeSelect.append( ardeE( 'option' ).attr( 'value', GroupedCounter.typeName ).append( ardeT( GroupedCounter.typeName ) ) );
		td.append( this.typeSelect );
	} else {
		td.append( ardeE( 'span' ).cls( 'fixed' ).append( ardeT( this.constructor.typeName ) ) );
	}
	
	tr = ardeE( 'tr' ).append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'name:' ) ) ).appendTo( tb );
	this.nameInput = new ArdeInput( this.name );
	tr.append( ardeE( 'td' ).cls( 'tail' ).append( this.nameInput ) );
	
	if( ArdeClass.instanceOf( this, ListCounter ) ) {
		tr = ardeE( 'tr' ).append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'entity:' ) ) ).appendTo( tb );
		if( ArdeClass.instanceOf( this, NewCounter ) ) {
			this.entitySelect = new ArdeSelect();
			if( ArdeClass.instanceOf( this, NewCounter ) ) this.entitySelect.addDummyOption( 'Select...' );
			
			for( var i in entities ) {
				var option = ardeE( 'option' ).append( ardeT( entities[i] ) ).attr( 'value', i );
				this.entitySelect.append( option );
				
				if( this.entityId == i ) {
					option.n.selected = true;
				}
			}
			tr.append( ardeE( 'td' ).cls( 'tail' ).append( this.entitySelect ) );
		} else {
			tr.append( ardeE( 'td' ).cls( 'tail' ).append( ardeE( 'span' ).cls( 'fixed' ).append( ardeT( entities[ this.entityId ] ) ) ) );
		}
	}
	if( ArdeClass.instanceOf( this, GroupedCounter ) ) {
		tr = ardeE( 'tr' ).append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'group:' ) ) ).appendTo( tb );
		
		if( ArdeClass.instanceOf( this, NewCounter ) ) {
			this.groupSelect = new ArdeSelect();
	
			if( ArdeClass.instanceOf( this, NewCounter ) ) this.groupSelect.addDummyOption( 'Select...' );
			for( var i in entities ) {
				
				var option = ardeE( 'option' ).append( ardeT( entities[i] ) ).attr( 'value', i );
				this.groupSelect.append( option );
				
				if( this.groupId == i ) {
					option.n.selected = true;
				}
			}
			tr.append( ardeE( 'td' ).cls( 'tail' ).append( this.groupSelect ) );
		} else {
			tr.append( ardeE( 'td' ).cls( 'tail' ).append( ardeE( 'span' ).cls( 'fixed' ).append( ardeT( entities[ this.groupId ] ) ) ) );
		}
	}
	
	tb = ardeE( 'tbody' ).appendTo( new ArdeTable().cls( 'form' ).appendTo( ardeE( 'td' ).style( 'verticalAlign', 'top' ).appendTo( topTr ) ) );
	tr = ardeE( 'tr' ).append( ardeE( 'td' ).cls( 'head' ).style( 'verticalAlign', 'top' ).append( ardeT( 'Period Types:' ) ) ).appendTo( tb );
	td = ardeE( 'td' ).cls( 'tail' ).appendTo( tr );
	this.periodTypesList = new PeriodTypesList();

	for( var i in Period.typeStrings ) {
		if( ardeArrayContains( this.periodTypes, i ) ) var del = this.del;
		else var del = defaultDel;
		if( ArdeClass.instanceOf( this, ListCounter ) ) {
			if( ardeArrayContains( this.periodTypes, i ) ) {
				var trim = this.trim;
				var activeTrim = this.activeTrim;
			} else {
				var trim = defaultTrim;
				var activeTrim = defaultActiveTrim;
			}
			this.periodTypesList.listElements[i] = new ListPeriodTypeElement( i, this.periodTypesList, del, trim, activeTrim );
		} else	{
			this.periodTypesList.listElements[i] = new PeriodTypeElement( i, this.periodTypesList, del );
		}
	}
	for( var i in Period.typeStrings ) {
		if( ardeArrayContains( this.periodTypes, i ) ) {
			this.periodTypesList.list.addItem( this.periodTypesList.listElements[ i ] );
		} else {
			this.periodTypesList.select.append( ardeE( 'option' ).append( ardeT( Period.typeStrings[i] ) ).attr( 'value', i ) );
		}
	}
	td.append( this.periodTypesList );
	
	this.whenInput = new TwatchExpressionInput( this.when );
	this.append( ardeE( 'div' ).cls( 'group pad_half' ).append( ardeE( 'p' ).cls( 'margin_half' ).append( ardeE( 'span' ).style( 'fontWeight', 'bold' ).append( ardeT( 'when: ' ) ) ).append( this.whenInput ) ) );
	
	
	
	
	var p = ardeE( 'p' ).appendTo( this );
	if( ArdeClass.instanceOf( this, NewCounter ) ) {
		this.addButton = new ArdeRequestButton( 'Add Counter' );
		p.append( this.addButton );
	} else {
		this.applyButton = new ArdeRequestButton( 'Apply Changes' );
		this.applyButton.confirmationText = 'any data related to removed period type will be deleted, are you sure?';
		this.cleanupButton = new ArdeRequestButton( 'Cleanup Now' );
		this.restoreButton = new ArdeRequestButton( 'Restore Defaults' );
		this.resetButton = new ArdeRequestButton( 'Reset', 'Are you sure this will remove all data related to this counter in database' ).setCritical( true );
		this.deleteButton = new ArdeRequestButton( 'Delete', 'Are you sure this will remove all data related to this counter in database' ).setCritical( true );
		
		this.moreButton = new ArdeButton( 'More Info' ).cls( 'passive' );
		this.moreButton.setFloat( 'right' ).style( 'marginTop', '5px' );
		
		this.moreCloseButton = new ArdeButton( 'Less Info' ).cls( 'passive' );
		this.moreCloseButton.setFloat( 'right' ).setDisplay( false ).style( 'marginTop', '5px' );
		
		
		
		p.append( this.moreButton ).append( this.moreCloseButton );
		p.append( this.applyButton ).append( this.cleanupButton ).append( this.restoreButton ).append( this.resetButton ).append( this.deleteButton );
		
		this.morePane = new ArdeComponent( 'div' ).cls( 'group' ).setDisplay( false ).appendTo( this );
		this.morePane.append( ardeE( 'p' ).append( ardeT( 'Internal ID: ' ) ).append( ardeE( 'span' ).cls( 'fixed' ).append( ardeT( this.id ) ) ) );
		
		this.onOffHistoryRequester = new ArdeRequestLabel( 'Loading On-Off history...' ).setDisplay( false );
		this.availDiv = ardeE( 'div' ).cls( 'margin_std' ).append( this.onOffHistoryRequester );
		this.availability = null;
		this.morePane.append( this.availDiv );
	}
	
	var self = this;
	if( !ArdeClass.instanceOf( this, NewCounter ) ) {
		this.applyButton.onclick = function () { self.applyClicked(); };
		this.applyButton.afterResultReceived = function ( result ) { self.applyConfirmed( result ); };
		
		this.cleanupButton.onclick = function () { self.cleanupClicked(); };
		
		this.restoreButton.onclick = function () { self.restoreClicked(); };
		this.restoreButton.afterResultReceived = function ( result ) { self.restoreConfirmed( result ); };
		
		this.deleteButton.onclick = function () { self.deleteClicked(); };
		this.deleteButton.afterResultReceived = function ( result ) { self.deleteConfirmed( result ); };
		
		this.cleanupButton.onclick = function () { self.cleanupClicked(); };
		
		this.resetButton.onclick = function () { self.resetClicked(); };
		
		this.periodTypesList.list.onchange = function () { self.somethingChanged(); };
		
		this.moreButton.element.onclick = function () {
			self.morePane.setDisplay( true );
			self.moreButton.setDisplay( false );
			self.moreCloseButton.setDisplay( true );
			self.loadMore();
		};
		this.moreCloseButton.element.onclick = function () {
			self.morePane.setDisplay( false );
			self.moreCloseButton.setDisplay( false );
			self.moreButton.setDisplay( true );
			
		};
		
		this.onOffHistoryRequester.afterResultReceived = function ( result ) { self.availabilityReceived( result ); };
		
	} else {
		this.addButton.onclick = function () { self.addClicked(); };
		this.addButton.afterResultReceived = function ( result ) { self.addConfirmed( result ); };
	}
	
	if( !ArdeClass.instanceOf( this, NewCounter ) ) {
		this.visBox = new ArdeComponent( 'div' ).cls( 'sub_block' ).appendTo( this );
		this.setPerm( this.perm );
		
	}
}

Counter.TYPE_SINGLE = 0;
Counter.TYPE_LIST = 1;
Counter.TYPE_GROUPED = 2;

Counter.prototype.setPerm = function( perm ) {
	this.perm = perm;
	this.visBox.clear();
	this.visibilitySelect = new UserPermissionSelector( this.perm );
	this.visApplyButton = new ArdeRequestButton( 'Apply' );
	this.visApplyButton.setStandardCallbacks( this, 'visApply' );
	if( selectedUser.isRoot() ) this.visApplyButton.button.setDisabled( true );
	var p =  ardeE( 'p' ).appendTo( this.visBox );
	p.append( ardeT( 'This counter is visible to ' ) ).append( selectedUser.getName() ).append( ardeT( ': ' ) ).append( this.visibilitySelect );
	p.append( ardeT( ' ' ) ).append( this.visApplyButton );
};

Counter.prototype.visApplyClicked = function () {
	this.visApplyButton.request( twatchFullUrl( 'rec/rec_counters.php' ), 'a=set_vis&i='+this.id+'&'+this.visibilitySelect.getParams() );
};

Counter.fromXml = function ( element ) {
	var id = ArdeXml.intAttribute( element, 'id' );
	var type = ArdeXml.intAttribute( element, 'type' );
	var name = ArdeXml.attribute( element, 'name' );
	
	var periodTypeEs = new ArdeXmlElemIter( ArdeXml.element( element, 'period_types' ), 'type' );
	var periodTypes = [];
	while( periodTypeEs.current ) {
		periodTypes.push( ArdeXml.intContent( periodTypeEs.current ) );
		periodTypeEs.next();
	}
	
	var when = ArdeExpression.fromXml( ArdeXml.element( element, 'when' ) );

	var delEs = new ArdeXmlElemIter( ArdeXml.element( element, 'delete' ), 'period_type' );
	var del = {};
	while( delEs.current ) {
		var pType = ArdeXml.intAttribute( delEs.current, 'id' );
		var age = ArdeXml.intAttribute( delEs.current, 'age' );
		del[ pType ] = age;
		delEs.next();
	}
	
	var on = ArdeXml.boolAttribute( element, 'on' );
	
	if( type == Counter.TYPE_SINGLE ) {
		return new SingleCounter( id, name, periodTypes, when, del, on, new Permission( 0, 0, true, true, true ) );
	}
	
	var entityId = ArdeXml.intAttribute( element, 'entity_id' );
	
	var trimEs = new ArdeXmlElemIter( ArdeXml.element( element, 'trim' ), 'period_type' );
	var trim = {};
	while( trimEs.current ) {
		var pType = ArdeXml.intAttribute( trimEs.current, 'id' );
		var age = ArdeXml.intAttribute( trimEs.current, 'age' );
		var top = ArdeXml.intAttribute( trimEs.current, 'top' );
		trim[ pType ] = [ age, top ];
		trimEs.next();
	}
	
	var activeTrimEs = new ArdeXmlElemIter( ArdeXml.element( element, 'active_trim' ), 'period_type' );
	var activeTrim = {};
	while( activeTrimEs.current ) {
		var pType = ArdeXml.intAttribute( activeTrimEs.current, 'id' );
		var days = ArdeXml.intAttribute( activeTrimEs.current, 'days' );
		var top = ArdeXml.intAttribute( activeTrimEs.current, 'top' );
		activeTrim[ pType ] = [ days, top ];
		activeTrimEs.next();
	}
	
	if( type == Counter.TYPE_LIST ) {
		return new ListCounter( id, name, periodTypes, when, del, on, trim, entityId, activeTrim, new Permission( 0, 0, true, true, true ) );
	}
	
	var groupEntityId = ArdeXml.intAttribute( element, 'group_entity_id' );
	
	if( type == Counter.TYPE_GROUPED ) {
		return new GroupedCounter( id, name, periodTypes, when, del, on, trim, entityId, activeTrim, groupEntityId, new Permission( 0, 0, true, true, true ) );
	}
	
	throw new ArdeException( 'invalid counter type '+type );
};

Counter.prototype.loadMore = function () {
	if( this.availability != null ) {
		this.availability.remove();
		this.availability = null;
	}
	this.onOffHistoryRequester.setDisplay( true );
	this.onOffHistoryRequester.request( twatchFullUrl( 'rec/rec_counters.php' ), 'a=get_availability&i='+this.id, CounterAvailability );
};

Counter.prototype.availabilityReceived = function( avail ) {
	this.onOffHistoryRequester.setDisplay( false );
	this.availDiv.append( avail );
	this.availability = avail;
};



Counter.prototype.somethingChanged = function () {
	var ptRemoved = false;
	found:
	for( var i in this.periodTypes ) {
		for( var j in this.periodTypesList.list.items ) {
			if( this.periodTypes[i] == this.periodTypesList.list.items[j].typeId ) continue found; 
		}
		ptRemoved = true;
		break;
	}
	this.applyButton.setCritical( ptRemoved );
};

Counter.prototype.resetClicked = function () {
	this.resetButton.request( twatchFullUrl( 'rec/rec_counters.php' ), 'a=reset&i='+this.id );
};

Counter.prototype.cleanupClicked = function () {
	this.cleanupButton.request( twatchFullUrl( 'rec/rec_counters.php' ), 'a=cleanup&i='+this.id );
};

Counter.prototype.startClicked = function () {
	this.startButton.request( twatchFullUrl( 'rec/rec_counters.php' ), 'a=start&i='+this.id );
};

Counter.prototype.startConfirmed = function ( result ) {
	this.setOn( true );
	this.stopButton.ok();
};

Counter.prototype.stopClicked = function () {
	this.stopButton.request( twatchFullUrl( 'rec/rec_counters.php' ), 'a=stop&i='+this.id );
};

Counter.prototype.stopConfirmed = function ( result ) {
	this.setOn( false );
	this.startButton.ok();
};

Counter.prototype.setOn = function( on ) {
	this.on = on;
	this.onP.setDisplay( on );
	this.offP.setDisplay( !on );
};

Counter.prototype.check = function() {
	if( this.nameInput.element.value == '' ) {
		alert( 'name can not be empty' );
		return false;
	}
	return true;
};

Counter.prototype.applyClicked = function() {
	if( !this.check() ) return;
	this.applyButton.request( twatchFullUrl( 'rec/rec_counters.php' ), 'a=change&'+this.getParams() );
};


Counter.prototype.getParams = function() {
	if( !ArdeClass.instanceOf( this, NewCounter ) ) {
		var id = 'i='+this.id+'&';
	} else {
		var id = '';
	}
	var pt = '';
	var d = '';
	var j = 0;
	for( var i in this.periodTypesList.list.items ) {
		if( this.periodTypesList.list.items[i].typeId != Period.ALL && this.periodTypesList.list.items[i].delCheck.element.checked ) {
			d += (j==0?'':'+')+this.periodTypesList.list.items[i].typeId+'+'+ardeEscape( this.periodTypesList.list.items[i].delInput.element.value );
			++j;
		}
		pt += (i==0?'':'+')+this.periodTypesList.list.items[i].typeId;
	}
	
	w = ardeEscape( this.whenInput.getParam() );
	return id+'t='+this.constructor.type+'&n='+ardeEscape( this.nameInput.element.value )+'&pt='+pt+'&w='+w+'&d='+d;
};

Counter.prototype.applyConfirmed = function ( result ) {
	this.titleText.n.nodeValue = this.nameInput.element.value;
	this.periodTypes = [];
	for( var i in this.periodTypesList.list.items ) {
		this.periodTypes.push( this.periodTypesList.list.items[i].typeId );
	}
	this.somethingChanged();
};

Counter.prototype.deleteClicked = function() {
	this.deleteButton.request( twatchFullUrl( 'rec/rec_counters.php' ), 'a=delete&i='+this.id );
};

Counter.prototype.deleteConfirmed = function ( result ) {
	this.ardeList.removeCounter( this );
};

Counter.prototype.restoreClicked = function () {
	this.restoreButton.request( twatchFullUrl( 'rec/rec_counters.php' ), 'a=restore&i='+this.id, Counter );
};

Counter.prototype.restoreConfirmed = function ( counter ) {
	counter.setPerm( this.perm );
	this.ardeList.replaceCounter( this, counter );
	counter.restoreButton.ok();
};

Counter.prototype.clear = function() {
	this.nameInput.element.value = '';
};

ArdeClass.extend( Counter, ArdeComponent );

function SingleCounter( id, name, periodTypes, when, del, on, perm ) {
	this.Counter( id, name, periodTypes, when, del, on, perm );
}
SingleCounter.typeName = 'single';
SingleCounter.type = Counter.TYPE_SINGLE;
ArdeClass.extend( SingleCounter, Counter );

function ListCounter( id, name, periodTypes, when, del, on, trim, entityId, activeTrim, perm ) {
	this.trim = trim;
	this.entityId = entityId;
	this.activeTrim = activeTrim;
	this.Counter( id, name, periodTypes, when, del, on, perm );
}
ListCounter.typeName = 'list';
ListCounter.type = Counter.TYPE_LIST;
ListCounter.prototype.getParams = function() {
	var t = '';
	var j = 0;
	for( var i in this.periodTypesList.list.items ) {
		if( this.periodTypesList.list.items[i].typeId == Period.ALL ) continue;
		if( this.periodTypesList.list.items[i].trimCheck.element.checked ) {
			t += (j==0?'':'+')+this.periodTypesList.list.items[i].typeId+'+'+ardeEscape( this.periodTypesList.list.items[i].trimInput.element.value )+'_'+ardeEscape( this.periodTypesList.list.items[i].trimTopInput.element.value );
			++j;
		}
	}

	var at = '';
	var j = 0;
	for( var i in this.periodTypesList.list.items ) {
		if( this.periodTypesList.list.items[i].activeTrimCheck.element.checked ) {
			at += (j==0?'':'+')+this.periodTypesList.list.items[i].typeId+'+'+ardeEscape( this.periodTypesList.list.items[i].activeTrimInput.element.value )+'_'+ardeEscape( this.periodTypesList.list.items[i].activeTrimTopInput.element.value );
			++j;
		}
	}
	
	if( ArdeClass.instanceOf( this, NewCounter ) ) {
		var ei = '&ei='+this.entitySelect.selectedOption().value;
	} else {
		var ei = '';
	}
	return this.Counter_getParams()+ei+'&tr='+t+'&atr='+at;
};


ArdeClass.extend( ListCounter, Counter );

function GroupedCounter( id, name, periodTypes, when, del, on, trim, entityId, activeTrim, groupId, perm ) {
	this.groupId = groupId;
	this.ListCounter( id, name, periodTypes, when, del, on, trim, entityId, activeTrim, perm );
}
GroupedCounter.typeName = 'grouped';
GroupedCounter.type = Counter.TYPE_GROUPED;

GroupedCounter.prototype.getParams = function() {
	if( ArdeClass.instanceOf( this, NewCounter ) ) {
		var gi = '&gi='+this.groupSelect.selectedOption().value;
	} else {
		var gi = '';
	}
	return this.ListCounter_getParams()+gi;
};

ArdeClass.extend( GroupedCounter, ListCounter );

function NewCounter() {
	this.cls( 'special_block' );
	var self = this;
	this.typeSelect.element.onchange = function () {
		countersHolder.showNew( self.typeSelect.selectedOption().value );
	};
}


NewCounter.prototype.addClicked = function () {
	if( !this.check() ) return;
	this.addButton.request( twatchFullUrl( 'rec/rec_counters.php' ), 'a=add&'+this.getParams(), Counter );
};

NewCounter.prototype.addConfirmed = function ( counter ) {
	this.holder.insertFirstItem( counter );
	this.clear();
};

NewCounter.prototype.resetTypeSelect = function () {
	for( var i = 0 ; i < this.typeSelect.element.options.length ; ++i ) {
		if( this.typeSelect.element.options[i].value == this.constructor.typeName ) {
			this.typeSelect.element.options[i].selected = true;
			return;
		}
	}
};

function NewSingleCounter() {
	this.SingleCounter( null, '', defaultPeriodTypes, [], newDel, false, null );
	this.NewCounter();	
}
NewSingleCounter.title = 'New Single Counter';
ArdeClass.extend( NewSingleCounter, SingleCounter );
ArdeClass.extend( NewSingleCounter, NewCounter );

function NewListCounter() {
	
	this.ListCounter( null, '', defaultPeriodTypes, [], newDel, false, defaultTrim, 0, newActiveTrim, null );
	this.NewCounter();
}
NewListCounter.title = 'New List Counter';

NewListCounter.prototype.check = function() {
	
	if( this.entitySelect.selectedOption() == null ) {
		alert( 'select a group' );
		return false;
	}
	return this.ListCounter_check();
};

ArdeClass.extend( NewListCounter, ListCounter );
ArdeClass.extend( NewListCounter, NewCounter );

function NewGroupedCounter() {
	this.GroupedCounter( null, '', defaultPeriodTypes, [], newDel, false, defaultTrim, 0, newActiveTrim, 0, null );
	this.NewCounter();
}
NewGroupedCounter.title = 'New Grouped Counter';

NewGroupedCounter.prototype.check = function() {
	
	if( this.entitySelect.selectedOption() == null ) {
		alert( 'select a data' );
		return false;
	}
	if( this.groupSelect.selectedOption() == null ) {
		alert( 'select a group' );
		return false;
	}
	
	return this.GroupedCounter_check();
};

ArdeClass.extend( NewGroupedCounter, GroupedCounter );
ArdeClass.extend( NewGroupedCounter, NewCounter );



function PeriodTypesList() {
	this.ArdeComponent( 'div' );
	
	this.select = new ArdeSelect();
	this.addButton = new ArdeButton( 'Add' ).cls( 'passive' );
	
	this.append( ardeE( 'div' ).append( this.select ).append( this.addButton ) );
	
	this.list = new ArdeCompListComp( 'div' );
	
	this.append( ardeE( 'div' ).append( this.list ) );
	
	this.listElements = {};
	
	var self = this;
	this.addButton.element.onclick = function () {
		if( self.select.selectedOption() != null ) {
			self.list.addItem( self.listElements[ self.select.selectedOption().value ] );
			self.select.element.options[ self.select.element.selectedIndex ] = null;
		}
	};
	
}
ArdeClass.extend( PeriodTypesList, ArdeComponent );

defaultDel = {};
defaultDel[ Period.DAY ] = 90;
defaultDel[ Period.MONTH ] = 12;

newDel = {};
newDel[ Period.DAY ] = 90;

defaultTrim = {};
defaultTrim[ Period.DAY ] = [3,20];
defaultTrim[ Period.MONTH ] = [1,20];
defaultTrim[ Period.ALL ] = [1,20];

defaultActiveTrim = {};
defaultActiveTrim[ Period.DAY ] = [30,20];
defaultActiveTrim[ Period.MONTH ] = [30,20];
defaultActiveTrim[ Period.ALL ] = [30,20];

newActiveTrim = {};
newActiveTrim[ Period.ALL ] = [30,20];

defaultPeriodTypes = [ Period.DAY, Period.MONTH, Period.ALL ];

function PeriodTypeElement( typeId, list, del ) {
	this.typeId = typeId;
	this.list = list;
	this.ArdeComponent( 'div' );
	this.cls( 'sub_block pad_half margin_half' );
	this.removeButton = new ArdeButton( 'Remove' ).cls( 'passive' ).setFloat( 'right' ).style( 'marginLeft', '5px' );
	
	
	this.p = ardeE( 'p' ).cls( 'margin_half' ).appendTo( this );
	this.p.append( this.removeButton );
	
	var self = this;
	this.removeButton.element.onclick = function () {
		self.list.list.removeItem( self );
		self.list.select.append( ardeE( 'option' ).append( ardeT( Period.typeStrings[ self.typeId ] ) ).attr( 'value', typeId ) );
	};
	
	
	this.p.append( ardeE( 'span' ).style( 'fontWeight', 'bold' ).append( ardeT( Period.typeStrings[ typeId ] ) ) ).append( ardeT( ' ' ) );

	if( typeId != Period.ALL ) {
		this.delCheck = new ArdeCheckBox( typeof del[ typeId ] != 'undefined' );
		this.p.append( this.delCheck);
		
		this.delSpan = new ArdeComponent( 'span' ).setOpacity( this.delCheck.element.checked?1:.5 ).appendTo( this.p );
		this.delSpan.append( ardeT( 'delete' ) );
		this.delInput = new ArdeInput( typeof del[ typeId ] == 'undefined' ? defaultDel[ typeId ] : del[ typeId ] );
		this.delInput.attr( 'size', '2' );
		this.delSpan.append( ardeT( ' data older than ' ) ).append( this.delInput ).append( ardeT( ' '+Period.numStrings[ typeId ]+' ' ) );
		
		var self = this;
		this.delCheck.element.onclick = function () {
			self.delSpan.setOpacity( self.delCheck.element.checked?1:.5 );
		};
	}
	this.append( ardeE( 'div' ).append( ardeT( ' ' ) ).cls( 'clear' ) );
}
ArdeClass.extend( PeriodTypeElement, ArdeComponent );

function ListPeriodTypeElement( typeId, list, del, trim, activeTrim ) {
	
	this.PeriodTypeElement( typeId, list, del );
	
	var self = this;
	
	if( typeId != Period.ALL ) {

		this.p.append( ardeE( 'br' ) );
		this.trimCheck = new ArdeCheckBox( typeof trim[ typeId ] != 'undefined' );
		this.p.append( this.trimCheck );
		this.trimSpan = new ArdeComponent( 'span' ).setOpacity( self.trimCheck.element.checked?'1':'.5' ).appendTo( this.p );
		this.trimSpan.append( ardeT( 'trim' ) );
		this.trimInput = new ArdeInput( typeof trim[ typeId ] == 'undefined' ? defaultTrim[ typeId ][0] : trim[ typeId ][0] );
		this.trimInput.attr( 'size', '2' );
		this.trimSpan.append( ardeT( ' data older than ' ) ).append( this.trimInput ).append( ardeT( ' '+Period.numStrings[ typeId ] ) );
		this.trimTopInput = new ArdeInput( typeof trim[ typeId ] == 'undefined' ? defaultTrim[ typeId ][1] : trim[ typeId ][1] );
		this.trimTopInput.attr( 'size', '2' );
		this.trimSpan.append( ardeT( ', keep only top ' ) ).append( this.trimTopInput );
		
		this.trimCheck.element.onclick = function() {
			self.trimSpan.setOpacity( self.trimCheck.element.checked?'1':'.5' )
		};
	}	
	
	this.p.append( ardeE( 'br' ) );
	this.activeTrimCheck = new ArdeCheckBox( typeof activeTrim[ typeId ] != 'undefined' );
	this.p.append( this.activeTrimCheck );
	
	this.activeTrimSpan = new ArdeComponent( 'span' ).setOpacity( self.activeTrimCheck.element.checked?'1':'.5' ).appendTo( this.p );
	this.activeTrimSpan.append( ardeT( 'active trim' ) );
	this.activeTrimInput = new ArdeInput( typeof activeTrim[ typeId ] == 'undefined' ? defaultActiveTrim[ typeId ][0] : activeTrim[ typeId ][0] );
	this.activeTrimInput.attr( 'size', '2' );
	this.activeTrimSpan.append( ardeT( ' every ' ) ).append( this.activeTrimInput ).append( ardeT( ' days' ) );
	this.activeTrimTopInput = new ArdeInput( typeof activeTrim[ typeId ] == 'undefined' ? defaultActiveTrim[ typeId ][1] : activeTrim[ typeId ][1] );
	this.activeTrimTopInput.attr( 'size', '2' );
	this.activeTrimSpan.append( ardeT( ', top ' ) ).append( this.activeTrimTopInput ).append( ardeT( ' rows' ) );
	
	this.activeTrimCheck.element.onclick = function() {
		self.activeTrimSpan.setOpacity( self.activeTrimCheck.element.checked?'1':'.5' );
	};
	
		
		
		
}
ArdeClass.extend( ListPeriodTypeElement, PeriodTypeElement );

