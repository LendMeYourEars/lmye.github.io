
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



function StatsPage( id, width, pTypeNames, periods ) {
	this.id = id;
	this.width = width;
	this.pTypeNames = pTypeNames;
	this.periods = periods;
}

function StatsDateSelect( minYear, maxYear, sYear, sMonth, sDay ) {
	this.DateSelect( minYear, maxYear, sYear, sMonth, sDay );
	

	this.todayButton = new ArdeButton( ardeLocale.text( 'Today' ) );
	var self = this;
	this.todayButton.element.onclick = function () { self.todayClicked(); };
	
	this.goButton = new ArdeButton( ardeLocale.text( 'Go' ) );
	this.goButton.element.onclick = function () { self.goClicked(); };
	this.insertFirstChild( ardeT( ' ' ) ).insertFirstChild( this.todayButton );
	this.append( this.goButton );
}

StatsDateSelect.prototype.todayClicked = function () {
	var url = new ArdeUrlWriter( window.location.toString() );
	url.removeParam( 'y' );
	url.removeParam( 'm' );
	url.removeParam( 'd' );
	window.location = url.getUrl();
};

StatsDateSelect.prototype.goClicked = function () {
	var url = new ArdeUrlWriter( window.location.toString() );
	url.setParam( 'y', this.yearSelect.selectedOption().value );
	url.setParam( 'm', this.monthSelect.selectedOption().value );
	url.setParam( 'd', this.daySelect.selectedOption().value );
	window.location = url.getUrl();
};

ArdeClass.extend( StatsDateSelect, DateSelect );

function SinglesTable() {
	this.ArdeComponent( 'div' );
	this.cls( 'margin_double' );
	var t = new ArdeTable().style( 'width', '100%' ).setCellSpacing( '2' ).cls( 'cute' ).appendTo( this );
	var tr = ardeE( 'tr' ).appendTo( ardeE( 'thead' ).appendTo( t ) );
	
	var width = 1.5;
	var spacerWidth = -1;
	for( var i = 0; i < statsPage.periods.ardeLength(); ++i ) {
		periodType = statsPage.periods.ardeKeyAt( i );

		if( periodType == Period.ALL ) width += 1;
		else width += statsPage.width;
		++spacerWidth;
	}

	tr.append( ardeE( 'td' ).cls( 'dead' ).style( 'width', (1.5*((100-spacerWidth)/width))+'%' ).append( ardeT( ' ' ) ) );
	
	var pt = 0;

	for( var j = 0; j < statsPage.periods.ardeLength(); ++j ) {
		periodType = statsPage.periods.ardeKeyAt( j );
		if( pt ) tr.append( ardeE( 'td' ).cls( 'dead spacer' ).style( 'width', '1%' ) );
		for( var i in statsPage.periods[ periodType ] ) {
			var td = ardeE( 'td' ).style( 'width', ((100-spacerWidth)/width)+'%' ).append( ardeT( statsPage.periods[ periodType ][i].name ) );
			if( statsPage.periods[ periodType ][i].highlighted ) td.cls( 'alt' );
			tr.append( td );
		}
		++pt;
		
	}
	this.ArdeComponentList( new ArdeComponent( 'tbody' ).appendTo( t ) );
}

ArdeClass.extend( SinglesTable, ArdeComponent );
ArdeClass.extend( SinglesTable, ArdeComponentList );

function SingleCView( title, numberTitle, counterId, values, graph, periodTypes ) {
	this.title = title;
	this.numberTitle = numberTitle;
	this.counterId = counterId;
	this.values = values;
	this.graph = graph;
	this.periodTypes = {};
	for( var i in periodTypes ) this.periodTypes[ periodTypes[i] ] = true;
	
	this.ArdeComponent( 'tr' );
	
	var td = ardeE( 'td' ).cls( 'head' ).appendTo( this );
	if( this.graph ) {
		var span = new ArdeComponent( 'span' ).setDisplayMode( 'inline-block' ).style( 'textAlign', ardeLocale.left() ).appendTo( td );
		this.optionsHolder = new HangingPane( new ArdeImgButton( baseUrl+'img/options.gif', 'options', 16, 16 ) );
		this.optionsHolder.style( 'marginTop', '0px' );
		span.append( this.optionsHolder );
		this.optionsButton = new ArdeImgButton( baseUrl+'img/options2.gif', 'options', 16, 16 );
		this.optionsButton.style( 'verticalAlign', '-4px' );
		var self = this;
		this.optionsButton.element.onclick = function () { self.optionsClicked(); };
		span.append( this.optionsButton  );
	}
	td.append( ardeT( (this.graph?' ':'')+this.title ) );
	var pos = 0;
	var pt = 0;
	for( var j = 0; j < statsPage.periods.ardeLength(); ++j ) {
		periodType = statsPage.periods.ardeKeyAt( j );
		if( pt ) this.append( ardeE( 'td' ).cls( 'dead spacer' ) );
		for( var i in statsPage.periods[ periodType ] ) {
			if( typeof this.periodTypes[ periodType ] == 'undefined' ) {
				var td = ardeE( 'td' ).cls( 'dead' ).append( ardeT( ardeNbsp() ) );
			} else {
				valueStr = this.values[ pos ] == null ? ardeLocale.text( 'NA' ) : this.values[ pos ];
				var td = new ArdeComponent( 'td' ).append( ardeT( valueStr ) );
				if( statsPage.periods[ periodType ][i].highlighted ) td.cls( 'alt' );
				if( this.values[ pos ] == null ) td.addClass( 'na' );
			}
			this.append( td );
			++pos;	
		}
		++pt;
	}
}
SingleCView.prototype.optionsClicked = function () {
	this.optionsHolder.setDisplay( true );
	this.optionsHolder.pane.append( new GraphOption( this.counterId, 0, 0, this.title, false, this.numberTitle, false ) );
	this.optionsHolder.pane.append( new GraphOption( this.counterId, 0, 0, this.title, false, this.numberTitle, true ) );
}
ArdeClass.extend( SingleCView, ArdeComponent );

function GraphView( width, height, barWidth, xStart, xEnd, labels, names ) {
	this.width = width;
	this.height = height;
	this.barWidth = barWidth;
	this.xStart = xStart;
	this.xEnd = xEnd;
	this.labels = labels;
	this.names = names;
}

function ListCView( id, title, numberTitle, counterId, groupId, limit, graphView, subs, periodTypes ) {
	this.id = id;
	this.title = title;
	this.numberTitle = numberTitle;
	this.counterId = counterId;
	this.groupId = groupId;
	this.limit = limit;
	this.graphView = graphView;
	this.subs = subs;
	this.periodTypes = {};
	for( var i in periodTypes ) this.periodTypes[ periodTypes[i] ] = true;
	
	this.parent = null;
	
	this.buttons = [];
	this.ArdeComponent( 'div' );
	this.cls( 'margin_double' );
	var d = ardeE( 'div' ).appendTo( this );
	this.buttonsDiv = new ArdeComponent( 'div' ).setLocaleFloat( 'right' ).appendTo( d );
	d.append( ardeE( 'h3' ).append( ardeT( this.title ) ) );
	d.append( ardeE( 'div' ).cls( 'clear' ) );
	
	this.ArdeComponentList( ardeE( 'div' ).appendTo( this ) );
	
}

ListCView.prototype.clone = function( groupId ) {
	return new ListCView( this.id, this.title, this.numberTitle, this.counterId, groupId, this.limit, this.graphView, this.subs );
}

ListCView.prototype.makeEntityOptions = function( entityV, pane, period ) {
	for( var i in this.subs ) {
		if( typeof this.subs[i].periodTypes[ period.type ] != 'undefined' ) {
			pane.append( new SubOption( this, this.subs[i], entityV, period ) );
		}
	}
	var	title = entityV.getStr();
	var numberTitle = this.numberTitle.replace( /\{value\}/, title );
	pane.append( new GraphOption( this.counterId, this.groupId, entityV.id, title, entityV.forceLtr, numberTitle, false ) );
	pane.append( new GraphOption( this.counterId, this.groupId, entityV.id, title, entityV.forceLtr, numberTitle, true ) );
	pane.append( new LatestFilterOption( entityV ) );
}



function LatestFilterOption( entityV ) {
	this.EntityVOption( twatchUrl+'img/filter.gif', ' '+ardeLocale.text( 'Search Latest Visitors' ) );
	this.entityV = entityV;
}
LatestFilterOption.prototype.clicked = function () {
	var url = new ArdeUrlWriter( twatchUrl+'latest.php' );
	url.setParam( 'lang', ardeLocale.id, ardeLocale.defaultId );
	url.setParam( 'f', this.entityV.entityId+'_'+this.entityV.id );
	url.setParam( 'website', websiteId, defaultWebsiteId );
	url.setParam( 'profile', twatchProfile, 'default' );
	window.location = url.getUrl();
}
ArdeClass.extend( LatestFilterOption, EntityVOption );

function SubOption( cView, subView, entityV, period ) {
	this.cView = cView;
	this.subView = subView;
	this.entityV = entityV;
	this.period = period;
	
	this.EntityVOption( twatchUrl+'img/details.gif', entityV.getStr() );
	this.paneHolder = new HangingPane( new ArdeImgButton( twatchUrl+'img/details.gif', null, 16, 16 ) );
	this.paneHolder.style( 'marginTop', '0px' );
	this.insertFirstChild( this.paneHolder );
	this.requester = new ArdeRequestIcon();
	var self = this;
	this.requester.resultReceived = function ( result ) { self.received( result ); };
	this.append( this.requester );
}
SubOption.prototype.getTextElement = function( text ) {
	if( this.forceLtr ) {
		t = ardeTextComp( this.subView.title, { 'group': ardeE( 'span' ).style( 'direction', 'ltr' ).style( 'unicodeBidi', 'bidi-override' ).append( ardeT( text ) ) } );
		
		return ardeE( 'span' ).append( t );
	}
	return ardeT( ardeLocale.text( this.subView.title, { 'group': text } ) );

}
SubOption.prototype.clicked = function () {
	var q = 'w='+websiteId;
	q += '&si='+statsPage.id;
	q += '&vi='+this.cView.id;
	q += '&svi='+this.subView.id;
	q += '&gi='+this.entityV.id;
	q += '&pt='+this.period.type;
	q += '&pc='+this.period.code;
	q += '&l='+( this.subView.limit );
	this.requester.request( twatchFullUrl( 'rec/rec_counter_views.php' ), q, CounterRes );
}

SubOption.prototype.received = function ( counterRes ) {
	var subView = this.subView.clone( this.entityV.id );
	subView.parent = this.cView;
	counterRes.table.style( 'width', '400px' );
	counterRes.finalize( subView, this.period );
	this.paneHolder.setDisplay( true );

	this.paneHolder.pane.append( ardeE( 'div' ).cls( 'margin_std' ).append( counterRes ) );
	
}
ArdeClass.extend( SubOption, EntityVOption );

function GraphOption( counterId, groupId, entityVId, title, forceLtr, numberTitle, add ) {
	this.add = add;
	this.counterId = counterId;
	this.groupId = groupId;
	this.entityVId = entityVId;
	this.title = title;
	this.forceLtr = forceLtr;
	this.numberTitle = numberTitle;
	this.EntityVOption( twatchUrl+'img/graph'+(add?'_add':'')+'.gif', this.title );
	this.paneHolder = new HangingPane( new ArdeImgButton( twatchUrl+'img/graph'+(add?'_add':'')+'.gif', null, 16, 16 ) );
	this.paneHolder.style( 'marginTop', '0px' );
	
	this.insertFirstChild( this.paneHolder );
	this.requester = new ArdeRequestIcon();
	var self = this;
	this.requester.resultReceived = function ( result ) { self.received( result ); };
	this.append( this.requester );
	
	
}

GraphOption.prototype.getTextElement = function( text ) {
	if( this.forceLtr ) {
		t = ardeTextComp( ardeLocale.text( '{something} history' ), { 'something': ardeE( 'span' ).style( 'direction', 'ltr' ).style( 'unicodeBidi', 'bidi-override' ).append( ardeT( text ) ) } );
		
		return ardeE( 'span' ).append( t ).append( ardeT( ' ('+ardeLocale.text(this.add?'add to graph':'reset graph')+')' ) );
	}
	return ardeT( ardeLocale.text( '{something} history', { 'something': text } )+' ('+ardeLocale.text(this.add?'add to graph':'reset graph')+')' );

}

GraphOption.prototype.clicked = function () {
	if( this.paneHolder.isDisplaying() ) {
		this.paneHolder.setDisplay( false );
		this.paneHolder.pane.clear();
	} else {
		
		var id ='&evi='+this.entityVId;
		if( this.groupId != 0 ) var group = '&g='+this.groupId;
		else var group = '';
		var q = 'w='+websiteId+'&ci='+this.counterId+group+id;
		this.requester.request( twatchFullUrl( 'rec/rec_graph.php' ), q, ardeXmlObjectListClass( GraphData, 'gdata' ) );
	}
};

GraphOption.prototype.received = function ( result ) {
	this.paneHolder.setVisible( false );
	this.paneHolder.setDisplay( true );
	this.paneHolder.setOffset( 0 );
	if( ardeLocale.rightToLeft ) {
		var offset = this.paneHolder.absRight() - 30 - ardeScrollRight();
	} else {
		var offset = this.paneHolder.absLeft() - 30 - ardeScrollLeft();
	}
	this.paneHolder.setOffset( offset );
	this.paneHolder.setVisible( true );
	var graph = graphManager.addResult( result.a, this.numberTitle, !this.add, ardeClientWidth() - 80 );
	this.paneHolder.pane.append( ardeE( 'p' ).append( graph ) );
	graph.forceRedraw();
	
}
ArdeClass.extend( GraphOption, EntityVOption );

function GraphManager() {
	this.series = [];
	this.xEnd = 0;
	this.names = [];
}
GraphManager.seriesStyles = [
	 new ArdeGraphSeriesStyle( 0x459a45, 0x65de65, 0x007800 )
	,new ArdeGraphSeriesStyle( 0xccc077, 0xeee099, 0x888033 )
	,new ArdeGraphSeriesStyle( 0xaa0000, 0xff0000, 0x880000 )
];
GraphManager.prototype.addResult = function( a, numberTitle, reset, width ) {
	if( reset || this.series.length == 0 ) {
		this.series = [];
		this.xEnd = a.length - 1;
		for( var i in a ) {
			
			this.names.push( a[i].xName );
		}
	}
	var series = new ArdeGraphSeries( GraphManager.seriesStyles[ (this.series.length) % 3 ] );
	series.numberTitle = numberTitle;
	for( var i in a ) {
		if( a[i].value !== null ) {
			series.addData( new ArdeGraphSeriesData( i, a[i].value, a[i].span, a[i].note ) );
		}
	}
	this.series.push( series );
	var zooms = [ 20, 10, 5, 2 ];
	var graph = new ArdeGraph( 0, this.xEnd, {}, this.names, this.series, width, 200, zooms, true, true, true, true, 0xe8e7db, 0xffffff );
	return graph;
};

function GraphData( xName, value, span, note ) {
	this.xName = xName;
	this.value = value;
	this.span = span;
	this.note = note;
}
GraphData.fromXml = function ( element ) {
	var xName = ArdeXml.strContent( element );
	var value = ArdeXml.attribute( element, 'value', null );
	var span = ArdeXml.intAttribute( element, 'span', 1 );
	var note = ArdeXml.attribute( element, 'note', null );
	return new GraphData( xName, value, span, note );
}


ListCView.prototype.addItem = function ( counterResGroup ) {
	if( this.items.length ) counterResGroup.setDisplay( false );
	counterResGroup.cView = this;
	this.ArdeComponentList_addItem( counterResGroup );
	var index = this.items.length - 1;
	this.buttons[ index ] = new PTypeButton( statsPage.pTypeNames[ counterResGroup.pType ] );
	if( index == 0 ) {
		this.buttons[ index ].setSelectedHighlight( true );
	}
	var self = this;
	this.buttons[ index ].element.onclick = function () {
		for( var i in self.items ) {
			self.items[i].setDisplay( self.items[i].pType == counterResGroup.pType );
			self.buttons[i].setSelectedHighlight( i == index );
		}
	}
	this.buttonsDiv.append( ardeT( ' ' ) );
	this.buttonsDiv.append( this.buttons[ index ] );
}

ArdeClass.extend( ListCView, ArdeComponent );
ArdeClass.extend( ListCView, ArdeComponentList );

function PTypeButton( text ) {
	this.ArdeComponent( 'span' );
	this.setDisplayMode( 'inline-block' );
	this.cls( 'tab_button pad_full' );
	this.style( 'width', '100px' );
	this.setClickable( true );
	this.append( ardeT( text ) );
}
PTypeButton.prototype.setSelectedHighlight = function( selectedHighlight ) {
	if( selectedHighlight ) {
		this.addClass( 'highlight' );
	} else {
		this.removeClass( 'highlight' );
	}
	return this;
};

ArdeClass.extend( PTypeButton, ArdeComponent );

function CounterResGroup( pType ) {
	this.pType = pType;
	this.cView = null;
	
	this.ress = [];
	
	this.ArdeComponent( 'div' );
	var t = new ArdeTable().attr( 'cellspacing', '5' ).cls( 'cute_canvas' ).style( 'width', '100%' ).appendTo( this );
	

	this.tr = new ArdeComponent( 'tr' ).appendTo( ardeE( 'tbody' ).appendTo( t ) );
	this.ArdeComponentList( this.tr );
}

CounterResGroup.prototype.addItem = function ( counterRes ) {
	counterRes.finalize( this.cView, statsPage.periods[ this.pType ][ this.items.length ] );
	var item = new ArdeComponent( 'td' ).style( 'verticalAlign', 'top' ).style( 'width', (100/statsPage.width)+'%' ).append( counterRes );
	this.ress.push( counterRes );
	this.ArdeComponentList_addItem( item );
}

CounterResGroup.prototype.setDisplay = function( display ) {
	this.ArdeComponent_setDisplay( display );

	for( var i in this.ress ) {
		this.ress[i].forceRedraw();
	}
}

ArdeClass.extend( CounterResGroup, ArdeComponent );
ArdeClass.extend( CounterResGroup, ArdeComponentList );

function CounterRes( more, rows ) {
	this.more = more;
	this.rows = rows;
	this.ArdeComponent( 'div' );
	if( this.more && this instanceof CounterRes ) {
		this.moreHolder = new ArdeComponent( 'div' ).appendTo( this );
		this.moreHolder.cls( 'more_holder' );
		this.moreHolder.style( 'position', 'absolute' );
		this.moreHolder.style( 'margin'+ardeLocale.Left(), '-6px' );
		this.moreHolder.style( 'marginTop', '-6px' );
		this.moreHolder.style( 'padding', '5px 5px' );
		this.moreHolder.setDisplay( false );
		
	}
	this.table = new ArdeTable().setCellSpacing( '2' ).cls( 'cute no_canvas' ).style( 'width', '100%' ).appendTo( this );
}

CounterRes.fromXml = function ( element ) {
	return CounterRes._fromXml( element, CounterRes );
}

CounterRes._fromXml = function ( element, constructor ) {
	var more = ArdeXml.boolAttribute( element, 'more' );
	var rowEs = new ArdeXmlElemIter( ArdeXml.element( element, 'rows' ), 'row' );
	var rows = [];
	while( rowEs.current ) {
		rows.push( CounterRow.fromXml( rowEs.current ) );
		rowEs.next();
	}
	return new constructor( more, rows );
}

CounterRes.prototype.finalize = function( cView, period ) {
	this.period = period;
	this.cView = cView;
	
	if( this.period.highlighted ) this.table.addClass( 'alt' );
	if( this.cView.parent === null ) {
		this.table.append( ardeE( 'thead' ).append( ardeE( 'tr' ).append( new ArdeTd().setColSpan( '3' ).append( ardeT( this.period.name ) ) ) ) );
	}
	this.tBody = ardeE( 'tbody' ).appendTo( this.table );
	
	var self = this;
	
	for( var i in this.rows ) {
		this.rows[i].entityV.optionMakers.push( function ( entityV, pane ) {
			self.cView.makeEntityOptions( entityV, pane, period );
		} );
		this.rows[i].entityV.makeOptionsButton();
		this.tBody.append( this.rows[i] );
	}
	
	if( this.more ) {
		this.moreButton = new ArdeRequestImgButton( baseUrl+'img/more.gif', ardeLocale.text( 'more' ), null, true );
		if( !ardeLocale.rightToLeft || !ardeBro.ie || ardeBro.ie >= 8 ) {
			this.moreButton.style( 'margin'+ardeLocale.Left(), '-25px' );
		}
		this.moreButton.setShowOk( false );
		var self = this;
		this.moreButton.setStandardCallbacks( this, 'more' );
		this.moreTd = new ArdeTd().style( 'textAlign', 'center' ).setColSpan( '3' ).appendTo( ardeE( 'tr' ).appendTo( this.tBody ) );
		this.moreTd.append( this.moreButton );
	}
};

CounterRes.prototype.forceRedraw = function () {};

CounterRes.prototype.moreClicked = function () {
	var q = 'w='+websiteId;
	q += '&si='+statsPage.id;
	if( this.cView.parent !== null ) {
		q += '&vi='+this.cView.parent.id;
		q += '&svi='+this.cView.id;
	} else {
		q += '&vi='+this.cView.id;
	}
	q += '&pt='+this.period.type;
	
	if( this.cView.groupId != 0 ) q += '&gi='+this.cView.groupId;
	q += '&pc='+this.period.code;
	q += '&l='+( this.getLimit() + this.cView.limit );


	this.moreButton.request( twatchFullUrl( 'rec/rec_counter_views.php' ), q, MoreCounterRes );
};

CounterRes.prototype.getLimit = function () {
	return this.cView.limit;
};

CounterRes.prototype.moreConfirmed = function ( moreRes ) {
	this.moreHolder.setWidth( this.getWidth() + 12 );
	this.moreHolder.setDisplay( true );
	moreRes.finalize( this.cView, this.period, this, this.getLimit() + this.cView.limit );
	this.moreHolder.append( moreRes );
}

CounterRes.prototype.lessClicked = function () {
	this.moreHolder.clear();
	this.moreHolder.setDisplay( false );
}

ArdeClass.extend( CounterRes, ArdeComponent );

function CounterResGraph( more, values ) {
	this.CounterRes( more, values );
}


CounterResGraph.prototype.finalize = function( cView, period ) {

	this.period = period;
	this.cView = cView;
	
	if( this.period.highlighted ) this.table.addClass( 'alt' );
	
	this.table.append( ardeE( 'thead' ).append( ardeE( 'tr' ).append( ardeE( 'td' ).append( ardeT( this.period.name ) ) ) ) );
	
	this.tBody = ardeE( 'tbody' ).appendTo( this.table );
	
	var self = this;
	
	var td = ardeE( 'td' ).style( 'textAlign', 'center' ).appendTo( ardeE( 'tr' ).appendTo( this.tBody ) );
	
	var view = this.cView.graphView;
	
	if( this.period.highlighted ) {
		var seriesStyle = new ArdeGraphSeriesStyle( 0x7d90e5, 0x94abe9, 0x4f74b1 );
		var indColor = 0xe3e5e6;
		var bgColor = 0xf7f9fa;
	} else {
		var seriesStyle = new ArdeGraphSeriesStyle( 0xc99b47, 0xdebb78, 0x9c7e42 );
		var indColor = 0xe8e7db;
		var bgColor = 0xf8f7eb;
	}
	var series = new ArdeGraphSeries( seriesStyle, this.cView.numberTitle );
	
	var x = view.xStart;
	for( var i in this.rows ) {
		if( this.rows[i] != null ) {
			series.addData( new ArdeGraphSeriesData( x, this.rows[i], 1 ) );
		}
		++x;
	}
	var zooms = [];
	zooms.push( view.barWidth );
	this.graph = new ArdeGraph( view.xStart, view.xEnd, view.labels, view.names, [series], view.width, view.height, zooms, false, true, false, false, indColor, bgColor );
	var sp = new ArdeImg( baseUrl+'img/dummy.gif', '', 1, view.height );
	td.append( this.graph ).append( sp );

}

CounterResGraph.prototype.forceRedraw = function() {
	this.graph.forceRedraw();
}

ArdeClass.extend( CounterResGraph, CounterRes );

function MoreCounterRes( more, rows ) {
	this.CounterRes( more, rows );
}

MoreCounterRes.prototype.getLimit = function () {
	return this.limit;
}

MoreCounterRes.prototype.moreConfirmed = function ( moreRes ) {
	moreRes.finalize( this.cView, this.period, this.parent, this.limit + this.cView.limit );
	this.replace( moreRes );
	
}
MoreCounterRes.prototype.finalize = function( cView, period, parent, limit ) {
	this.parent = parent;
	this.limit = limit;
	this.CounterRes_finalize( cView, period );
	this.lessButton = new ArdeImgButton( baseUrl+'img/less.gif', ardeLocale.text( 'close' ) );
	var self = this;
	this.lessButton.element.onclick = function () { self.parent.lessClicked(); };
	if( typeof this.moreTd == 'undefined' ) {
		this.moreTd = new ArdeTd().style( 'textAlign', 'center' ).setColSpan( 3 ).appendTo( ardeE( 'tr' ).appendTo( this.tBody ) );
	}
	this.moreTd.append( this.lessButton );
}

MoreCounterRes.fromXml = function ( element ) {
	return CounterRes._fromXml( element, MoreCounterRes );
}
ArdeClass.extend( MoreCounterRes, CounterRes );

function CounterRow( entityV, count, percent ) {
	this.entityV = entityV;
	this.count = count;
	this.percent = percent;
	
	this.ArdeComponent( 'tr' );
	this.append( ardeE( 'td' ).append( ardeT( this.count ) ) );
	this.append( ardeE( 'td' ).append( ardeT( this.percent+'%' ) ) );
	this.append( ardeE( 'td' ).style( 'width', '100%' ).append( this.entityV ) );
}
CounterRow.fromXml = function ( element ) {
	var count = ArdeXml.attribute( element, 'count' );
	var percent = ArdeXml.attribute( element, 'percent' );
	var entityV = InlineEntityV.fromXml( element );
	return new CounterRow( entityV, count, percent );
}

ArdeClass.extend( CounterRow, ArdeComponent );


