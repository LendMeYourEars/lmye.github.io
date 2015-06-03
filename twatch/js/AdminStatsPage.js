
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
    
function StatsPagesHolder( visibility ) {
	this.ArdeComponent( 'div' );
	this.append( ardeE( 'h1' ).append( ardeT( 'Stats Pages' ) ) ); 
	this.append( twatchPerUserWebsite() );
	this.visSelect = new BinarySelector( visibility, 'Visible', 'Hidden', 'Default' );
	this.visApplyButton = new ArdeRequestButton( 'Apply' );
	this.visApplyButton.setStandardCallbacks( this, 'visApply' );
	this.append( ardeE( 'p' ).append( ardeT( 'Stats Pages Visibility: ' ) ).append( this.visSelect ).append( ardeT( ' ' ) ).append( this.visApplyButton ) );
	this.menuDiv = new ArdeComponent( 'div' ).attr( 'id', 'sub_menu' ).style( 'marginTop', '10px' );
	
	this.append( this.menuDiv );
	
	this.newButton = new ArdeComponent( 'span' ).cls( 'button special' ).append( ardeT( 'New Stats Page' ) ).setClickable( true );
	this.menuDiv.append( this.newButton );
	
	var self = this;
	this.newButton.element.onclick = function () { self.showNew(); };
	
	this.newStatsPage = new NewStatsPage();
	this.newStatsPage.holder = this;
	this.newStatsPage.setDisplay( false );
	this.append( this.newStatsPage );
	
	this.statsPages = {};
	this.buttons = {};
	this.nowShowing = null;
}

StatsPagesHolder.prototype.visApplyClicked = function () {
	this.visApplyButton.request( twatchFullUrl( 'rec/rec_stats_pages.php' ), 'a=set_vis&'+this.visSelect.getParams() );
};

StatsPagesHolder.prototype.addStatsPage = function( statsPage ) {
	
	statsPage.holder = this;
	
	if( this.nowShowing == null ) {
		this.nowShowing = statsPage;
	} else {
		statsPage.setDisplay( false );
	}
	this.append( statsPage );
	this.statsPages[ statsPage.id ] = statsPage;
	
	this.buttons[ statsPage.id ] = new ArdeComponent( 'span' ).cls( 'button' ).append( ardeT( statsPage.name ) ).setClickable( true );
	if( this.nowShowing == statsPage ) {
		this.buttons[ statsPage.id ].addClass( 'selected' );
		this.buttons[ statsPage.id ].setClickable( false );
	}
	
	var self = this;
	
	this.buttons[ statsPage.id ].element.onclick = function () {
		self.show( statsPage.id );
	};
	this.menuDiv.insertBefore( this.buttons[ statsPage.id ], this.newButton );
};

StatsPagesHolder.prototype.removeStatsPage = function( id ) {
	
	if( this.nowShowing == this.statsPages[id] ) {
		var prevId = null;
		for( var i in this.statsPages ) {
			if( i == id ) {
				if( prevId != null ) this.show( prevId );
				else this.showNew();
			}
			prevId = i;
		}
	}
	this.statsPages[ id ].remove();
	this.buttons[ id ].remove();
	
	var newStatsPages = {};
	var newButtons = {};
	for( var i in this.statsPages ) {
		if( i != id ) {
			newStatsPages[ i ] = this.statsPages[ i ];
			newButtons[ i ] = this.buttons[ i ];
		}
	}
	this.statsPages = newStatsPages;
	this.buttons = newButtons;
};

StatsPagesHolder.prototype.replace = function( id, replacement ) {
	if( this.nowShowing == this.statsPages[ id ] ) this.nowShowing = replacement;
	this.statsPages[ id ].replace( replacement );
	this.statsPages[ id ] = replacement;
	replacement.holder = this;
	this.buttons[ id ].clean();
	this.buttons[ id ].append( ardeT( replacement.name ) );
};

StatsPagesHolder.prototype.updateName = function( id ) {
	this.buttons[ id ].clean();
	this.buttons[ id ].append( ardeT( this.statsPages[ id ].name ) );
};

StatsPagesHolder.prototype.show = function( statsPageId ) {
	this.nowShowing = this.statsPages[ statsPageId ];
	this.newStatsPage.setDisplay( false );
	this.newButton.removeClass( 'selected' );
	this.newButton.setClickable( true );
	for( var i in this.statsPages ) {
		this.statsPages[ i ].setDisplay( i == statsPageId );
		this.buttons[ i ].setClickable( i != statsPageId );
		if( i == statsPageId ) {
			this.buttons[ i ].addClass( 'selected' );
			
		} else {
			this.buttons[ i ].removeClass( 'selected' );
		}
	}
};

StatsPagesHolder.prototype.showNew = function() {
	for( var i in this.statsPages ) {
		this.statsPages[ i ].setDisplay( false );
		this.buttons[ i ].removeClass( 'selected' );
		this.buttons[ i ].setClickable( true );
	}
	this.newStatsPage.setDisplay( true );
	this.newButton.addClass( 'selected' );
	this.newButton.setClickable( false );
	this.nowShowing = null;
};

ArdeClass.extend( StatsPagesHolder, ArdeComponent );

function StatsPage( id, name, width ) {
	this.id = id;
	this.name = name;
	this.width = width;
	
	this.sCounterVs = [];
	this.lCounterVs = [];
	
	this.ArdeComponent( 'div' );
	this.cls( 'margin_std pad_std' );
	
	this.nameText = ardeT( this instanceof NewStatsPage ? 'New Stats Page' : this.name );
	this.append( ardeE( 'h2' ).append( this.nameText ) );
	
	var div = ardeE( 'div' ).cls( this instanceof NewStatsPage ? 'special_block' : 'block' ).appendTo( this );
	var tb = ardeE( 'tbody' ).appendTo( new ArdeTable().cls( 'form' ).appendTo( div ) );
	var tr = ardeE( 'tr' ).appendTo( tb );
	tr.append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'name:' ) ) );
	this.nameInput = new ArdeInput( name );
	tr.append( ardeE( 'td' ).cls( 'tail' ).append( this.nameInput ) );
	
	tr = ardeE( 'tr' ).appendTo( tb );
	tr.append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'width:' ) ) );
	this.widthInput = new ArdeInput( width );
	this.widthInput.element.size = 2;
	tr.append( ardeE( 'td' ).cls( 'tail' ).append( this.widthInput ).append( ardeT( ' columns' ) ) );
	
	var self = this;
	
	var p = ardeE( 'p' ).appendTo( div );
	
	
	if( !( this instanceof NewStatsPage ) ) {
		this.applyButton = new ArdeRequestButton( 'Apply Changes' );
		p.append( this.applyButton );
		
		if( this.id == 1 ) {
			this.restoreButton = new ArdeRequestButton( 'Restore Defaults', 'Are you sure? this will revert all change you made to this stats page.' ).setCritical( true );
			p.append( ardeT( ' ' ) ).append( this.restoreButton );
		} else {
			this.deleteButton = new ArdeRequestButton( 'Delete', 'Are You sure?' ).setCritical( true );
			p.append( ardeT( ' ' ) ).append( this.deleteButton );
		}
	
		this.append( ardeE( 'h2' ).append( ardeT( 'Single Counter Views' ) ) );
		this.singleCViewsHolder = new ArdeComponent( 'div' ).cls( 'indent_pad' ).appendTo( this );
		this.append( ardeE( 'h2' ).append( ardeT( 'List Counter Views' ) ) );
		this.listCViewsHolder = new ArdeComponent( 'div' ).cls( 'indent_pad' ).appendTo( this );
	
	
		this.newSingleCView = new NewSingleCounterView( this );
		this.newSingleCView.fillCounterSelect();
		this.singleCViewsHolder.append( this.newSingleCView );
		this.newListCView = new NewListCounterView( this );
		this.newListCView.fillCounterSelect();
		this.listCViewsHolder.append( this.newListCView );
		
		this.applyButton.onclick = function () { self.applyClicked(); };
		this.applyButton.afterResultReceived = function ( result ) { self.applyConfirmed( result ); };
		
		if( this.id == 1 ) {
			this.restoreButton.onclick = function () { self.restoreClicked(); };
			this.restoreButton.afterResultReceived = function ( result ) { self.restoreConfirmed( result ); };
		} else {
			this.deleteButton.onclick = function () { self.deleteClicked(); };
			this.deleteButton.afterResultReceived = function ( result ) { self.deleteConfirmed( result ); };
		}
	} else {
		
		this.addButton = new ArdeRequestButton( 'Add Stats Page' );
		p.append( this.addButton );
		
		this.addButton.onclick = function () { self.addClicked(); };
		this.addButton.afterResultReceived = function ( result ) { self.addConfirmed( result ); };
	}
	
	
	
}

StatsPage.fromXml = function ( element ) {
	var id = ArdeXml.intAttribute( element, 'id' );
	var width = ArdeXml.intAttribute( element, 'width' );
	var name = ArdeXml.strElement( element, 'name' );
	var o = new StatsPage( id, name, width );
	
	var sCounterVsE = ArdeXml.element( element, 'scountervs' );
	var sCounterVEs = new ArdeXmlElemIter( sCounterVsE, 'counter_view' );
	while( sCounterVEs.current ) {
		var cView = SingleCounterView.fromXml( sCounterVEs.current, o );
		o.addSingleCView( cView );
		sCounterVEs.next();
	}
	
	var lCounterVsE = ArdeXml.element( element, 'lcountervs' );
	var lCounterVEs = new ArdeXmlElemIter( lCounterVsE, 'counter_view' );
	while( lCounterVEs.current ) {
		var cView = ListCounterView.fromXml( lCounterVEs.current, o );
		o.addListCView( cView );
		lCounterVEs.next();
	}
	return o;
};

StatsPage.prototype.getParams = function () {
	
	if( this instanceof NewStatsPage ) {
		var i = '';
	} else {
		var i = 'i='+this.id+'&';
	}
	
	var s = i+'n='+ardeEscape( this.nameInput.element.value )+'&w='+ardeEscape( this.widthInput.element.value );
	var sc = '';
	var scp = '';
	for( var i in this.sCounterVs ) {
		sc += (i!=0?'+':'')+this.sCounterVs[i].id;
		scp += '&'+this.sCounterVs[i].getParams( 's'+this.sCounterVs[i].id+'_' );
	}
	var lc = '';
	var lcp = '';
	for( var i in this.lCounterVs ) {
		lc += (i!=0?'+':'')+this.lCounterVs[i].id;
		lcp += '&'+this.lCounterVs[i].getParams( 'l'+this.lCounterVs[i].id+'_' );
	}
	s += '&sc='+sc+'&lc='+lc+scp+lcp;
	return s;
	
};

StatsPage.prototype.check = function () {
	if( this.nameInput.element.value == '' ) {
		alert( 'name can not be empty' );
		return false;
	}
	return true;
};

StatsPage.prototype.applyClicked = function () {
	if( !this.check() ) return;
	for( var i in this.sCounterVs ) if( !this.sCounterVs[i].check() ) return;
	for( var i in this.lCounterVs ) if( !this.lCounterVs[i].check() ) return;
	
	this.applyButton.request( twatchFullUrl( 'rec/rec_stats_pages.php' ), 'a=change&'+this.getParams() );
};

StatsPage.prototype.applyConfirmed = function ( result ) {
	this.name = this.nameInput.element.value;
	this.nameText.n.nodeValue = this.name;
	this.holder.updateName( this.id );
	for( var i in this.sCounterVs ) this.sCounterVs[i].update();
	for( var i in this.lCounterVs ) this.lCounterVs[i].update();
	this.updateReferences();
};

StatsPage.prototype.deleteClicked = function () {
	this.deleteButton.request( twatchFullUrl( 'rec/rec_stats_pages.php' ), 'a=delete&i='+this.id );
};

StatsPage.prototype.deleteConfirmed = function () {
	this.holder.removeStatsPage( this.id );
};

StatsPage.prototype.addSingleCView = function ( counterView ) {
	this.sCounterVs.push( counterView );
	this.singleCViewsHolder.insertBefore( counterView, this.newSingleCView );
};

StatsPage.prototype.addListCView = function ( counterView ) {
	this.lCounterVs.push( counterView );
	this.listCViewsHolder.insertBefore( counterView, this.newListCView );
};

StatsPage.prototype.removeSingleCView = function ( id ) {
	var newSCounterVs = [];
	for( var i in this.sCounterVs ) {
		if( this.sCounterVs[i].id != id ) newSCounterVs.push( this.sCounterVs[i] );
		else this.sCounterVs[i].remove();
	}
	this.sCounterVs = newSCounterVs;
};

StatsPage.prototype.removeListCView = function ( id ) {
	var newLCounterVs = [];
	for( var i in this.lCounterVs ) {
		if( this.lCounterVs[i].id != id ) newLCounterVs.push( this.lCounterVs[i] );
		else {
			
			this.lCounterVs[i].remove();
		}
	}
	this.lCounterVs = newLCounterVs;
};




StatsPage.prototype.restoreClicked = function () {
	this.restoreButton.request( twatchFullUrl( 'rec/rec_stats_pages.php' ), 'a=restore&i='+this.id, StatsPage );
};

StatsPage.prototype.restoreConfirmed = function ( result ) {
	this.holder.replace( this.id, result );
	result.restoreButton.ok();
};

StatsPage.prototype.upClicked = function( cView ) {
	if( cView instanceof SingleCounterView ) {
		var a = this.sCounterVs;
		var holder = this.singleCViewsHolder;
	} else {
		var a = this.lCounterVs;
		var holder = this.listCViewsHolder;
	}
	if( a[0] == cView ) return;
	
	for( var i = 0; i < a.length; ++i ) {
		if( a[i] == cView ) {
			a[ i ] = a[ i - 1 ];
			a[ i - 1 ] = cView;
			break;
		}
	}

	holder.insertBefore( cView, cView.element.previousSibling );
	
	this.updateReferences();
};

StatsPage.prototype.downClicked = function( cView ) {
	if( cView instanceof SingleCounterView ) {
		var a = this.sCounterVs;
		var holder = this.singleCViewsHolder;
	} else {
		var a = this.lCounterVs;
		var holder = this.listCViewsHolder;
	}
	if( a[ a.length - 1 ] == cView ) return;

	for( var i = 0; i < a.length; ++i ) {
		if( a[i] == cView ) {
			a[ i ] = a[ i + 1 ];
			a[ i + 1 ] = cView;
			break;
		}
	}
	
	holder.insertBefore( cView, cView.element.nextSibling.nextSibling );
	
	this.updateReferences();
};

StatsPage.prototype.updateReferences = function() {
	for( var i in this.sCounterVs ) {
		this.sCounterVs[i].fillValueSelect();
	}
	for( var i in this.lCounterVs ) {
		this.lCounterVs[i].fillValueSelect();
	}
};

ArdeClass.extend( StatsPage, ArdeComponent );

function NewStatsPage() {
	this.StatsPage( null, '', 3 );
};

NewStatsPage.prototype.addClicked = function () {
	if( !this.check() ) return;
	this.addButton.request( twatchFullUrl( 'rec/rec_stats_pages.php' ), 'a=add&'+this.getParams(), StatsPage );
};

NewStatsPage.prototype.addConfirmed = function ( statsPage ) {
	this.holder.addStatsPage( statsPage );
	this.holder.show( statsPage.id );
	this.clear();
};

NewStatsPage.prototype.clear = function () {
	this.nameInput.element.value = '';
};

ArdeClass.extend( NewStatsPage, StatsPage );

function Counter( id, type, name, entityId, groupEntityId, groupAllowExplicitAdd, possibleSubs, set ) {
	this.id = id;
	this.name = name;
	this.type = type;
	this.groupEntityId = groupEntityId;
	this.entityId = entityId;
	this.groupAllowExplicitAdd = groupAllowExplicitAdd;
	this.possibleSubs = possibleSubs;
	this.set = set;
};
Counter.TYPE_SINGLE = 0;
Counter.TYPE_LIST = 1;
Counter.TYPE_SUB = 2;

function CounterView() {
	var self = this;
	
	this.ArdeComponent( 'div' );
	
	this.mainDiv = new ArdeComponent( 'div' ).appendTo( this );
	
	this.invalidCounter = false;
	if( this.counterId === null ) {
		this.counter = null;
	} else if( typeof counters[ this.counterId ] == 'undefined' ) {
		this.invalidCounter = true;
		this.counter = null;
	} else {
		this.counter = counters[ this.counterId ];
	}
	
	
	if( this instanceof NewSingleCounterView || this instanceof NewListCounterView || this instanceof NewSubCounterView ) {
		this.mainDiv.cls( 'special_block' );
	} else {
		if( this instanceof SubCounterView ) {
			this.mainDiv.cls( 'sub_block' );
		} else {
			this.mainDiv.cls( 'block' );
		}
		var floatDiv = new ArdeComponent( 'div' ).setFloat( 'right' ).cls( 'margin_std' ).appendTo( this.mainDiv );
		
		this.upButton = new ArdeButton( 'Up' ).cls( 'passive' );
		this.downButton = new ArdeButton( 'Down' ).cls( 'passive' );
		
		floatDiv.append( this.upButton ).append( this.downButton );
	}
	
	
	
	if( this instanceof NewSingleCounterView ) {
		this.titleText = ardeT( 'New Single Counter View' );
	} else if( this instanceof NewListCounterView ) {
		this.titleText = ardeT( 'New List Counter View' );
	} else if( this instanceof NewSubCounterView ) {
		this.titleText = ardeT( 'New Sub Counter View' );
	} else {
		this.titleText = ardeT( this.title );
	}
	
	this.mainDiv.append( ardeE( 'h3' ).append( this.titleText ) );
	
	var topTr = ardeE( 'tr' ).appendTo( ardeE( 'tbody' ).appendTo( new ArdeTable().appendTo( this.mainDiv ) ) );
	
	var tb = ardeE( 'tbody' ).appendTo( new ArdeTable().cls( 'form' ).appendTo( ardeE( 'td' ).style( 'width', '430px' ).appendTo( topTr ) ) );
	var tr = ardeE( 'tr' ).appendTo( tb );
	tr.append( ardeE( 'td' ).cls( 'head' ).style( 'width', '60px' ).append( ardeT( 'Title:' ) ) );
	this.titleInput = new ArdeInput( this.title );
	tr.append( ardeE( 'td' ).cls( 'tail' ).append( this.titleInput ) );
	
	tr = ardeE( 'tr' ).appendTo( tb );
	tr.append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'Counter:' ) ) );
	
	this.counterSelect = new ArdeSelect( 'select' );
	if( ArdeClass.instanceOf( this, NewCounterView ) ) {
		this.counterSelect.addDummyOption( 'Select...' );
	}
	
	this.counterSelect.setDisabled( this.graphView != null );
	
	var td = ardeE( 'td' ).cls( 'tail' ).appendTo( tr );
	td.append( this.counterSelect );
	
	
	
	if( ArdeClass.instanceOf( this, ListCounterView ) ) {
		this.groupSpan = new ArdeComponent( 'span' ).appendTo( td ).setDisplay( !ArdeClass.instanceOf( this, SubCounterView ) && !ArdeClass.instanceOf( this, NewCounterView ) && this.counter !== null && this.counter.type == Counter.TYPE_SUB );
		this.groupSelect = new ArdeActiveSelect( this.group, 10 );
		this.groupSpan.append( ardeT( ' Group: ' ) ).append( this.groupSelect );
		var self = this;
		this.groupSelect.request = function ( offset, count, beginWith ) {
			if( beginWith == '' ) b = '';
			else b = '&b='+ardeEscape( beginWith );
			var q = 'a=get_values&i='+counters[ self.counterSelect.selectedOption().value ].groupEntityId+'&o='+offset+'&c='+count+b+'&w='+websiteId;
			self.groupSelect.requester.request( twatchFullUrl( twatchUrl+'rec/rec_entity_values.php' ), q, EntityVList );
		};
		this.groupSelect.processResult = function( result ) {
			self.groupSelect.resultsReceived( result.a, result.more );
		};
		
		this.groupSelect.addClicked = function( str ) {
			self.groupSelect.addButton.request( twatchFullUrl( twatchUrl+'rec/rec_entity_values.php' ), 'a=add&ei='+counters[ self.counterSelect.selectedOption().value ].groupEntityId+'&s='+ardeEscape( str ), EntityV );
		};
	}
	
	if( this.invalidCounter ) {
		this.invalidCounterNotice = new ArdeComponent( 'tr' );
		this.invalidCounterNotice.appendTo( tb );
		
	}
	
	tr = ardeE( 'tr' ).appendTo( tb );
	tr.append( ardeE( 'td' ).cls( 'head' ).style( 'verticalAlign', 'top' ).append( ardeT( 'Divide By:' ) ) );
	this.divBySelect = new ArdeSelect( 'select' );
	this.divBySelect.setDisabled( this.graphView != null );
	for( var id in CounterView.divTexts ) {
		var option = new ardeE( 'option' ).attr( 'value', id ).append( ardeT( CounterView.divTexts[id] ) );
		if( id == this.divBy ) option.n.selected = true;
		this.divBySelect.append( option );
	}
	td = ardeE( 'td' ).cls( 'tail' ).appendTo( tr );
	td.append( this.divBySelect );
	this.divValueSelect = new ArdeSelect();
	this.fillValueSelect();
	this.divValueSelect.setDisplay( this.divBy == CounterView.DIV_VALUE );
	td.append( this.divValueSelect );
	
	this.divExtrasSpan = new ArdeComponent( 'span' ).appendTo( td );
	this.divExtrasSpan.setDisplay( this.divBy != CounterView.DIV_NONE );
	
	this.divLimitInput = new ArdeInput( this.divLimit ).attr( 'size', '2' );
	this.divLimitInput.setDisabled( this.graphView != null );
	this.divExtrasSpan.append( ardeT( ' minimum divisor:' ) ).append( this.divLimitInput );
	
	this.roundCheck = new ArdeCheckBox( this.round >= 0 );
	this.roundCheck.setDisabled( this.graphView != null );
	this.roundInputSpan = new ArdeComponent( 'span' );
	this.roundInput = new ArdeInput( this.round >= 0 ? this.round : 2 ).attr( 'size', '2' );
	this.roundInput.setDisabled( this.graphView != null ); 
	this.roundInputSpan.append( ardeT( ' to ' ) ).append( this.roundInput ).append( ardeT( ' digits after decimal point' ) );
	this.roundInputSpan.setDisplay( this.round >= 0 );
	this.divExtrasSpan.append( ardeT( ' round:' ) ).append( this.roundCheck ).append( this.roundInputSpan );
	
	if( ArdeClass.instanceOf( this, ListCounterView ) ) {
		
		this.percentRoundTr = new ArdeComponent( 'tr' ).appendTo( tb );
		this.percentRoundTr.setDisplay( this.graphView == null );
		this.percentRoundTr.append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'round percentage:' ) ) );
		td = ardeE( 'td' ).cls( 'tail' ).appendTo( this.percentRoundTr );
		
		this.percentRoundCheck = new ArdeCheckBox( this.percentRound >= 0 );
		
		this.percentRoundInput = new ArdeInput( this.percentRound >= 0 ? this.percentRound : 2 ).attr( 'size', '2' );

		this.percentRoundInputSpan = new ArdeComponent( 'span' ).setDisplay( this.percentRound >= 0 );
		this.percentRoundInputSpan.append( ardeT( ' to ' ) ).append( this.percentRoundInput ).append( ardeT( ' digits after decimal point' ) );
		
		td.append( this.percentRoundCheck ).append( this.percentRoundInputSpan );
		
		this.entityViewTr = new ArdeComponent( 'tr' ).appendTo( tb );
		this.entityViewTr.setDisplay( this.graphView == null );
		this.entityViewTr.append( ardeE( 'td' ).cls( 'head' ).append( ardeT( '' ) ) );
		if( this.counterId !== null && typeof counters[ this.counterId ] != 'undefined' ) {
			var entityId = counters[ this.counterId ].entityId;
		} else {
			var entityId = 0;
		}
		this.entityView.setEntityId( entityId );
		this.entityViewTr.append( ardeE( 'td' ).cls( 'tail' ).append( this.entityView ) );
		
		this.startFromTr = new ArdeComponent( 'tr' ).appendTo( tb );
		
		this.startFromSelect = new ArdeActiveSelect( this.startFrom, 7 );
		this.startFromSelect.setStandardCallbacks( this, 'startFrom' );
		this.startFromTr.append( new ArdeTd().setColSpan( 2 ).style( 'verticalAlign', 'top' ).cls( 'pad_full' ).style( 'paddingTop', '10px' ).append( ardeT( 'Start'+ardeNbsp()+'From: ' ) ).append( this.startFromSelect ) );
		this.startFromTr.setDisplay( typeof counters[ this.counterId ] != 'undefined' && counters[ this.counterId ].set !== false );
		
	}
	
	var topTd = ardeE( 'td' ).style( 'verticalAlign', 'top' ).appendTo( topTr );
	tb = ardeE( 'tbody' ).appendTo( new ArdeTable().cls( 'form' ).appendTo( topTd ) );
	tr = ardeE( 'tr' ).appendTo( tb );
	tr.append( ardeE( 'td' ).style( 'verticalAlign', 'top' ).cls( 'head' ).append( ardeT( 'Period Types:' ) ) );
	this.periodTypesList = new ArdePassiveList();
	this.periodTypesList.list.element.size = 3;
	this.periodTypesList.list.element.style.width = '150px';
	for( var typeId in Period.typeStrings ) {
		var option = ardeE( 'option' ).append( ardeT( Period.typeStrings[ typeId ] ) ).n;
		option.value = typeId;
		if( ardeArrayContains( this.periodTypes, typeId ) ) {
			this.periodTypesList.list.append( option );
		} else {
			this.periodTypesList.select.append( option );
		}
	}
	td = ardeE( 'td' ).cls( 'tail' ).append( this.periodTypesList );
	tr.append( td );
	
	if( ArdeClass.instanceOf( this, ListCounterView ) && !ArdeClass.instanceOf( this, SubCounterView ) ) {
		this.subsDiv = new ArdeComponent( 'div' ).cls( 'double_indent_pad' ).appendTo( this.mainDiv );
		this.ArdeComponentList( new ArdeComponent( 'div' ).appendTo( this.subsDiv ) );
		if( this.subs.length != 0 ) {
			for( var i in this.subs ) {
				this.subs[i].parent = this;
				this.subs[i].fillCounterSelect();
				this.addItem( this.subs[i] );
			}
		}
		
		this.newSubDiv = new ArdeComponent( 'div' ).appendTo( this.subsDiv );
		this.newSubButton = new ArdeButton( 'New Sub Counter View' ).cls( 'passive' );
		this.newSubDiv.append( ardeE( 'p' ).append( this.newSubButton ) );
		this.newSub = new NewSubCounterView( this ).setDisplay( false ).appendTo( this.newSubDiv );
		
		this.newSubDiv.setDisplay( this.counter !== null && counters[ this.counterId ].possibleSubs.length );
		
		var self = this;
		this.newSubButton.element.onclick = this.newSub.cancelButton.element.onclick = function () {
			self.newSubButton.switchDisplay();
			self.newSub.switchDisplay();
		}

	}
	
	if( ArdeClass.instanceOf( this, NewCounterView ) ) {
		var p = new ArdeComponent( 'p' ).appendTo( this.mainDiv );
		if( ArdeClass.instanceOf( this, NewSubCounterView ) ) {
			this.addButton = new ArdeButton( 'Add' ).cls( 'passive' );
			this.cancelButton = new ArdeButton( 'Cancel' ).cls( 'passive' );
			p.append( this.addButton ).append( ardeT( ' ' ) ).append( this.cancelButton );
		} else {
			this.addButton = new ArdeRequestButton( 'Add Counter View' );
			p.append( this.addButton );
		}
		
	} else if( !ArdeClass.instanceOf( this, SubCounterView ) ) {
		this.applyButton = new ArdeRequestButton( 'Apply Changes' );
		
		this.deleteButton = new ArdeRequestButton( 'Delete' ).setCritical( true );
		
		this.mainDiv.append( ardeE( 'p' ).append( this.applyButton ).append( ardeT( ' ' ) ).append( this.deleteButton ) );
		
	} else {
		this.deleteButton = new ArdeButton( 'Delete' ).cls( 'passive' );
		this.mainDiv.append( ardeE( 'p' ).append( this.deleteButton ) );
	}
	
	if( ArdeClass.instanceOf( this, ListCounterView ) ) {
		this.rowsTr = new ArdeComponent( 'tr' ).appendTo( tb );
		this.rowsTr.setDisplay( this.graphView == null );
		this.rowsTr.append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'Limit Rows:' ) ) );
		this.rowsCheck = new ArdeCheckBox( this.rows != 0 );
		
		this.rowsInput = new ArdeInput( this.rows?this.rows:7 ).attr( 'size', '2' );
		this.rowsInputSpan = new ArdeComponent( 'span' ).append( ardeT( 'to ' ) ).append( this.rowsInput );
		this.rowsInputSpan.setDisplay( this.rows != 0 );
		td = ardeE( 'td' ).cls( 'tail' ).append( this.rowsCheck ).append( this.rowsInputSpan );
		this.rowsTr.append( td );
	
		if( this.graphView != null ) {
			topTd.append( ardeE( 'p' ).append( ardeE( 'span' ).cls( 'info' ).append( ardeT( 'Graph View not fully editable yet' ) ) ) );
		}
		
	}
	
	
	
	this.roundCheck.element.onclick = function () {
		self.roundInputSpan.setDisplay( self.roundCheck.element.checked );
	};
	
	this.divBySelect.element.onchange = function () {
		var value = self.divBySelect.selectedOption().value;
		self.divExtrasSpan.setDisplay( value != CounterView.DIV_NONE );
		self.divValueSelect.setDisplay( value == CounterView.DIV_VALUE );
	};
	
	if( this instanceof SingleCounterView || this instanceof ListCounterView ) {
		this.applyButton.onclick = function () {
			self.applyClicked();
		};
		
		this.applyButton.afterResultReceived = function ( result ) {
			self.applyConfirmed( result );
		};
		
		this.deleteButton.onclick = function () {
			self.deleteClicked();
		};
		
		this.deleteButton.afterResultReceived = function ( result ) {
			self.deleteConfirmed( result );
		};
	}
	
	if( !ArdeClass.instanceOf( this, NewCounterView ) ) {
		if( !ArdeClass.instanceOf( this, SubCounterView ) ) {
			this.upButton.element.onclick = function () { self.statsPage.upClicked( self ); };
			this.downButton.element.onclick = function () { self.statsPage.downClicked( self ); };
		}
	} else {
		this.addButton.onclick = function () { self.addClicked(); };
		this.addButton.afterResultReceived = function ( result ) { self.addConfirmed( result ); };
	}
	
	
	this.counterSelect.element.onchange = function () {
		self.counterSelectChanged();
	};
}

CounterView.DIV_NONE = 0;
CounterView.DIV_HOUR_COUNT = 1;
CounterView.DIV_DAY_COUNT = 2;
CounterView.DIV_DAYS = 3;
CounterView.DIV_HOURS = 4;
CounterView.DIV_VALUE = 5;

CounterView.divTexts = {};
CounterView.divTexts[ CounterView.DIV_NONE ] = 'None';
CounterView.divTexts[ CounterView.DIV_HOUR_COUNT ] = 'Hour Count';
CounterView.divTexts[ CounterView.DIV_DAY_COUNT ] = 'Day Count';
CounterView.divTexts[ CounterView.DIV_DAYS ] = 'Days';
CounterView.divTexts[ CounterView.DIV_HOURS ] = 'Hours';
CounterView.divTexts[ CounterView.DIV_VALUE ] = 'Value Of';

CounterView.prototype.counterSelectChanged = function () {
	
	if( ( ArdeClass.instanceOf( this, NewCounterView ) || this.invalidCounter ) &&  this.counterSelect.element.selectedIndex <= 0 ) {
		this.counterId = null;
		this.counter = null;
		return;
	} else {
		this.counterId = this.counterSelect.selectedOption().value;
		this.counter = counters[ this.counterId ];
	}
	
	if( this.invalidCounter ) {
		this.invalidCounterOption.remove();
		this.invalidCounterNotice.remove();
		this.counterSelect.style( 'background', '#fff' ).style( 'color', '#000' );
		this.invalidCounter = false;
	}
};

CounterView.prototype.fillCounterSelect = function () {
	this.counterSelect.clearItems();
	
	if( this.invalidCounter ) {
		this.invalidCounterOption = new ArdeOption( '', '' );
		this.counterSelect.append( this.invalidCounterOption );
		this.counterSelect.style( 'background', '#800' ).style( 'color', '#fff' );
	}
	
	for( var id in counters ) {
		if( ArdeClass.instanceOf( this, SubCounterView ) ) {
			if( this.parent.counter === null ) return;
			if( !ardeArrayContains( this.parent.counter.possibleSubs, id ) ) continue;
		}
		if( ( ( ArdeClass.instanceOf( this, SingleCounterView ) ) && counters[ id ].type == Counter.TYPE_SINGLE )
			|| ( !( ArdeClass.instanceOf(this, SingleCounterView ) ) && counters[ id ].type != Counter.TYPE_SINGLE ) ) {
			var option = ardeE( 'option' ).append( ardeT( counters[ id ].name ) ).appendTo( this.counterSelect );
			option.n.value = id;
			if( id == this.counterId ) option.n.selected = true;
		}
	}
}

CounterView.prototype.fillValueSelect = function () {
	if( this.statsPage == null ) return;
	this.divValueSelect.clear();
	this.divValueSelect.append( ardeE( 'option' ).append( ardeT( 'Select...' ) ) );
	for( var i in this.statsPage.sCounterVs ) {
		if( ( this instanceof SingleCounterView ) && this.statsPage.sCounterVs[i] == this ) break;
		var option = new ardeE( 'option' ).attr( 'value', this.statsPage.sCounterVs[i].id ).append( ardeT( this.statsPage.sCounterVs[i].title ) );
		if( this.statsPage.sCounterVs[i].id == this.divByValueId ) option.n.selected = true;
		this.divValueSelect.append( option );
	}
};

CounterView.prototype.check = function () {
	if( this.counter === null ) return ardeAlert( 'please select a counter' );
	if( this.titleInput.element.value == '' ) return ardeAlert( 'enter a title please' );
	if( this.divBySelect.selectedOption().value == CounterView.DIV_VALUE && this.divValueSelect.element.selectedIndex == 0 ) {
		alert( 'please select a value to divide by for '+this.title );
		return false;
	}
	return true;
};

CounterView.prototype.getParams = function ( prefix ) {
	
	if( typeof prefix == 'undefined' ) prefix = '';
	
	if( !ArdeClass.instanceOf( this, SubCounterView ) ) {
		si = prefix+'si='+this.statsPage.id+'&';
	} else {
		si = '';
	}
	
	if( !( this instanceof NewSingleCounterView ) && !( this instanceof NewListCounterView ) ) {
		var ind = prefix+'i='+this.id+'&';
	} else {
		var ind = '';
	}
	var pt = '';
	for( var i = 0; i < this.periodTypesList.list.element.options.length; ++i ) {
		pt += (i?'+':'') + this.periodTypesList.list.element.options[i].value; 
	}
	if( this.divBySelect.selectedOption().value == CounterView.DIV_VALUE ) {
		var dv = '&'+prefix+'dv='+this.divValueSelect.selectedOption().value;
	} else {
		var dv = '';
	}
	if( this.divBySelect.selectedOption().value != CounterView.DIV_NONE ) {
		var l = ardeEscape( this.divLimitInput.element.value );
		if( this.roundCheck.element.checked ) {
			var r = ardeEscape( this.roundInput.element.value );
		} else {
			var r = '-1';
		}
	} else {
		var l = '0';
		var r = '-1';
	}
	
	return ind+si+prefix+'t='+ardeEscape( this.titleInput.element.value )+'&'+prefix+'ci='+this.counterSelect.selectedOption().value+
			'&'+prefix+'pt='+pt+'&'+prefix+'d='+this.divBySelect.selectedOption().value+dv+'&'+prefix+'l='+l+'&'+prefix+'r='+r;
};

CounterView.prototype.applyConfirmed = function( result ) {
	this.update();
	this.statsPage.updateReferences();
};

CounterView.prototype.update = function() {
	this.title = this.titleText.n.nodeValue = this.titleInput.element.value;
};

CounterView.prototype.clear = function() {
	this.titleInput.element.value = '';
	this.counterSelect.element.selectedIndex = 0;
};

CounterView.fromXml = function ( element, statsPage, list, sub ) {
	
	if( typeof statsPage == 'undefined' ) statsPage = null;
	if( typeof sub == 'undefined' ) sub = false;
	if( statsPage == null ) {
		var statsPageId = ArdeXml.intAttribute( element, 'stats_page_id', null );
		if( statsPageId != null ) statsPage = statsPagesHolder.statsPages[ statsPageId ];
	}
	
	var id = ArdeXml.intAttribute( element, 'id' );
	var counterId = ArdeXml.intAttribute( element, 'counter_id' );
	var title = ArdeXml.strElement( element, 'title' );
	var numberTitle = ArdeXml.strElement( element, 'number_title' );
	var divE = ArdeXml.element( element, 'div' );
	var divBy = ArdeXml.intAttribute( divE, 'by' );
	var divByValueId = ArdeXml.intAttribute( divE, 'value_id', null );
	var divLimit = ArdeXml.intAttribute( divE, 'limit' );
	var round = ArdeXml.intAttribute( divE, 'round' );
	
	var periodTypesE = ArdeXml.element( element, 'period_types' );
	var periodTypeEs = new ArdeXmlElemIter( periodTypesE, 'type' );
	var periodTypes = [];
	while( periodTypeEs.current ) {
		periodTypes.push( ArdeXml.intContent( periodTypeEs.current ) );
		periodTypeEs.next();
	}
	
	if( !list ) {
		return new SingleCounterView( statsPage, id, title, numberTitle, counterId, periodTypes, divBy, divByValueId, divLimit, round );
	}
	
	var rows = ArdeXml.intAttribute( element, 'rows' );
	var groupE = ArdeXml.element( element, 'group', null );
	if( groupE == null ) {
		var group = null;
	} else {
		var group = EntityV.fromXml( groupE );
	}
	var startFromE = ArdeXml.element( element, 'start_from', null );
	if( startFromE == null ) {
		var startFrom = null;
	} else {
		var startFrom = EntityV.fromXml( startFromE );
	}
	
	var percentRound = ArdeXml.intAttribute( element, 'percent_round' );
	var entityViewE = ArdeXml.element( element, 'entity_view' ); 
	var entityView = EntityView.fromXml( entityViewE );
	var graphView = ArdeXml.element( element, 'graph_view', null ) == null ? null : true;
	
	var subs = [];
	var subEs = new ArdeXmlElemIter( ArdeXml.element( element, 'subs' ), 'sub' );
	while( subEs.current ) {
		subs.push( SubCounterView.fromXml( subEs.current ) );
		subEs.next();
	}
	if( sub ) {
		return new SubCounterView( id, title, numberTitle, counterId, periodTypes, divBy, divByValueId, divLimit, round, group, graphView, rows, percentRound, entityView, subs, startFrom );
	} else {
		return new ListCounterView( statsPage, id, title, numberTitle, counterId, periodTypes, divBy, divByValueId, divLimit, round, group, graphView, rows, percentRound, entityView, subs, startFrom );
	}
};

ArdeClass.extend( CounterView, ArdeComponent );

function NewCounterView() {}

function SingleCounterView( statsPage, id, title, numberTitle, counterId, periodTypes, divBy, divByValueId, divLimit, round ) {
	this.id = id;
	this.title = title;
	this.numberTitle = numberTitle;
	this.counterId = counterId;
	this.periodTypes = periodTypes;
	this.divBy = divBy;
	this.divByValueId = divByValueId;
	this.divLimit = divLimit;
	this.round = round;
	this.statsPage = statsPage;
	
	this.CounterView();
	
	if( ( this instanceof SingleCounterView ) ) {
		this.fillCounterSelect();
		this.statsPage.addSingleCView( this );
	}
}

SingleCounterView.fromXml = function ( element, statsPage ) {
	return CounterView.fromXml( element, statsPage, false );
};

SingleCounterView.prototype.applyClicked = function () {
	if( !this.check() ) return;
	this.applyButton.request( twatchFullUrl( 'rec/rec_stats_pages.php' ), 'a=change_scview&'+this.getParams(), null );
};

SingleCounterView.prototype.deleteClicked = function () {
	this.deleteButton.request( twatchFullUrl( 'rec/rec_stats_pages.php' ), 'a=delete_scview&si='+this.statsPage.id+'&i='+this.id );
};

SingleCounterView.prototype.deleteConfirmed = function ( result ) {
	this.statsPage.removeSingleCView( this.id );
};

ArdeClass.extend( SingleCounterView, CounterView );

function NewSingleCounterView( statsPage ) {
	this.SingleCounterView( statsPage, null, '', '#', null, Period.allPeriodTypes, CounterView.DIV_NONE, null, 0, -1 );
}

NewSingleCounterView.prototype.addClicked = function () {
	if( this.counterSelect.element.selectedIndex <= 0 ) return alert( 'select a counter please' );
	if( !this.check() ) return;
	this.addButton.request( twatchFullUrl( 'rec/rec_stats_pages.php' ), 'a=add_scview&'+this.getParams(), SingleCounterView );
};



NewSingleCounterView.prototype.addConfirmed = function ( result ) {
	result.statsPage = this.statsPage;
	result.fillValueSelect();
	this.clear();
};


ArdeClass.extend( NewSingleCounterView, SingleCounterView );
ArdeClass.extend( NewSingleCounterView, NewCounterView );

function ListCounterView( statsPage, id, title, numberTitle, counterId, periodTypes, divBy, divByValueId, divLimit, round, group, graphView, rows, percentRound, entityView, subs, startFrom ) {
	this.id = id;
	this.title = title;
	this.numberTitle = numberTitle;
	this.counterId = counterId;
	this.periodTypes = periodTypes;
	this.divBy = divBy;
	this.divByValueId = divByValueId;
	this.divLimit = divLimit;
	this.round = round;
	this.group = group;
	this.graphView = graphView;
	this.rows = rows;
	this.percentRound = percentRound;
	this.entityView = entityView;
	this.subs = subs;
	this.startFrom = startFrom;
	
	
	this.statsPage = statsPage;
	
	this.CounterView();
	
	
	
	var self = this;

	this.percentRoundCheck.element.onclick = function () {
		self.percentRoundInputSpan.setDisplay( self.percentRoundCheck.element.checked );
	};
	
	this.rowsCheck.element.onclick = function () {
		self.rowsInputSpan.setDisplay( self.rowsCheck.element.checked );
	};
	

	
	if( ( this instanceof ListCounterView ) || ( this instanceof SingleCounterView ) ) {
		this.fillCounterSelect();
		this.statsPage.addListCView( this );
	}
}

ListCounterView.prototype.counterSelectChanged = function () {
	
	this.CounterView_counterSelectChanged();
	
	if( !ArdeClass.instanceOf( this, SubCounterView ) ) this.ArdeComponentList_clear();
	
	if( this instanceof NewListCounterView  && this.counterSelect.element.selectedIndex <= 0 ) {
		this.newSubDiv.setDisplay( false );
		this.groupSpan.setDisplay( false );
		this.startFromTr.setDisplay( false );
		this.entityView.setEntityId( 0 );
		return;
	}
	
	
	
	this.startFromTr.setDisplay( typeof counters[ this.counterId ] != 'undefined' && counters[ this.counterId ].set !== false );
	
	this.entityView.setEntityId( counters[ this.counterId ].entityId );
	
	if( counters[ this.counterId ].type == Counter.TYPE_SUB && !ArdeClass.instanceOf( this, SubCounterView ) ) {
		this.groupSelect.showAddButton( counters[ this.counterId ].groupAllowExplicitAdd );
		this.groupSelect.retract();
		this.groupSelect.setValue( null );
		this.groupSpan.setDisplay( true );
	} else {
		this.groupSelect.retract();
		this.groupSpan.setDisplay( false );
	}
	
	if( ArdeClass.instanceOf( this, SubCounterView ) ) return;
	
	if( counters[ this.counterId ].possibleSubs.length ) {
		this.newSubButton.setDisplay( true );
		this.newSub.setDisplay( false );
		this.newSub.fillCounterSelect();
		this.ArdeComponentList_clear();
		this.newSubDiv.setDisplay( true );
	} else {
		this.newSubDiv.setDisplay( false );
	}
}

ListCounterView.fromXml = function ( element, statsPage ) {
	return CounterView.fromXml( element, statsPage, true );
};

ListCounterView.prototype.startFromRequested = function ( offset, count, beginWith ) {
	if( typeof counters[ this.counterId ] == 'undefined' ) return;
	
	if( beginWith == '' ) b = '';
	else b = '&b='+ardeEscape( beginWith );
	
	this.startFromSelect.requester.request( twatchFullUrl( twatchUrl+'rec/rec_entity_values.php' ),
		'a=get_values&i='+counters[ this.counterId ].entityId+'&o='+offset+'&c='+count+b+'&w='+websiteId,
		ardeXmlObjectListClass( EntityV, 'entity_v', true, false )
	);
};

ListCounterView.prototype.startFromReceived = function ( result ) {
	this.startFromSelect.resultsReceived( result.a, result.more );
};


ListCounterView.prototype.clear = function () {
	this.CounterView_clear();
	if( !ArdeClass.instanceOf( this, SubCounterView ) ) {
		this.ArdeComponentList_clear();
	}
}

ListCounterView.prototype.getParams = function ( prefix ) {
	
	if( typeof prefix == 'undefined' ) prefix = '';
	
	if( this.percentRoundCheck.element.checked ) {
		var pr = ardeEscape( this.percentRoundInput.element.value );
	} else {
		var pr = '-1';
	}
	if( this.rowsCheck.element.checked ) {
		var rw = ardeEscape( this.rowsInput.element.value );
	} else {
		var rw = '0';
	}
	if( !ArdeClass.instanceOf( this, SubCounterView ) && counters[ this.counterSelect.selectedOption().value ].type == Counter.TYPE_SUB ) {
		var g = '&'+prefix+'g='+this.groupSelect.getValue().id;
	} else {
		var g = '';
	}
	
	if( !ArdeClass.instanceOf( this, SubCounterView ) ) {
		var subs = '&'+prefix+'subc='+this.items.length;
		for( var i in this.items ) {
			subs += '&'+this.items[i].getParams( prefix+'s'+i+'_' );
		}
	} else {
		var subs = '&'+prefix+'subc=0';
	}
	
	if( typeof counters[ this.counterId ] != 'undefined' && counters[ this.counterId ].set !== false ) {
		var sf = '&'+prefix+'sf='+this.startFromSelect.getValue().id;
	} else {
		var sf = '';
	}
	
	return this.CounterView_getParams( prefix )+g+'&'+prefix+'pr='+pr+'&'+prefix+'rw='+rw+'&'+this.entityView.getParams( prefix+'ev_' )+subs+sf;
};

ListCounterView.prototype.check = function () {
	if( !this.CounterView_check() ) return false;
	if( ArdeClass.instanceOf( this, NewCounterView ) &&  this.counterSelect.selectedOption() === null ) return ardeAlert( 'select a counter please' );
	if( counters[ this.counterSelect.selectedOption().value ].type == Counter.TYPE_SUB && !ArdeClass.instanceOf( this, SubCounterView ) ) {
		if( this.groupSelect.getValue() == null ) {
			alert( 'please select a group' );
			return false;
		}
	}
	if( typeof counters[ this.counterId ] != 'undefined' && counters[ this.counterId ].set !== false ) {
		if( this.startFromSelect.getValue() === null ) return ardeAlert( 'select a value for start from' );
	}
	return true;
};

ListCounterView.prototype.applyClicked = function () {
	if( !this.check() ) return;
	this.applyButton.request( twatchFullUrl( 'rec/rec_stats_pages.php' ), 'a=change_lcview&'+this.getParams(), null );
};

ListCounterView.prototype.deleteClicked = function () {
	this.deleteButton.request( twatchFullUrl( 'rec/rec_stats_pages.php' ), 'a=delete_lcview&si='+this.statsPage.id+'&i='+this.id );
};

ListCounterView.prototype.deleteConfirmed = function ( result ) {
	this.statsPage.removeListCView( this.id );
};

ArdeClass.extend( ListCounterView, CounterView );
ArdeClass.extend( ListCounterView, ArdeComponentList );

function SubCounterView( id, title, numberTitle, counterId, periodTypes, divBy, divByValueId, divLimit, round, group, graphView, rows, percentRound, entityView, subs, startFrom  ) {
	this.ListCounterView( null, id, title, numberTitle, counterId, periodTypes, divBy, divByValueId, divLimit, round, group, graphView, rows, percentRound, entityView, subs, startFrom );
	
	
	
	if( !ArdeClass.instanceOf( this, NewCounterView ) ) {
		var self = this;
		this.deleteButton.element.onclick = function () {
			self.ardeList.removeItem( self );
		}
		this.upButton.element.onclick = function () {
			self.ardeList.moveItemUp( self );
		}
		
		this.downButton.element.onclick = function () {
			self.ardeList.moveItemDown( self );
		}
	}
};

SubCounterView.fromXml = function ( element, statsPage ) {
	return CounterView.fromXml( element, statsPage, true, true );
};


ArdeClass.extend( SubCounterView, ListCounterView );



function NewListCounterView( statsPage ) {
	this.ListCounterView( statsPage, null, '', '#', null, Period.allPeriodTypes, CounterView.DIV_NONE, null, 0, -1, null, null, 7, 0, new EntityView( true, false, false ), [], null );
}


NewListCounterView.prototype.addClicked = function () {
	if( !this.check() ) return;
	this.addButton.request( twatchFullUrl( 'rec/rec_stats_pages.php' ), 'a=add_lcview&'+this.getParams(), ListCounterView );
};

NewListCounterView.prototype.addConfirmed = function ( result ) {
	result.statsPage = this.statsPage;
	result.fillValueSelect();
	this.clear();
};

ArdeClass.extend( NewListCounterView, ListCounterView );
ArdeClass.extend( NewListCounterView, NewCounterView );

function NewSubCounterView( parent ) {
	this.parent = parent;
	this.SubCounterView( null, '', '#', null, Period.allPeriodTypes, CounterView.DIV_NONE, null, 0, -1, null, null, 7, 0, new EntityView( true, false, false ), [], null );
	
	var self = this;
	this.addButton.element.onclick = function () {
		view = self.getView();
		view.parent = self.parent;
		view.fillCounterSelect();
		if( view === false ) return;
		self.parent.addItem( view );
		self.clear();
		self.setDisplay( false );
		self.parent.newSubButton.setDisplay( true );
	}
	
	if( !ArdeClass.instanceOf( this.parent, NewCounterView ) ) this.fillCounterSelect();
}



NewSubCounterView.prototype.getView = function () {
	if( !this.check() ) return false;
	var pt = [];
	for( var i = 0; i < this.periodTypesList.list.element.options.length; ++i ) {
		pt.push( parseInt( this.periodTypesList.list.element.options[i].value ) ); 
	}
	
	if( this.divBySelect.selectedOption().value == CounterView.DIV_VALUE ) {
		var dv = this.divValueSelect.selectedOption().value;
	} else {
		var dv = null;
	}
	
	if( this.divBySelect.selectedOption().value != CounterView.DIV_NONE ) {
		var l = this.divLimitInput.element.value;
		if( this.roundCheck.element.checked ) {
			var r = this.roundInput.element.value;
		} else {
			var r = -1;
		}
	} else {
		var l = 0;
		var r = -1;
	}
	if( this.rowsCheck.element.checked ) {
		var rw = parseInt( this.rowsInput.element.value );
	} else {
		var rw = 0;
	}
	if( this.percentRoundCheck.element.checked ) {
		var pr = parseInt( this.percentRoundInput.element.value );
	} else {
		var pr = -1;
	}
	var ev = this.entityView.inputClone();
	return new SubCounterView( null, this.titleInput.element.value, this.numberTitle, this.counterSelect.selectedOption().value, pt, this.divBySelect.selectedOption.value, dv, l, r, null, null, rw, pr, ev );
}


ArdeClass.extend( NewSubCounterView, SubCounterView );
ArdeClass.extend( NewSubCounterView, NewCounterView );

function EntitySelect() {
	this.ArdeComponent( 'span' );
}
ArdeClass.extend( EntitySelect, ArdeComponent );

