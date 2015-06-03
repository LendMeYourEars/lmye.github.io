
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
    
function LatestPage( perPage, requestPerSession, requestPerSingleSession, priItems, secItems, filters, vTypes, viscs, start, more, onlineVisitors, defVtSelection, singleSessionId ) {
	this.perPage = perPage;
	
	this.requestPerSession = requestPerSession;
	this.requestPerSingleSession = requestPerSingleSession;
	
	this.priItems = priItems;
	this.secItems = secItems;
	
	this.filters = filters;
	
	this.vTypes = vTypes;
	
	this.defVtSelection = defVtSelection;
	
	this.visitorTypes = {};
	for( var i in this.vTypes ) {
		this.visitorTypes[ this.vTypes[i].id ] = this.vTypes[i];
	}
	
	this.viscs = viscs;


	if( ardeMembersCount( this.viscs ) == 0 ) {
		for( var i in this.vTypes ) {
			this.viscs[ vTypes[ i ].id ] = true;
		}
	}
	
	this.start = start;
	this.more = more;

	this.onlineVisitors = onlineVisitors;
	
	this.singleSessionId = singleSessionId;
}

LatestPage.prototype.initEntityV = function ( entityV ) {
	entityV.optionMakers.push( function ( entityV, pane ) { latestPage.makeEntityOptions( entityV, pane ); } );
	entityV.makeOptionsButton();
};

LatestPage.prototype.makeEntityOptions = function ( entityV, pane ) {
	for( var i in this.filters ) {
		if( this.filters[i].entityV.entityId == entityV.entityId ) {
			pane.append( ardeE( 'p' ).append( ardeT( ardeLocale.text('already have filter of the same type') ) ) );
			pane.append( new LatestFilterOption( entityV, false ) );
			return;
		}
	}
	if( this.filters.length ) {
		pane.append( new LatestFilterOption( entityV, true ) );
		pane.append( new LatestFilterOption( entityV, false ) );
	} else {
		pane.append( new LatestFilterOption( entityV, null ) );
	} 
};

function LatestFilterOption( entityV, add ) {
	this.add = add;
	this.EntityVOption( twatchUrl+'img/filter'+(add?'_add':'')+'.gif', ' '+ardeLocale.text( 'Filter Latest Visitors' )+( add==null ? '' : ( add ? ' ('+ardeLocale.text('add')+')' : ' ('+ardeLocale.text('reset')+')' ) ) );
	this.entityV = entityV;
}
LatestFilterOption.prototype.clicked = function () {
	if( this.entityV.id == 0 ) return alert( ardeLocale.text( 'Sorry, You don\'t have permission' ) );
	var f = new ArdeAppender( '_' );
	if( this.add ) {
		for( var i in latestPage.filters ) {
			if( typeof latestPage.filters[i].entityV == 'number' ) {
				f.append( latestPage.filters[i].entityId+'_'+(latestPage.filters[i].entityV == EntityVExtra.Exists?'e':'de'));
			} else {
				f.append( latestPage.filters[i].entityId+'_'+latestPage.filters[i].entityV.id );
			}
		}
	}
	f.append( this.entityV.entityId+'_'+this.entityV.id );
	ArdeUrlWriter.getCurrent().setParam( 'f', f.s ).removeParam( 'start' ).go();
}
ArdeClass.extend( LatestFilterOption, EntityVOption );

LatestPage.prototype.insertHeader = function () {
	var headerDiv = new ArdeComponent( 'div' ).setLocaleFloat( 'right' ).style( 'marginLeft', ardeLocale.rightToLeft?'10px':'0px' );
	headerDiv.insert();
	
	
	var tbody = ardeE( 'tbody' ).appendTo( new ArdeTable().cls( 'cute_canvas' ).style( 'marginTop', '10px' ).appendTo( headerDiv ) );
	var td = ardeE( 'td' ).cls( 'pad_full' ).appendTo( ardeE( 'tr' ).appendTo( tbody ) );
	
	this.clearButton = new ArdeButton( ardeLocale.text( 'Clear' ) ).cls( 'passive' );
	this.clearButton.element.onclick = function () {
		for( var i in self.vTypeChecks ) {
			self.vTypeChecks[i].element.checked = false;
		}
	}
	td.append( this.clearButton ).append( ardeT( ' ' ) );
	
	this.vTypeChecks = [];

	for( var i in this.vTypes ) {
		var label = ardeE( 'label' ).appendTo( td );
		this.vTypeChecks[ i ] = new ArdeCheckBox( typeof this.viscs[ this.vTypes[i].id ] != 'undefined' );
		this.vTypeChecks[ i ].value = this.vTypes[i].id;
		label.append( this.vTypeChecks[ i ] );
		
		var vTypeIcon = new ArdeImg( twatchUrl+'img/vtype_s/'+this.vTypes[ i ].id+'.gif', null, 22, 22 );
		vTypeIcon.style( 'verticalAlign', '-4px' );
		
		label.append( vTypeIcon ).append( ardeT( this.vTypes[i].name + ardeNbsp( 3 ) ) );
	}
	this.updateButton = new ArdeButton( ardeLocale.text( 'Update' ) );
	var self = this;
	this.updateButton.element.onclick = function () { self.updateVTypeClicked(); };
	td.append( ardeT( ' ' ) ).append( this.updateButton );
	
	tbody = ardeE( 'tbody' ).appendTo( new ArdeTable().style( 'width', '100%' ).cls( 'cute_canvas' ).style( 'marginTop', '5px' ).appendTo( headerDiv ) );
	td = ardeE( 'td' ).cls( 'pad_full' ).style( 'margin', '0px' ).appendTo( ardeE( 'tr' ).appendTo( tbody ) );
	this.entitySelect = new ArdeSelect();
	for( var i in searchableEntities ) {
		ardeE( 'option' ).append( ardeT( entities[i].name ) ).attr( 'value', i ).appendTo( this.entitySelect );
	}
	this.entityVSelect = new ArdeActiveSelect( null, 10 );
	this.entityVSelect.addExtra( new EntityVExtra( ardeLocale.text( 'Exists' ), EntityVExtra.Exists ) );
	this.entityVSelect.addExtra( new EntityVExtra( ardeLocale.text( 'Doesn\'t Exist' ), EntityVExtra.DoesntExist ) );
	this.searchButton = new ArdeButton( ardeLocale.text( 'Search' ) );
	
	td.append( this.entitySelect ).append( this.entityVSelect ).append( this.searchButton );
	
	if( this.filters.length ) {
		var td = ardeE( 'td' ).cls( 'block pad_full' ).style( 'margin', '0px' ).appendTo( ardeE( 'tr' ).appendTo( ardeE( 'tbody' ).appendTo( new ArdeTable().cls( 'margin_std' ).insert() ) ) );
		td.append( ardeT( ardeLocale.text( 'Visitors' )+' ' ) );
		for( var i in this.filters ) {
			
			if( i != 0 ) td.append( ardeT( ' ' ) );
			
			if( typeof this.filters[i].entityV == 'number' ) {
				td.append( ardeT( this.filters[i].visitorTitle ) );
			} else {
				td.append( ardeTextComp( this.filters[i].visitorTitle, { 'value': this.filters[i].entityV } ) );
			}

		}
		this.removeFilterButton = new ArdeButton( ardeLocale.text( 'Remove Filter' ) );
		this.removeFilterButton.element.onclick = function () { self.removeFilterClicked(); };
		td.append( ardeT( ardeNbsp( 3 ) ) ).append( this.removeFilterButton );
	}
	
	if( this.singleSessionId === null ) {
		var tr = ardeE( 'tr' ).appendTo( ardeE( 'tbody' ).appendTo( new ArdeTable().cls( 'margin_std' ).insert() ) );
		var td = ardeE( 'td' ).cls( 'special_sub_block pad_full' ).style( 'margin', '0px' ).appendTo( tr );
		td.append( ardeT( ardeLocale.text( 'Online' )+': ' ) ).append( ardeE( 'b' ).append( ardeT( ardeLocale.number( this.onlineVisitors ) ) ) );
	}
	
	
	this.entityVSelect.setStandardCallbacks( this, 'entityValues' );
	
	var self = this;
	
	this.searchButton.element.onclick =  function () { self.searchClicked() };
	
	this.entitySelect.element.onchange = function () {
		self.entityVSelect.retract();
		self.entityVSelect.setValue( null );
	};
	
	new ArdeComponent( 'div' ).cls( 'clear' ).insert();
}

function EntityVExtra( text, id ) {
	this.text = text;
	this.id = id;
	this.ArdeComponent( 'div' );
	this.style( 'color', '#080' );
	this.append( ardeT( text ) );
}

EntityVExtra.Exists = 1;
EntityVExtra.DoesntExist = 2;

EntityVExtra.prototype.setMouseOverHighlight = function( mouseOverHighlight ) {
	if( mouseOverHighlight ) {
		this.element.style.background = '#f88';
		this.element.style.color = '#080';
	} else {
		this.element.style.background = '#fff';
		this.element.style.color = '#080';
	}
	return this;
};

EntityVExtra.prototype.setSelectedHighlight = function( selectedHighlight ) {
	if( selectedHighlight ) {
		this.element.style.background = '#a00';
		this.element.style.color = '#fff';
	} else {
		this.element.style.background = '#fff';
		this.element.style.color = '#080';
	}
	return this;
};
ArdeClass.extend( EntityVExtra, ArdeItem );


LatestPage.prototype.searchClicked = function() {
	var selected = this.entityVSelect.getSelected();
	if( selected === null ) alert( ardeLocale.text( 'Please select something to search for' ) );
	if( selected instanceof EntityVExtra ) {
		var f = this.entitySelect.selectedOption().value+'_'+( selected.id == EntityVExtra.Exists ? 'e' : 'de' );
	} else {
		var f = this.entitySelect.selectedOption().value+'_'+selected.id;
	}
	ArdeUrlWriter.getCurrent().removeParam( 's' ).setParam( 'f', f ).removeParam( 'start' ).go();
};

LatestPage.prototype.entityValuesRequested = function( offset, count, beginWith ) {
	if( beginWith == '' ) b = '';
	else b = '&b='+ardeEscape( beginWith );

	this.entityVSelect.requester.request( twatchFullUrl( twatchUrl+'rec/rec_entity_values.php' ),
		'a=get_values&i='+this.entitySelect.selectedOption().value+'&o='+offset+'&c='+count+b+'&w='+websiteId,
		ardeXmlObjectListClass( EntityV, 'entity_v', true, false )
	);
};

LatestPage.prototype.entityValuesReceived = function( result ) {
	this.entityVSelect.resultsReceived( result.a, result.more );
};

LatestPage.prototype.insertFooter = function () {
	var div = new ArdeComponent( 'div' ).style( 'marginTop', '10px' ).insert();
	var self = this;
	if( this.more ) {
		this.nextButton = new ArdeButton( ardeLocale.text( 'next {number}', { 'number': this.perPage } )+' >>' );
		if( ardeLocale.rightToLeft ) this.nextButton.style( 'marginLeft', '10px' );
		this.nextButton.element.onclick = function () { self.nextClicked(); };
		div.append( new ArdeComponent( 'div' ).setLocaleFloat( 'right' ).append( this.nextButton ) );
	}
	if( this.start != 0 ) {
		this.prevButton = new ArdeButton( '<< '+ardeLocale.text( 'prev {number}', { 'number': this.perPage } ) );
		if( !ardeLocale.rightToLeft ) this.prevButton.style( 'marginLeft', '10px' );
		this.prevButton.element.onclick = function () { self.prevClicked(); };
		div.append( this.prevButton );
	}
	
	div.append( new ArdeComponent( 'div' ).cls( 'clear' ) );
}

LatestPage.prototype.updateVTypeClicked = function () {
	url = ArdeUrlWriter.getCurrent();
	var def = true;
	var vs = new ArdeAppender( '_' );
	for( var i in this.vTypeChecks ) {
		if( this.vTypeChecks[i].element.checked ) {
			vs.append( this.vTypeChecks[i].value );
			if( typeof this.defVtSelection[ this.vTypeChecks[i].value ] == 'undefined' ) {
				def = false;	
			}
		}
	}
	if( !vs.c ) {
		return alert( 'select at least one visitor type' );
	}
	
	if( def && vs.c == ardeMembersCount( this.defVtSelection ) ) url.removeParam( 'vt' );
	else url.setParam( 'vt', vs.s );
	
	url.removeParam( 'start' ).removeParam( 's' );
	window.location = url.getUrl();
};

LatestPage.prototype.nextClicked = function () {
	ArdeUrlWriter.getCurrent().setParam( 'start', this.start + this.perPage ).go();
};

LatestPage.prototype.prevClicked = function () {
	var newStart = this.start - this.perPage;
	if( newStart < 0 ) newStart = 0;
	ArdeUrlWriter.getCurrent().setParam( 'start', newStart, 0 ).go();
};

LatestPage.prototype.removeFilterClicked = function () {
	ArdeUrlWriter.getCurrent().removeParam( 'f' ).removeParam( 'start' ).go();
}

LatestPage.prototype.noSessions = function () {
	new ArdeComponent( 'div' ).style( 'fontSize', '2em' ).style( 'textAlign', 'center' ).append( ardeT( ardeLocale.text( 'No Visitors' ) ) ).insert();
}


function LatestPageItem( title, notFound ) {
	this.title=  title;
	this.notFound = notFound;
}

function Session(  id, time, ip, sCookie, pCookie, visitorTypeId, priItems, secItems, requests, offset, more ) {
	this.id = id;
	this.time = time;
	this.ip = ip;
	this.sCookie = sCookie;
	this.pCookie = pCookie;
	this.visitorTypeId = visitorTypeId;
	this.priItems = priItems;
	this.secItems = secItems;
	this.requests = requests;
	this.offset = offset;
	this.more = more;
	
	this.ArdeComponent( 'div' );
	this.cls( 'block' );
	
	var div = new ArdeComponent( 'div' ).style('textAlign', 'right').setLocaleFloat( 'right' ).appendTo( this );
	
	var tb = ardeE( 'tbody' ).appendTo( new ArdeTable().style( 'marginTop', '10px' ).setCellSpacing( '5' ).appendTo( div ) );
	
	if( this.pCookie != null ) {
		stick = new ArdeImg( twatchUrl+'img/stick.gif', null, 20, 13 );
		latestPage.initEntityV( this.pCookie );
		var pCookieTd = ardeE( 'td' ).cls( 'sub_block alt pad_full').style( 'padding', '8px 10px' ).style( 'textAlign', ardeLocale.left() ).append( this.pCookie ).append( stick ).append( ardeT( ' '+ardeLocale.text( 'VID' ) ) );
		if( ardeBro.opera ) {
			pCookieTd.style( 'whiteSpace', 'nowrap' );
		}
		tb.append( ardeE( 'tr' ).append( pCookieTd ) );
	}
	if( this.sCookie != null ) {
		var stick = new ArdeImg( twatchUrl+'img/stick.gif', null, 20, 13 );
		var sCookieTd = ardeE( 'td' ).cls( 'sub_block pad_full').style( 'padding', '8px 10px' ).style( 'textAlign', ardeLocale.right() ).append( this.sCookie ).append( stick ).append( ardeT( ' '+ardeLocale.text( 'SID' ) ) );
		if( ardeBro.opera ) {
			sCookieTd.style( 'whiteSpace', 'nowrap' );
		}
		tb.append( ardeE( 'tr' ).append( sCookieTd ) );
	}
	
	
	tb = ardeE( 'tbody' ).appendTo( new ArdeTable().cls( 'margin_std' ).attr( 'cellspacing', '2' ).appendTo( this ) );
	tr = ardeE( 'tr' ).appendTo( tb );
	tr.append( ardeE( 'td' ).cls( 'cute_fat alt' ).append( new ArdeImg( twatchUrl+'img/vtype/'+this.visitorTypeId+'.gif', latestPage.visitorTypes[ this.visitorTypeId ].name, 45, 45 ) ) );
	
	var td = ardeE( 'td' ).cls( 'cute_fat alt pad_std' ).appendTo( tr );
	
	td.append(  new ArdeComponent( 'span' ).setDisplayMode( 'inline-block' ).cls( 'block alt pad_full' ).append( this.time ) );
	
	if( ardeBro.opera ) {
		td.style( 'whiteSpace', 'nowrap' );
	}
	var self = this;
	
	var makeItem = function ( item, itemValue ) {
		var span = new ArdeComponent( 'span' ).setDisplayMode( 'inline-block' ).cls( 'sub_block margin_half pad_full' );
		if( item.title !== null ) span.append( ardeE( 'span' ).cls( 'title' ).append( ardeT( item.title+': ' ) ) );
		if( itemValue === null ) {
			if( item.notFound === null ) return null;
			span.append( ardeT( item.notFound ) );
		} else {
			latestPage.initEntityV( itemValue );
			span.append( itemValue );
		}
		return span;
	}
	
	for( var i in latestPage.priItems ) {
		if( i == 1 ) td.append( ardeE( 'br' ) );
		var itemComp = makeItem( latestPage.priItems[i], this.priItems[i] );
		if( itemComp !== null ) td.append( itemComp );
	}
	
	td = new ArdeTd().cls( 'cute_fat pad_half' ).setColSpan( '2' ).appendTo( ardeE( 'tr' ).appendTo( tb ) );
	if( ardeBro.opera ) {
		td.style( 'whiteSpace', 'nowrap' );
	}
	for( var i in latestPage.secItems ) {
		var itemComp = makeItem( latestPage.secItems[i], this.secItems[i] );
		if( itemComp !== null ) td.append( itemComp );
	}
	
	for( var i = 1; i < this.requests.length; ++i ) {
		this.requests[ i - 1 ].setTime( this.requests[i].time.id - this.requests[i-1].time.id );
	}
	
	if( this.more != 0 ) {
		var url = ArdeUrlWriter.getCurrent();
		url.removeParam( 'f' );
		url.removeParam( 'vt' );
		url.setParam( 's', this.id );
		url.setParam( 'rs', latestPage.singleSessionId !== null ? this.offset + latestPage.requestPerSingleSession : 0, 0 );
		div = ardeE( 'div' ).cls( 'pad_full' ).appendTo( this );
		var text = '<< ' + ardeLocale.text( '{number} older request' + (this.more==1?'':'s'), { 'number' : ardeLocale.number( this.more ) } );
		div.append( ardeE( 'a' ).cls( 'entityv' ).attr( 'href', url.getUrl() ).append( ardeT( text ) ) );
	}
	
	div = ardeE( 'div' ).appendTo( this );
	
	
	
	for( var i in this.requests ) {
		var img = new ArdeImg( twatchUrl+'img/next'+(ardeLocale.rightToLeft?'_rtl':'')+'.gif', null, 24, 48 ).style( 'verticalAlign', ardeBro.ie>=8 || ardeBro.opera?'-25px':'5px' );
		if( ardeLocale.rightToLeft ) {
			img.style( 'marginLeft', '7px' );
		} else {
			img.style( 'marginLeft', '15px' ).style( 'marginRight', '-8px' );
		}
		div.append( img );
		
		div.append( this.requests[i] );
	}
	
	if( this.offset != 0 ) {
		var newOffset = this.offset - latestPage.requestPerSingleSession;
		if( newOffset < 0 ) newOffset = 0;
		var url = ArdeUrlWriter.getCurrent();
		url.removeParam( 'f' );
		url.removeParam( 'vt' );
		url.setParam( 's', this.id );
		url.setParam( 'rs', newOffset, 0 );
		div = ardeE( 'div' ).cls( 'pad_full' ).appendTo( this );

		var text = ardeLocale.text( '{number} newer request'+(this.offset==1?'':'s'), { 'number' : ardeLocale.number( this.offset ) } ) + ' >>';
		div.append( ardeE( 'a' ).cls( 'entityv' ).attr( 'href', url.getUrl() ).append( ardeT( text ) ) );
	}
}
ArdeClass.extend( Session, ArdeComponent );

function Request( id, time, page, data ) {
	this.id = id;
	this.page = page;
	this.time = time;
	this.data = data;
	
	this.ArdeComponent( 'span' );
	this.setDisplayMode( 'inline-block' );
	
	this.cls( 'block alt' );
	
	var div = ardeE( 'div' ).cls( 'margin_std' ).appendTo( this );
	
	var tb = ardeE( 'tbody' ).appendTo( new ArdeTable().appendTo( div ) );
	
	if( this.page !== null ) {
		var td = new ArdeTd().setColSpan( 2 ).appendTo( ardeE( 'tr' ).appendTo( tb ) ).style( 'paddingBottom', '3px' );
		latestPage.initEntityV( this.page );
		td.append( this.page );
	}
	
	this.tr = ardeE( 'tr' ).appendTo( tb );
	td = ardeE( 'td' ).cls( 'alt_text' ).appendTo( this.tr );
	this.timeText = new ardeT( ardeNbsp() );
	td.append( this.timeText );
	
	td = ardeE( 'td' ).style( 'paddingLeft', '10px' ).style( 'width', '16px' ).style( 'textAlign', ardeLocale.right() ).appendTo( this.tr );
	if( this.data.length ) {
		var bh = new ArdeComponent( 'span' ).setDisplayMode( 'inline-block' ).appendTo( td );
		td.append( bh );
		this.dataHolder = new HangingPane( new ArdeImgButton( twatchUrl+'img/data.gif', null, 16, 16 ) );
		this.dataHolder.style( 'textAlign', ardeLocale.left() );
		
		bh.append( this.dataHolder );
		this.dataButton = new ArdeImgButton( twatchUrl+'img/data.gif', 'request data', 16, 16 ).style( 'verticalAlign', '-4px');
		var self = this;
		for( var i in this.data ) {
			latestPage.initEntityV( this.data[i] );
		}
		this.dataButton.element.onclick = function () {
			for( var i in self.data ) {
				self.dataHolder.pane.append( ardeE( 'p' ).append( ardeT( entities[ self.data[i].entityId ].name+': ' ) ).append( self.data[i] ) );
			}
			self.dataHolder.setDisplay( true );
			
		}
		bh.append( this.dataButton );
	} else {
		td.append( new ArdeImg( baseUrl+'img/dummy.gif', null, 16, 16 ) );
	}
}

Request.prototype.setTime = function ( time ) {
	this.timeText.n.nodeValue = ardeSecondsString( time );
}
ArdeClass.extend( Request, ArdeComponent );

function Filter( entityId, entityV, visitorTitle ) {
	this.entityId = entityId;
	this.entityV = entityV;
	this.visitorTitle = visitorTitle;
}
