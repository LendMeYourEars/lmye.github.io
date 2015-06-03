
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
    
function Period( type, code, name, highlighted ) {
	this.type = type;
	this.code = code;
	this.name = name;
	this.highlighted = highlighted;
}

Period.ALL = 0;
Period.DAY = 1;
Period.MONTH = 2;

Period.typeStrings = {};
Period.typeStrings[ Period.ALL ] = 'All Time';
Period.typeStrings[ Period.DAY ] = 'Daily';
Period.typeStrings[ Period.MONTH ] = 'Monthly';

Period.numStrings = {};
Period.numStrings[ Period.ALL ] = '';
Period.numStrings[ Period.DAY ] = 'days';
Period.numStrings[ Period.MONTH ] = 'months';

Period.allPeriodTypes = [ Period.DAY, Period.MONTH, Period.ALL ];

function DateSelect( minYear, maxYear, sYear, sMonth, sDay, sHour, sMinute, sSecond ) {
	this.ArdeComponent( 'span' );

	this.minYear = minYear;
	this.maxYear = maxYear;
	this.yearSelect = new ArdeSelect();
	var option;
	for( var year = minYear; year <= maxYear; ++year ) {
		option = ardeE( 'option' ).attr( 'value', year ).append( ardeT( ardeLocale.number( year ) ) );
		if( year == sYear ) option.attr( 'selected', 'true' );
		this.yearSelect.append( option );
	}
	this.monthSelect = new ArdeSelect();
	for( var month = 1; month < 13; ++month ) {
		option = ardeE( 'option' ).attr( 'value', month ).append( ardeT( ardeLocale.text( DateSelect.monthNames[ month ] ) ) );
		if( month == sMonth ) option.attr( 'selected', 'true' );
		this.monthSelect.append( option );
		
	}
	this.daySelect = new ArdeSelect();
	for( var day = 1; day < 32; ++day ) {
		option = ardeE( 'option' ).attr( 'value', day ).append( ardeT( ardeLocale.number( day ) ) );
		if( day == sDay ) option.attr( 'selected', 'true' );
		this.daySelect.append( option );
	}

	this.append( this.yearSelect ).append( this.monthSelect ).append( this.daySelect );
	
	if( typeof sHour != 'undefined' ) {
		this.hourSelect = new ArdeSelect();
		for( var hour = 0; hour < 24; ++hour ) {
			option = ardeE( 'option' ).attr( 'value', hour ).append( ardeT( ardeLocale.number( ardeLeftPad( hour, 0, 2 ) ) ) ).appendTo( this.hourSelect );
			if( hour == sHour ) option.attr( 'selected', 'true' );
		}
		this.append( ardeT( ardeNbsp(4) ) ).append( this.hourSelect );
		if( typeof sMinute != 'undefined' ) {
			this.minuteSelect = new ArdeSelect();
			for( var minute = 0; minute < 60; ++minute ) {
				option = ardeE( 'option' ).attr( 'value', minute ).append( ardeT( ardeLocale.number( ardeLeftPad( minute, 0, 2 ) ) ) ).appendTo( this.minuteSelect );
				if( minute == sMinute ) option.attr( 'selected', 'true' );
			}
			this.append( ardeT( ':' ) ).append( this.minuteSelect );
			
			if( typeof sSecond != 'undefined' ) {
				this.secondSelect = new ArdeSelect();
				for( var second = 0; second < 60; ++second ) {
					option = ardeE( 'option' ).attr( 'value', second ).append( ardeT( ardeLocale.number( ardeLeftPad( second, 0, 2 ) ) ) ).appendTo( this.secondSelect );
					if( second == sSecond ) option.attr( 'selected', 'true' );
				}
				this.append( ardeT( ':' ) ).append( this.secondSelect );
			}
		}
	}
	
}
DateSelect.monthNames = [ '', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];

DateSelect.prototype.setDisabled = function ( disabled ) {
	this.yearSelect.setDisabled( disabled );
	this.monthSelect.setDisabled( disabled );
	this.daySelect.setDisabled( disabled );
	if( typeof this.hourSelect != 'undefined' ) {
		this.hourSelect.setDisabled( disabled );
		if( typeof this.minuteSelect != 'undefined' ) {
			this.minuteSelect.setDisabled( disabled );
			if( typeof this.secondSelect != 'undefined' ) {
				this.secondSelect.setDisabled( disabled );
			}
		}
	}
	return this;
}

DateSelect.prototype.getParams = function( prefix ) {
	if( typeof prefix == 'undefined' ) prefix = '';
	var p =	prefix+'y='+this.yearSelect.selectedOption().value;
	p +='&'+prefix+'m='+this.monthSelect.selectedOption().value;
	p +='&'+prefix+'d='+this.daySelect.selectedOption().value;
	if( typeof this.hourSelect != 'undefined' ) {
		p += '&'+prefix+'h='+this.hourSelect.selectedOption().value;
		if( typeof this.minuteSelect != 'undefined' ) {
			p += '&'+prefix+'i='+this.minuteSelect.selectedOption().value;
			if( typeof this.secondSelect != 'undefined' ) {
				p += '&'+prefix+'s='+this.secondSelect.selectedOption().value;
			}
		}
	}
	return p;
}

DateSelect.prototype.setTimestamp = function( ts ) {
	var dt = new Date( ts );

	this.yearSelect.element.selectedIndex = dt.getUTCFullYear() - minYear;
	this.monthSelect.element.selectedIndex = dt.getUTCMonth();
	this.daySelect.element.selectedIndex = dt.getUTCDate() - 1;
	if( typeof this.hourSelect != 'undefined' ) {
		this.hourSelect.element.selectedIndex = dt.getUTCHours();
		if( typeof this.minuteSelect != 'undefined' ) {
			this.minuteSelect.element.selectedIndex = dt.getUTCMinutes();
			if( typeof this.secondSelect != 'undefined' ) {
				this.secondSelect.element.selectedIndex = dt.getUTCSeconds();
			}
		}
	}
}

ArdeClass.extend( DateSelect, ArdeComponent );

function EntityView( showText, showImage, link, stringId, trimString, forceLtr ) {
	this.showText = showText;
	this.showImage = showImage;
	this.link = link;
	if( typeof stringId == 'undefined' ) this.stringId = 1;
	else this.stringId = stringId;
	if( typeof trimString == 'undefined' ) this.trimString = false;
	else this.trimString = trimString;
	if( typeof forceLtr == 'undefined' ) this.forceLtr = null;
	else this.forceLtr = forceLtr;
	this.ArdeComponent( 'span' );
	
	this.stringSelect = new ArdeSelect();
	this.append( ardeT( 'string: ' ) ).append( this.stringSelect );
	
	this.textCheck = new ArdeCheckBox( this.showText );
	this.imageCheck = new ArdeCheckBox( this.showImage ); 
	this.linkCheck = new ArdeCheckBox( this.link );
	this.append( this.textCheck ).append( ardeT( 'text ' ) );
	this.append( this.imageCheck ).append( ardeT( 'image ' ) );
	this.append( this.linkCheck ).append( ardeT( 'link' ) );
	
	
	
}

EntityView.prototype.setEntityId = function ( entityId ) {
	this.stringSelect.clear();
	if( entityId != 0 && typeof EntityView.strings[ entityId ] != 'undefined' ) {
		for( var id in EntityView.strings[ entityId ] ) {
			var o = ardeE( 'option' ).attr( 'value', id ).append( ardeT( EntityView.strings[ entityId ][id] ) );
			if( this.stringId == id ) o.attr( 'selected', 'true' );
			this.stringSelect.append( o );
		}
	}
	for( var id in EntityView.strings[0] ) {
		var o = ardeE( 'option' ).attr( 'value', id ).append( ardeT( EntityView.strings[0][id] ) );
		if( this.stringId == id ) o.attr( 'selected', 'true' );
		this.stringSelect.append( o );
	}
}

EntityView.strings = {};

EntityView.prototype.clone = function () {
	return new EntityView( this.showText, this.showImage, this.link, this.stringId, this.trimString, this.forceLtr );
};

EntityView.prototype.inputClone = function () {
	var stringId = parseInt( this.stringSelect.selectedOption().value );
	return new EntityView( this.textCheck.element.checked?true:false, this.imageCheck.element.checked?true:false, this.linkCheck.element.checked?true:false, stringId, this.trimString, this.forceLtr );
};

EntityView.fromXml = function ( element ) {
	return new EntityView(
		 ArdeXml.boolAttribute( element, 'text' )
		,ArdeXml.boolAttribute( element, 'image' )
		,ArdeXml.boolAttribute( element, 'link' )
		,ArdeXml.intAttribute( element, 'string_id' )
		,ArdeXml.intAttribute( element, 'trim_string' )
		,ArdeXml.boolAttribute( element, 'force_ltr', null )
	);
};

EntityView.prototype.getParams = function ( prefix ) {
	var t = this.textCheck.element.checked?'t':'f';
	var i = this.imageCheck.element.checked?'t':'f';
	var l = this.linkCheck.element.checked?'t':'f';
	var fl = this.forceLtr === null ? '' : ('&'+prefix+'fl='+(this.forceLtr?'t':'f'));
	var stringId = this.stringSelect.selectedOption().value;
	return prefix+'t='+t+'&'+prefix+'i='+i+'&'+prefix+'l='+l+'&'+prefix+'si='+stringId+'&'+prefix+'ts='+this.trimString+fl;
};

ArdeClass.extend( EntityView, ArdeComponent );

function EntityVList() {
	this.a = new Array();
	this.more = false;
}
EntityVList.fromXml = function ( element ) {
	var o = new EntityVList();
	o.more = ArdeXml.boolAttribute( element, 'more' );
	entityVEs = new ArdeXmlElemIter( element, 'entity_v' );
	while( entityVEs.current ) {
		o.a.push( EntityV.fromXml( entityVEs.current ) );
		entityVEs.next();
	}
	return o;
};

function EntityV( entityId, id, str, showImage, link, mode, trimString, forceLtr ) {

	this.entityId = entityId;
	this.id = id;
	this.str = str;
	
	if( typeof trimString == 'undefined' ) this.trimString = 0;
	this.trimString = trimString;
	
	if( typeof forceLtr == 'undefined' ) this.forceLtr = false;
	else this.forceLtr = forceLtr;
	
	var self = this;
	
	
	
	this.optionMakers = [
		function ( entityV, pane ) {
			var f = new ArdeClipboardCopyButton( ardeLocale.text( 'Copy to Clipboard' ), self.getClipboardStr() );
			var sp = new ArdeImg( baseUrl+'img/dummy.gif', '', '1', '25' );
			pane.append( ardeE( 'div' ).cls( 'option pad_full' ).style( 'paddingBottom', '0px' ).append( f ).append( sp ) );
			
		}
	];
	
	if( typeof link == 'undefined' ) this.link = null;
	else this.link = link;
	
	if( typeof showImage == 'undefined' ) this.showImage = false;
	else this.showImage = showImage;
	
	if( typeof mode == 'undefined' ) this.mode = EntityV.MODE_BLOCK;
	this.mode = mode;

	
	if( this.mode == EntityV.MODE_BLOCK ) tagName = 'div';
	else tagName = 'span';
	this.ArdeItem( tagName );
	if( this.mode == EntityV.MODE_INLINE_BLOCK ) this.setDisplayMode( 'inline-block' );
	
	if( this.link !== null ) {
		var parent = ardeE( 'a' ).cls( 'entityv' ).attr( 'href', this.link ).appendTo( this );
	} else {
		var parent = this;
	}
	
	if( this.showImage ) {
		this.img = this.getImage();
		parent.append( this.img );
	}
	

	if( this.str !== null ) {
		this.strSpan = new ArdeComponent( 'span' ).appendTo( parent );
		if( this.forceLtr ) this.strSpan.style( 'direction', 'ltr' ).style( 'unicodeBidi', 'bidi-override');
		this.writeStr();
	}
}

EntityV.prototype.getClipboardStr = function () {
	return this.getStr();
}

EntityV.prototype.writeStr = function () {
	if( this.str !== null ) {
		this.strSpan.clear();
		var trimmed = null;
		var str = this.getStr();
		if( this.trimString != 0 ) {
			if( str.length > this.trimString ) {
				trimmed = str.substr( 0, this.trimString )+'...';
			}
		}
		if( trimmed !== null ) {
			this.strSpan.append( ardeT(this.showImage?' ':'') ).append( new ArdeComponent( 'span' ).setCursor( 'help' ).attr( 'title', str ).append( ardeT( trimmed ) ) );
		} else {
			this.strSpan.append( ardeT( (this.showImage?' ':'')+str ) );
		}
	}
}

EntityV.MODE_BLOCK = 1;
EntityV.MODE_INLINE= 2;
EntityV.MODE_INLINE_BLOCK = 3;

EntityV._fromXml = function( element, mode ) {
	var cls = ArdeXml.attribute( element, 'js_class', null );
	if( cls === null ) constructor = EntityV;
	else if( typeof EntityV.classMap[ cls ] == 'undefined' ) var constructor = EntityV;
	else constructor = EntityV.classMap[ cls ];
	return new constructor.fromArgs( constructor, constructor.makeArgs( element ), mode );

};

EntityV.fromXml = function ( element ) {
	return EntityV._fromXml( element, EntityV.MODE_BLOCK );
};

EntityV.fromArgs = function ( constructor, args, mode ) {
	return new constructor( args.entityId, args.id, args.str, args.showImage, args.link, mode, args.trimString, args.forceLtr );
};

EntityV.makeArgs = function ( element ) {
	var args = {};
	args.entityId = ArdeXml.intAttribute( element, 'entity_id' );
	args.id = ArdeXml.intAttribute( element, 'id' );
	args.showImage = ArdeXml.boolAttribute( element, 'image' );
	args.str = ArdeXml.strElement( element, 'string', null );
	args.link = ArdeXml.strElement( element, 'link', null );
	args.trimString = ArdeXml.intAttribute( element, 'trim_string', 0 );
	args.forceLtr = ArdeXml.boolAttribute( element, 'force_ltr' );
	return args;
};

EntityV.prototype.getImage = function () {
	return new ArdeImg( twatchUrl +'img/entities/'+this.entityId+'/'+this.id+'.png', null, 16, 16 ).style( 'verticalAlign', '-4px' );
}

EntityV.prototype.getStr = function () {
	if( this.str === null ) return '';
	return this.str;
};


EntityV.prototype.makeOptionsButton = function() {
	if( this.optionMakers.length == 0 ) return;
	
	
	
	this.optionsButton = new ArdeImgButton( baseUrl+'img/options2.gif', 'options', 16, 16 );
	this.optionsButton.style( 'verticalAlign', '-3px' );
	this.optionsButton.style( 'margin'+ardeLocale.Right(), '5px' );

	this.insertFirstChild( this.optionsButton );
	
	this.optionsHolder = new OptionsHolder();
	this.optionsHolder.style( 'width', '370px' );
	this.insertFirstChild( this.optionsHolder );

	
	
	var self = this;
	this.optionsButton.element.onclick = function () {
		
		for( var i in self.optionMakers ) {
			self.optionMakers[i]( self, self.optionsHolder.pane );
		}
		
		
		self.optionsHolder.setDisplay( true );
		self.optionsHolder.forceRedraw();
	};
	
};


function HangingPane( imageButton, inactiveButton ) {
	if( typeof inactiveButton == 'undefined' ) inactiveButton = false;
	this.ArdeComponent( 'div' );
	this.style( 'position', 'absolute' );
	this.style( 'margin'+ardeLocale.Left(), '-1px' );
	this.style( 'marginTop', '-1px' );
	this.setDisplay( false );
	this.cls( 'options_holder' );
	this.button = imageButton;
	this.button.style( 'verticalAlign', '-3px' );
	this.button.style( 'position', 'relative' );
	this.button.style( 'zIndex', '10000' );
	this.buttonSpan = new ArdeComponent( 'span' ).setDisplayMode( 'inline-block' ).cls( 'button' ).append( this.button );
	this.append( this.buttonSpan );
	this.pane = new ArdeComponent( 'div' ).cls( 'pane' );
	this.pane.style( 'marginTop', '-1px' );
	this.append( this.pane );
	
	if( !inactiveButton ) {
		var self = this;
		this.button.element.onclick = function () {
			self.setDisplay( false );
			self.pane.clear();
			self.forceRedraw();
		}
	}
	
};

ArdeClass.extend( HangingPane, ArdeComponent );

HangingPane.prototype.setOffset = function ( offset ) {
	if( ardeBro.ie && ardeBro.ie < 8 ) return;
	this.style( 'margin'+ardeLocale.Left(), (-offset)+'px' );
	this.buttonSpan.style( 'margin'+ardeLocale.Left(), offset+'px' );
}

function OptionsHolder() {
	this.HangingPane( new ArdeImgButton( baseUrl+'img/options.gif', null, 16, 16 ) );
}
ArdeClass.extend( OptionsHolder, HangingPane );


EntityV.prototype.setMouseOverHighlight = function( mouseOverHighlight ) {
	if( mouseOverHighlight ) {
		this.element.style.background = '#f88';
		this.element.style.color = '#000';
	} else {
		this.element.style.background = '#fff';
		this.element.style.color = '#000';
	}
	return this;
};

EntityV.prototype.setSelectedHighlight = function( selectedHighlight ) {
	if( selectedHighlight ) {
		this.element.style.background = '#a00';
		this.element.style.color = '#fff';
	} else {
		this.element.style.background = '#fff';
		this.element.style.color = '#000';
	}
	return this;
};

ArdeClass.extend( EntityV, ArdeItem );

function InlineEntityV() { throw new ArdeException( "dont use InlineEntityV's constructor" ); }
InlineEntityV.fromXml = function ( element ) {
	return EntityV._fromXml( element, EntityV.MODE_INLINE );
}

function InlineBlockEntityV() { throw new ArdeException( "dont use InlineBlockEntityV's constructor" ); }
InlineEntityV.fromXml = function ( element ) {
	return EntityV._fromXml( element, EntityV.MODE_INLINE_BLOCK );
}

function EntityVPage( entityId, id, str, showImage, link, mode, trimString ) {
	this.EntityV( entityId, id, str, showImage, link, mode, trimString );
}

EntityVPage.prototype.getClipboardStr = function () {
	if( this.str === null ) return '';
	return this.str;
}

EntityVPage.prototype.getStr = function () {
	if( this.str === null ) return '';
	return ardeUnescape( this.str );
};

ArdeClass.extend( EntityVPage, EntityV );


function EntityVTime( entityId, id, str, showImage, link, mode, trimString ) {
	this.EntityV( entityId, id, str, showImage, link, mode, trimString );
}

EntityVTime.prototype.writeStr = function () {
	if( this.str !== null ) {
		var matches;
		if( matches = this.str.match( /^(.+)(\d\d\:\d\d\:\d\d)$/ )  ) {
			this.strSpan.append( ardeT( matches[1] ) ).append( ardeT( ardeNbsp(2) ) ).append( ardeE( 'b' ).append( ardeT( matches[2] ) ) );
		} else {
			this.strSpan.append( ardeT( this.str ) );
		}
		
	}
}

ArdeClass.extend( EntityVTime, EntityV );

function EntityVCountry( entityId, id, str, showImage, link, mode, trimString ) {
	this.EntityV( entityId, id, str, showImage, link, mode, trimString );
}

EntityVCountry.prototype.getImage = function () {
	return new ArdeImg( ardeCountryUrl +'img/flags/'+this.id+'.gif', null, 26, 13 ).style( 'verticalAlign', '-2px' );
}

ArdeClass.extend( EntityVCountry, EntityV );

function EntityVRefGroup( entityId, id, str, showImage, link, mode, trimString, forceLtr, typeEntityId, typeId ) {
	this.typeEntityId = typeEntityId;
	this.typeId = typeId;
	this.EntityV( entityId, id, str, showImage, link, mode, trimString, forceLtr );
}
EntityVRefGroup.makeArgs = function ( element ) {
	var args = EntityV.makeArgs( element );
	args.typeEntityId = ArdeXml.intAttribute( element, 'type_entity_id' );
	args.typeId = ArdeXml.intAttribute( element, 'type_id' );
	return args;
}
EntityVRefGroup.fromArgs = function ( constructor, args, mode ) {
	return new constructor( args.entityId, args.id, args.str, args.showImage, args.link, mode, args.trimString, args.forceLtr, args.typeEntityId, args.typeId );
}

EntityVRefGroup.prototype.getImage = function () {
	return new ArdeImg( twatchUrl +'img/entities/'+this.typeEntityId+'/'+this.typeId+'.gif', null, 17, 17 ).style( 'verticalAlign', '-4px' );
}

ArdeClass.extend( EntityVRefGroup, EntityV );

function EntityVProcRef( entityId, id, str, showImage, link, mode, trimString, forceLtr, typeEntityId, typeId, keyword ) {
	this.typeEntityId = typeEntityId;
	this.typeId = typeId;
	this.keyword = keyword;
	this.EntityV( entityId, id, str, showImage, link, mode, trimString, forceLtr );
}

EntityVProcRef.prototype.getClipboardStr = function () {
	if( this.keyword !== null ) return this.keyword;
	return this.getStr();
}

EntityVProcRef.makeArgs = function ( element ) {
	var args = EntityV.makeArgs( element );
	args.typeEntityId = ArdeXml.intAttribute( element, 'type_entity_id' );
	args.typeId = ArdeXml.intAttribute( element, 'type_id' );
	args.keyword = ArdeXml.strAttribute( element, 'keyword', null );
	return args;
}
EntityVProcRef.fromArgs = function ( constructor, args, mode ) {
	return new constructor( args.entityId, args.id, args.str, args.showImage, args.link, mode, args.trimString, args.forceLtr, args.typeEntityId, args.typeId, args.keyword );
}

EntityVProcRef.prototype.getImage = function () {
	return new ArdeImg( twatchUrl +'img/entities/'+this.typeEntityId+'/'+this.typeId+'.gif', null, 17, 17 ).style( 'verticalAlign', '-4px' );
}

ArdeClass.extend( EntityVProcRef, EntityV );


function EntityVIp( entityId, id, str, showImage, link, mode, trimString, forceLtr, domain, stringId, country ) {
	this.domain = domain;
	this.stringId = stringId;
	if( typeof country == 'undefined' ) country = null;
	this.country = country;
	this.EntityV( entityId, id, str, showImage, link, mode, trimString, forceLtr );
	
	if( this.stringId == EntityVIp.STRING_DOMAIN || this.stringId == EntityVIp.STRING_DOMAIN_IP ) {
		if( this.domain == null ) {
			this.requesterSpan = new ArdeComponent( 'span' ).setOpacity( '.5' ).appendTo( this );
			this.requester = new ArdeRequestIcon();
			this.requester.showOk = false;
			this.requester.silent = true;
			var self = this;
			this.resolvingText = new ArdeComponent( 'span' ).append( ardeT( 'resolving' ) );
			this.requester.afterResultReceived = function ( domain ) {
				self.resolvingText.remove();
				if( domain.str == 'unresolved' ) {
					self.domain = false;
				} else {
					self.domain = domain.str;
				}
				self.writeStr();
			}
			
			this.requesterSpan.append( ardeT( ' ' ) ).append( this.requester ).append( this.resolvingText );
			this.requester.request( twatchFullUrl( twatchUrl+'rec/rec_dns.php' ), 'i='+this.id, Domain );
		}
	}
	
	this.optionMakers.push( function ( entityV, pane ) { entityV.makeCountryOption( pane ); } );
}

EntityVIp.STRING_DOMAIN = 1001;
EntityVIp.STRING_DOMAIN_IP = 1002;

EntityVIp.prototype.makeCountryOption = function ( pane ) {
	var div = ardeE( 'div' ).cls( 'margin_half pad_full' ).appendTo( pane );
	if( this.country == null ) {
		div.append( ardeT( ardeLocale.text('Unknown Country') ) );
	} else {
		div.append( ardeT( ardeLocale.text('Country')+': ' ) ).append( this.country );
	}
}

EntityVIp.prototype.getClipboardStr = function () {
	if( this.str === null ) return '';
	return this.str;
}


EntityVIp.prototype.getStr = function () {
	if( this.str === null ) return '';
	if( this.stringId == EntityVIp.STRING_DOMAIN && typeof this.domain == 'string' ) {
		return this.domain;
	} else if( this.stringId == EntityVIp.STRING_DOMAIN_IP && typeof this.domain == 'string' ) {
		return this.domain+' ('+this.str+')';
	}
	return this.str;
}

EntityVIp.makeArgs = function ( element ) {
	var args = EntityV.makeArgs( element );
	var resolve = ArdeXml.boolAttribute( element, 'resolve', false );
	if( resolve ) {
		args.domain = null;
	} else {
		args.domain = ArdeXml.strAttribute( element, 'domain', false );
	}
	args.stringId = ArdeXml.intAttribute( element, 'string_id' );
	var countryE = ArdeXml.element( element, 'country', null );
	if( countryE === null ) {
		args.country = null;
	} else {
		args.country = InlineEntityV.fromXml( countryE );
	}
	return args;
}

EntityVIp.fromArgs = function ( constructor, args, mode ) {
	return new constructor( args.entityId, args.id, args.str, args.showImage, args.link, mode, args.trimString, args.forceLtr, args.domain, args.stringId, args.country );
}

ArdeClass.extend( EntityVIp, EntityV );

function Domain( str ) {
	this.str = str;
}
Domain.fromXml = function ( element ) {
	return new Domain( ArdeXml.strContent( element ) );
}

EntityV.classMap = {
	 'EntityVCountry': EntityVCountry
	,'EntityVRefGroup': EntityVRefGroup
};

function TimeZone( serverTs, difference, name, prefix, embedded ) {
	this.difference = difference * 1000;
	this.serverTs = serverTs * 1000;
	this.name = name;
	this.prefix = prefix;
	this.embedded = embedded;
	this.firstClientTs = new Date().getTime();

	this.ArdeComponent( 'div' );
	this.cls( 'block' );
	
	this.timeText = ardeT( '' );
	this.updateTime();
	var self = this;
	setInterval( function () { self.updateTime(); }, 500 );
	
	this.signSelect = new ArdeSelect().attr( 'name', prefix+'sg' );
	this.signSelect.append( ardeE( 'option' ).append( ardeT( '+' ) ).attr( 'value', 'p' ) );
	this.signSelect.append( ardeE( 'option' ).append( ardeT( '-' ) ).attr( 'value', 'm' ) );
	
	this.hourSelect = new ArdeSelect().attr( 'name', prefix+'hs' );
	for( var h = 0; h < 14; ++h ) {
		hs = (h<10?'0':'')+h;
		this.hourSelect.append( ardeE( 'option' ).append( ardeT( hs ) ).attr( 'value', h ) );
	}
	
	this.minuteSelect = new ArdeSelect().attr( 'name', prefix+'ms' );
	for( var m = 0; m < 4; ++m ) {
		ms = ((m*15)<10?'0':'')+(m*15);
		this.minuteSelect.append( ardeE( 'option' ).append( ardeT( ms ) ).attr( 'value', m*15 ) );
	}
	
	this.setDifference( this.difference );
	
	this.computerButton = new ArdeButton( 'Computer Time' ).cls( 'passive' );
	
	this.computerButton.element.onclick = function () { self.computerClicked(); };
	this.signSelect.element.onchange = function () { self.updateDifference(); };
	this.hourSelect.element.onchange = function () { self.updateDifference(); };
	this.minuteSelect.element.onchange = function () { self.updateDifference(); };
	
	this.append( ardeE( 'p' ).append( ardeT( "The time shown should match your computer's time (or any time you want logger to work with)" ) ) );
	
	var p = ardeE( 'p' ).appendTo( this );
	p.append( ardeE( 'span' ).cls( 'fixed' ).append( this.timeText ) );
	p.append( ardeT( ' '+ardeNbsp( 4 ) ) ).append( this.signSelect ).append( this.hourSelect ).append( this.minuteSelect );
	p.append( ardeT( ' '+ardeNbsp( 4 ) ) ).append( this.computerButton );
	
	this.nameInput = new ArdeInput( this.name );
	this.nameInput.attr( 'name', prefix+'n' );
	this.append( ardeE( 'p' ).append( ardeT( 'Time Zone Name: ' ) ).append( this.nameInput ) );
	
	if( !this.embedded ) {
		this.applyButton = new ArdeRequestButton( 'Apply Change' );
		this.restoreButton = new ArdeRequestButton( 'Restore Defaults' );
		this.applyButton.setStandardCallbacks( this, 'apply' );
		this.restoreButton.setStandardCallbacks( this, 'restore' );
		this.append( ardeE( 'p' ).append( this.applyButton ).append( this.restoreButton ) );
	}
	
}

TimeZone.prototype.getParams = function ( prefix ) {
	var p = prefix+'sg='+this.signSelect.selectedOption().value;
	p += '&'+prefix+'hs='+this.hourSelect.selectedOption().value;
	p += '&'+prefix+'ms='+this.minuteSelect.selectedOption().value;
	p += '&'+prefix+'n='+ardeEscape( this.nameInput.element.value );
	return p;
}

TimeZone.prototype.applyClicked = function () {
	this.applyButton.request( twatchFullUrl( 'rec/rec_general.php' ), 'a=set_time_zone&'+this.getParams( this.prefix ) );
}

TimeZone.prototype.restoreClicked = function () {
	this.restoreButton.request( twatchFullUrl( 'rec/rec_general.php' ), 'a=restore_time_zone', TimeZoneData );
}

TimeZone.prototype.restoreConfirmed = function ( tz ) {
	this.setDifference( tz.difference );
	this.nameInput.element.value = tz.name;
}

function TimeZoneData( difference, name ) {
	this.difference = difference;
	this.name = name;
}

TimeZoneData.fromXml = function ( element ) {
	return new TimeZoneData( ArdeXml.intAttribute( element, 'diff' ), ArdeXml.attribute( element, 'name' ) );
}

TimeZone.prototype.computerClicked = function () {
	var diff = - new Date().getTimezoneOffset() * 60 * 1000 + this.firstClientTs - this.serverTs;
	this.setDifference( diff );
}

TimeZone.prototype.setDifference = function( diff ) {
	
	if( Math.abs( diff ) >= 14 * 3600 * 1000 ) return alert( 'there is about '+Math.round( Math.abs( diff ) / ( 3600 * 1000 ) )+" hours difference between server time and your computer's time.\neither your computer or server's time is wrong." );
	
	if( diff < 0 ) {
		diff *= -1;
		this.signSelect.element.selectedIndex = 1;
	} else {
		this.signSelect.element.selectedIndex = 0;
	}
	
	
	diff = Math.round( diff / 1000 );

	var hs = this.hourSelect.element.selectedIndex = Math.floor( diff/3600 );
	
	diff -= hs * 3600;
	var ms = Math.round( diff / (15*60) );
	if( ms == 4 ) ms = 3;
	this.minuteSelect.element.selectedIndex = ms;
	
	this.updateDifference();
}


TimeZone.prototype.updateDifference = function () {
	var diff = this.hourSelect.selectedOption().value * 3600 * 1000;
	diff += this.minuteSelect.selectedOption().value * 60 * 1000;
	if( this.signSelect.selectedOption().value == 'm' ) diff *= -1;
	this.difference = diff;
	this.updateTime();
}
TimeZone.months = new Array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
TimeZone.prototype.updateTime = function () {
	var dt = new Date( this.serverTs + new Date().getTime() - this.firstClientTs + this.difference );

	var d = dt.getUTCDate();
	if( d%10 == 1 ) d += 'st';
	else if( d%10 == 2 ) d += 'nd';
	else if( d%10 == 3 ) d += 'rd';
	else d += 'th';
	var h = dt.getUTCHours();
	var m = dt.getUTCMinutes();
	var mon = dt.getUTCMonth();
	var s = dt.getUTCSeconds();
	h = (h<10?'0':'')+h;
	m = (m<10?'0':'')+m;
	s = (s<10?'0':'')+s;
	this.timeText.n.nodeValue = h+':'+m+':'+s+' '+TimeZone.months[ mon ]+' '+d+' ';
};
ArdeClass.extend( TimeZone, ArdeComponent );

function EntityVOption( imageUrl, text ) {
	
	this.ArdeComponent( 'div' );
	this.cls( 'option pad_full' );
	
	this.icon = new ArdeImg( imageUrl, null, 16, 16 );
	this.icon.cls( 'icon' );
	this.icon.style( 'verticalAlign', '-4px' );
	var span = new ArdeComponent( 'span' ).append( this.icon ).append( ardeT( ' ' ) ).append( this.getTextElement( text ) ).append( ardeT( ' ' ) );
	this.append( span );
	this.icon.setClickable( true );
	var self = this;
	this.icon.element.onclick = function () {
		self.clicked();
	}
}
EntityVOption.prototype.getTextElement = function( text ) {
	return ardeT( text );
};
ArdeClass.extend( EntityVOption, ArdeComponent );


function TwatchExpressionInput( expression, emptyString ) {
	this.ArdeExpressionInput( expression, emptyString );
}
if( typeof ArdeExpressionInput != 'undefined' ) {
	ArdeClass.extend( TwatchExpressionInput, ArdeExpressionInput );
}

function TwatchExpressionInputEVVar( name, id, entities ) {
	this.entities = entities;
	var values = {};
	for( var i in entities ) {
		values[i] = entities[i].name;
	}
	this.ArdeExpressionInputSFVar( name, id, values );
}

TwatchExpressionInputEVVar.prototype.selected = function ( input ) {
	this.ArdeExpressionInputSFVar_selected( input );
	this.entityVSelect = new ArdeActiveSelect( null, 10 ).setDisplay( false );
	var self = this;
	this.entityVSelect.onchange = function () {
		if( self.entityVSelect.getValue() !== null ) {
			self.addClicked( input );
			input.select.element.selectedIndex = 0;
			input.selectChanged();
		}
	}
	this.entityVSelect.setStandardCallbacks( this, 'entityValues' );
	input.extras.append( this.entityVSelect );
}

TwatchExpressionInputEVVar.prototype.selectChanged = function ( input ) { 
	this.entityVSelect.retract();
	this.entityVSelect.setSelected( null );
	if( this.select.element.selectedIndex == 0 ) {
		this.entityVSelect.setDisplay( false );
	} else {
		this.entityVSelect.showAddButton( this.entities[ this.select.selectedOption().value ].allowExplicitAdd );
		this.entityVSelect.setDisplay( true );
	}
};

TwatchExpressionInputEVVar.prototype.addClicked = function( input ) {
	if( this.select.selectedOption() == null ) return;
	var id = parseInt( this.select.selectedOption().value );
	if( this.entityVSelect.getSelected() == null ) return alert( 'please select a value' );
	var entityV = this.entityVSelect.getSelected();
	input.list.addItem( new ArdeExpressionElemComponent( new ArdeExpressionElem( entityV.getStr(), [ this.id, id, entityV.id ] ) ) );
}

TwatchExpressionInputEVVar.prototype.entityValuesRequested = function( offset, count, beginWith ) {
	if( beginWith == '' ) b = '';
	else b = '&b='+ardeEscape( beginWith );
	this.entityVSelect.requester.request( twatchFullUrl( twatchUrl + 'rec/rec_entity_values.php' ),
		'a=get_values&i='+this.select.selectedOption().value+'&o='+offset+'&c='+count+b+'&w='+websiteId,
		ardeXmlObjectListClass( EntityV, 'entity_v', true, false )
	);
};

TwatchExpressionInputEVVar.prototype.entityValuesReceived = function( result ) {
	this.entityVSelect.resultsReceived( result.a, result.more );
};

TwatchExpressionInputEVVar.prototype.entityValuesAddClicked = function( str ) {
	this.entityVSelect.addButton.request( twatchFullUrl( twatchUrl + 'rec/rec_entity_values.php' ), 'a=add&ei='+this.select.selectedOption().value+'&s='+ardeEscape( str ), EntityV );
};

if( typeof ArdeExpressionInputSFVar != 'undefined' ) {
	ArdeClass.extend( TwatchExpressionInputEVVar, ArdeExpressionInputSFVar );
}

function Entity( name, allowExplicitAdd ) {
	this.name = name;
	this.allowExplicitAdd = allowExplicitAdd;
}

function twatchFullUrl( path ) {
	var url = new ArdeUrlWriter( path );
	url.setParam( 'profile', twatchProfile, 'default' );
	url.setParam( 'lang', ardeLocale.id, ardeLocale.defaultId );
	return url.getUrl();
}


function VisitorType( id, name ) {
	this.id = id;
	this.name = name;
}


