
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

function ArdeClass() {}

ArdeClass.extend = function( descendant, parent ) {   
	descendant.prototype[ ArdeClass.className( parent ) ] = parent; 
    for ( var m in parent.prototype ) {
    	if( descendant.prototype[m] != undefined ) {
    		descendant.prototype[ ArdeClass.className( parent ) + '_' + m ] = parent.prototype[m];
    	} else {
    		descendant.prototype[m] = parent.prototype[m];
    	}
    }
    
    for ( var m in parent ) {
    	if( m != 'parentClasses' ) {
	    	if( descendant[m] != undefined ) {
	    		descendant[ ArdeClass.className( parent ) + '_' + m ] = parent[m];
	    	} else {
	    		descendant[m] = parent[m];
	    	}
    	}
    }
    
    if( typeof descendant.parentClasses == 'undefined' ) descendant.parentClasses = new Array();
    descendant.parentClasses.push( parent );
};

ArdeClass.functionName = function( func ) {
	if( !(func instanceof Function) ) throw "ArdeClass.functionName only accepts functions as parameter";
	var res = func.toString().match( /\s*function (.*)\(/ );
	if( !res ) return null;
	return res[1];
};

ArdeClass.className = function( cls ) {
	return ArdeClass.functionName( cls );
};

ArdeClass.instanceOf = function( obj, cls ) {
	if( obj instanceof cls ) return true;
	if( !obj.constructor instanceof Function ) return false;
	return ArdeClass.hasParent( obj.constructor, cls );
};

ArdeClass.hasParent = function( cls, parent ) {
	if( typeof cls.parentClasses == 'undefined' ) return false;
	for( var i in cls.parentClasses ) {
		if( cls.parentClasses[i] == parent ) return true;
		if( ArdeClass.hasParent( cls.parentClasses[i], parent ) ) return true;
	}
	return false;
};

ArdeClass.isA = function( cls1, cls2 ) {
	if( cls1 == cls2 ) return true;
	return ArdeClass.hasParent( cls1, cls2 );
};


ArdeClass.objectClassName = function( obj ) {
	if( typeof obj != 'object' ) return '[unknown]';
	if( !( obj.constructor instanceof Function ) ) return '[unknown]';
	return ArdeClass.functionName( obj.constructor ); 
};

function ArdeException( sender, message, code, child, extra ) {
	this.type = ArdeException.ERROR;
	this.cls = 'JavaScript Exception';
	this.sender = sender;
	this.message = message;
	this.extras = [];
	if( code == undefined ) {
		this.code = 0;
	} else {
		this.code = code;
	}

	if( child == undefined || child == null ) {
		this.child = null;
	} else if( !( child instanceof ArdeException ) ) {
		this.child = ArdeException.fromJsError( child );
	} else {
		this.child = child;
	}
	if( typeof extra != 'undefined' ) {
		this.extras.push( extra );
	}
	this.file = '';
	this.line = 0;
	this.trace = new Array();
	this.muted = false;
}

ArdeException.ERROR = 1;
ArdeException.WARNING = 2;
ArdeException.USER_ERROR = 3;

ArdeException.stringTypes = { 'error': ArdeException.ERROR, 'warning': ArdeException.WARNING, 'user_error': ArdeException.USER_ERROR };
ArdeException.typeStrings = {};
ArdeException.typeStrings[ ArdeException.ERROR ] = 'error';
ArdeException.typeStrings[ ArdeException.WARNING ] = 'warning';
ArdeException.typeStrings[ ArdeException.USER_ERROR ] = 'user_error';

ArdeException.fromJsError = function( jsError ) {
	var o = new ArdeException( 'Javascript', jsError.message );
	o.cls = 'Javascript Error';
	if( typeof jsError.fileName == 'string' ) {
		o.file = jsError.fileName;
	} else {
		o.file = '';
	}
	if( typeof jsError.line == 'number' ) {
		o.line = jsError.lineNumber;
	} else {
		o.line = 0;
	}
	return o;
};


ArdeException.prototype.toString = function () {
	var s = this.message;
	return s;
};

ArdeException.fromXml = function( element ) {
	try {	
		
		var message = ArdeXml.strElement( element, 'message', null );
		var code = ArdeXml.intAttribute( element, 'code', null );
		

		var childElement = ArdeXml.element( element, 'child', null );

		if( childElement != null ) {
			var child = ArdeException.fromXml( childElement );
		} else {
			var child = null;
		}
		
		var o = new ArdeException( '', message, code, child );
		
		var typeString = ArdeXml.attribute( element, 'type' );
		if( ArdeException.stringTypes[ typeString ] == undefined ) {
			throw new ArdeException( 'invalid type '+typeString );
		}
		o.type = ArdeException.stringTypes[ typeString ];
		o.cls = ArdeXml.attribute( element, 'class' );
		
		o.file = ArdeXml.strElement( element, 'file', '' );
		o.line = ArdeXml.intAttribute( element, 'line', 0 );
		
		o.muted = ArdeXml.boolAttribute( element, 'muted', false );
		
		var traceEs = new ArdeXmlElemIter( element, 'trace' );
		while( traceEs.current ) {
			o.trace.push( ArdeTrace.fromXml( traceEs.current ) );
			traceEs.next();
		}
		
		var extraEs = new ArdeXmlElemIter( ArdeXml.element( element, 'extras' ), 'extra' );
		while( extraEs.current ) {
			o.extras.push( ArdeXml.strContent( extraEs.current ) );
			extraEs.next();
		}
		
		return o;
	} catch( e ) {
		throw new ArdeException( 'ArdeException.fromXml', "can't make ArdeException", 0, e );
	}
};

function ArdeTrace() {
	this.args = new Array();
}
ArdeTrace.fromXml = function( element ) {
	var o = new ArdeTrace();
	o.cls = ArdeXml.attribute( element, 'class', null );
	o.func = ArdeXml.attribute( element, 'function', null );
	o.type = ArdeXml.attribute( element, 'type', null );

	
	var fileElement = ArdeXml.element( element, 'file', null );
	if( fileElement != null ) {
		o.file = ArdeXml.strContent( fileElement );
		o.line = ArdeXml.intAttribute( fileElement, 'line', null );
	} else {
		o.file = null;
	}
	
	var argEs = new ArdeXmlElemIter( element, 'arg' );
	while( argEs.current ) {
		o.args.push( ArdeXml.strContent( argEs.current ) );
		argEs.next();
	}

	return o;
};




function ArdeXml() {}

ArdeXml.ELEMENT_NODE = 1;
ArdeXml.TEXT_NODE = 3;

ArdeXml.prototype.thisIsAClass = true;

ArdeXml.attribute = function( element, name, defaultValue ) {
	if( !ArdeXml.hasAttribute( element, name ) ) {
		if( typeof defaultValue == 'undefined' ) throw new ArdeException( '', "Element <"+element.tagName+"> must contain attribute '"+name+"'." );
		return defaultValue;
	}
	return element.getAttribute( name );
};

ArdeXml.strAttribute = function( element, name, defaultValue ) {
	return ArdeXml.attribute( element, name, defaultValue );
}

ArdeXml.hasAttribute = function ( element, name ) {
	if( !ardeBro.ie ) return element.hasAttribute( name );
	return element.getAttribute( name ) !== null;
};

ArdeXml.intAttribute = function( element, name, defaultValue ) {
	if( !ArdeXml.hasAttribute( element,  name ) ) {
		if( typeof defaultValue == 'undefined' ) throw new ArdeException( '', "Element <"+element.tagName+"> must contain attribute '"+name+"'." );
		return defaultValue;
	}
	var s = element.getAttribute( name );
	try {
		return parseInt( s );
	} catch( error ) {
		throw new ArdeException( '', "Attribute '"+name+"' in element <"+element.tagName+"> may only contain integer value, '"+s+"' is not valid." );
	}
};

ArdeXml.boolAttribute = function( element, name, defaultValue ) {
	if( !ArdeXml.hasAttribute( element,  name ) ) {
		if( typeof defaultValue == 'undefined' ) throw new ArdeException( '', "Element <"+element.tagName+"> must contain attribute '"+name+"'." );
		return defaultValue;
	}
	var s = element.getAttribute( name );
	if( s == 'true' ) return true;
	if( s == 'false' ) return false;
	throw new ArdeException( '', "Attribute '"+name+"' element <"+element.tagName+"> may only contain 'true' or 'false', value '"+s+"' is not valid." );
};

ArdeXml.strContent = function( element ) {
	if( element.firstChild == null ) return '';
	if( element.firstChild.nodeType != ArdeXml.TEXT_NODE || element.firstChild.nextSibling != null ) 
		throw new ArdeException( '', "Element <"+element.tagName+"> should contain text only" );
	return element.firstChild.nodeValue;
};

ArdeXml.intContent = function( element ) {
	var s = ArdeXml.strContent( element );
	try {
		return parseInt( s );
	} catch( error ) {
		throw new ArdeException( '', "Element <"+element.tagName+"> may only contain integer value '"+s+"' is not valid." );
	}
};

ArdeXml.floatContent = function( element ) {
	var s = ArdeXml.strContent( element );
	try {
		return parseFloat( s );
	} catch( error ) {
		throw new ArdeException( '', "Element <"+element.tagName+"> may only contain a floating point value '"+s+"' is not valid." );
	}
};

ArdeXml.boolContent = function( element ) {
	var s = ArdeXml.strContent( element );
	if( s == 'true' ) return true;
	if( s == 'false' ) return false;
	throw new ArdeException( '', "Element <"+element.tagName+"> may only contain 'true' or 'false', value '"+s+"' is not valid." );
};

ArdeXml.element = function( element, tagName, defaultValue ) {
	node = element.firstChild;
	while( node ) {
		if( node.nodeType == ArdeXml.ELEMENT_NODE && node.tagName == tagName ) return node;
		node = node.nextSibling;
	}
	if( typeof defaultValue == 'undefined' ) throw new ArdeException( '', "Element <"+element.tagName+"> must contain element <"+tagName+">" );
	return defaultValue;
};

ArdeXml.strElement = function( element, tagName, defaultValue ) {
	var def = ( typeof defaultValue == "undefined" ? "undefined" : null );
	e = ArdeXml.element( element, tagName, def );
	if( e == null ) return defaultValue;
	return ArdeXml.strContent( e );
};

ArdeXml.intElement = function( element, tagName, defaultValue ) {
	var def = ( typeof defaultValue == "undefined" ? "undefined" : null );
	e = ArdeXml.element( element, tagName, def );
	if( e == null ) return defaultValue;
	return ArdeXml.intContent( e );
};

ArdeXml.boolElement = function( element, tagName, defaultValue ) {
	var def = ( typeof defaultValue == "undefined" ? "undefined" : null );
	e = ArdeXml.element( element, tagName, def );
	if( e == null ) return defaultValue;
	return ArdeXml.boolContent( e );
};

ArdeXml.removeChildren = function( element ) {
	
	node = element.firstChild;
	while( node ) {
		nextNode = node.nextSibling;
		element.removeChild( node );
		node = nextNode;
		
	}
};

function ardeXmlObjectListClass( objectClass, objectTagName, withMore, withPosition, withTotal ) {
	
	if( typeof withMore == 'undefined' ) withMore = false;
	if( typeof withPosition == 'undefined' ) withPosition = false;
	if( typeof withTotal == 'undefined' ) withTotal = false;
	
	var listClass = function () {
		this.a = [];
		if( withPosition ) {
			this.p = [];
		}
	};
	
	
	
	listClass.fromXml = function ( element ) {
		var o = new listClass();
		if( withMore ) {
			o.more = ArdeXml.boolAttribute( element, 'more' );
		} else if( withTotal ) {
			o.total = ArdeXml.intAttribute( element, 'total' );
		}
		objectEs = new ArdeXmlElemIter( element, objectTagName );
		while( objectEs.current ) {
			o.a.push( objectClass.fromXml( objectEs.current ) );
			if( withPosition ) {
				o.p.push( ArdeXml.intAttribute( objectEs.current, 'pos' ) );
			}
			objectEs.next();
		}
		return o;
	};
	
	return listClass;
};

function ArdeXmlObjString () {}

ArdeXmlObjString.fromXml = function ( element ) {
	return ArdeXml.strContent( element );
};

function ArdeXmlObjInteger () {}

ArdeXmlObjInteger.fromXml = function ( element ) {
	return ArdeXml.intContent( element );
};

function ArdeXmlElemIter( topElement, tagName ) {
	this.tagName = tagName;
    this.current = ArdeXml.element( topElement , tagName, null );
}

ArdeXmlElemIter.prototype.current = null;
ArdeXmlElemIter.prototype.tagName = null;
    
ArdeXmlElemIter.prototype.next = function() {
    do {
        this.current = this.current.nextSibling;
        if( !this.current ) {
        	this.current = null;
        	return;
		}
        if( this.current.nodeType == ArdeXml.ELEMENT_NODE ) {
            if( this.current.tagName == this.tagName ) {
                return;
            }
        }
    } while( true );
};

function ardeMembersCount( obj ) {
	var j = 0;
	for( var i in obj ) {
		++j;
	}
	return j;
}

function ardeArrayContains( a, value ) {
	for( var i in a ) {
		if( a[i] == value ) return true;
	}
	return false;
}

function ardeByteSize( bytes ) {
	if( bytes < 1024 ) return bytes+' B';
	if( bytes < 1024*1024 ) return Math.round( bytes/(1024) )+' KB';
	return Math.round( bytes/(1024*1024) )+' MB';
}

function ardeAlert( s ) {
	alert( s );
	return false;
}

function ardeEscape( s ) {
	s = encodeURIComponent( s );

	s = s.replace( /\%20/g, "+" );
	
	return s;
}

function ardeUnescape( s ) {
	s = s.replace( /\+/g, "%20" );
	try {
		s = decodeURIComponent( s );
	} catch( e ) {
		s = decodeURIComponent( '[badly encoded url]' );
	}
	return s;
}

function ArdeUrlWriter( url ) {
	if( typeof url == 'undefined' ) url = '.';
	var urlParts = url.split( '?' );
	this.url = urlParts[0];
	this.params = {};
	if( typeof urlParts[1] != 'undefined' ) {
		this.addParams( urlParts[1] );
	}
}

ArdeUrlWriter.prototype.addParams = function ( params ) {
	var vars = params.split( '&' );
	for( var i in vars ) {
		varParts = vars[i].split( '=' );
		this.params[ ardeUnescape( varParts[0] ) ] = (typeof varParts[1] !== 'undefined' ) ? ardeUnescape( varParts[1] ) : '';
	}
}

ArdeUrlWriter.getCurrent = function () {
	return new ArdeUrlWriter( window.location.toString() );
}

ArdeUrlWriter.prototype.setAddress = function( url ) {
	this.url = url;
	return this;
};

ArdeUrlWriter.prototype.setParam = function ( name, value, def ) {
	if( typeof def == 'undefined' ) def = null;
	if( typeof this.params[ name ] != 'undefined' && this.params[ name ] !== null ) {
		if( def !== null && value == def ) {
			this.params[ name ] = null;
		} else {
			this.params[ name ] = value;
		}
	} else {
		if( def === null || value != def ) this.params[ name ] = value;
	}
	return this;
};

ArdeUrlWriter.prototype.go = function () {
	window.location = this.getUrl();
};

ArdeUrlWriter.prototype.getUrl = function () {
	ps = '';
	i = 0;
	
	for( var name in this.params ) {
		if( this.params[ name ] === null ) continue;
		ps += (i?'&':'')+ardeEscape( ardeEscape( name ) )+'='+ardeEscape( this.params[ name ] );
		++i;
	}
	
	s = this.url;
    
	if( ps != '' ) s += '?'+ps;

	return s;
};

ArdeUrlWriter.prototype.removeParam = function ( name ) {
	this.params[ name ] = null;
	return this;
};

function ardeSecondsString( s ) {

	res = new ArdeAppender( ' ' );
	
	m = Math.floor( s / 60 );
	s -= m * 60;
	h = Math.floor( m / 60 );
	m -= h * 60;
	if( d = Math.floor( h / 24 ) ) res.append( d+ardeLocale.text('d') );
	if( h -= d * 24 ) res.append( h+ardeLocale.text('h') );
	if( m ) res.append( m+ardeLocale.text('m') );
	if( s ) res.append( s+ardeLocale.text('s') );
	return ardeLocale.number( res.s );	
}

function ArdeAppender( separator ) {
	this.separator = separator;
	this.c = 0;
	this.s = '';
}

ArdeAppender.prototype.append = function( s ) {
	if( this.c ) this.s += this.separator;
	this.s += s;
	++this.c;
};

function ArdeLocale( id, defaultId, rightToLeft, digits, texts ) {
	this.id = id;
	this.defaultId = defaultId;
	this.rightToLeft = rightToLeft;
	this.digits = digits;
	this.texts = texts;
};
ArdeLocale.prototype.left = function () {
	if( this.rightToLeft ) return 'right';
	return 'left';
};
ArdeLocale.prototype.right = function () {
	if( this.rightToLeft ) return 'left';
	return 'right';
};
ArdeLocale.prototype.Left = function () {
	if( this.rightToLeft ) return 'Right';
	return 'Left';
};
ArdeLocale.prototype.Right = function () {
	if( this.rightToLeft ) return 'Left';
	return 'Right';
};
ArdeLocale.prototype.text = function( text, values ) {
	
	if( typeof this.texts[ text ] != 'undefined' ) {
		text = this.texts[ text ];
	}

	if( typeof values != 'undefined' ) {
		
		for( var key in values ) {
			
			var index = text.indexOf( '{'+key+'}' );
			if( index >= 0 ) {
				text = text.substr( 0, index )+values[ key ]+text.substr( index + key.length + 2 );
			}
		}
	}
	return this.number( text );
};
ArdeLocale.prototype.number = function( n ) {
	if( this.digits == ArdeLocale.DIGIT_ENGLISH ) return n;
			
	n = '' + n;

	o = '';
	for( var i = 0; i < n.length; ++i ) {
		if( n.charCodeAt(i) >= 0x30 && n.charCodeAt(i) <= 0x39 ) {
			
			if( this.digits == ArdeLocale.DIGIT_PERSIAN ) {
				o += ardeUcs4ToUtf8( 0x6f0 + n.charCodeAt(i) - 0x30 );
			} else {
				o += ardeUcs4ToUtf8( 0x660 + n.charCodeAt(i) - 0x30 );
			}
		} else {
			o += n.charAt(i);
		}
	}

	return o;
};
ArdeLocale.DIGIT_ENGLISH = 1;
ArdeLocale.DIGIT_PERSIAN = 2;
ArdeLocale.DIGIT_ARABIC = 3;

ardeLocale = new ArdeLocale( false, ArdeLocale.DIGIT_ENGLISH, {} );

function ardeUcs4ToUtf8( i ) {
	return String.fromCharCode( i );
}

function ardeLeftPad( str, pad, length ) {
	str = String( str );
	pad = String( pad );
	while( str.length < length ) {
		str = pad+str;
	}
	return str;
}

function ArdeArray() {
	this.ardeKeys = [];
	for( var i = 0; i < arguments.length; i += 2 ) {
		this[ arguments[ i ] ] = arguments[ i+1 ];
		this.ardeKeys.push( arguments[ i ] );
	}
}

ArdeArray.prototype.ardePush = function ( key, value ) {
	if( typeof value === 'undefined' ) {
		this[ this.ardeKeys.length ] = value;
		this.ardeKeys.push( key );
	}
	this[ key ] = value;
	this.ardeKeys.push( key );
};

ArdeArray.prototype.ardeLength = function () {
	return this.ardeKeys.length;	
};

ArdeArray.prototype.ardeValueAt = function ( pos ) {
	return this[ this.ardeKeyAt( pos ) ];
};

ArdeArray.prototype.ardeKeyAt = function ( pos ) {
	return this.ardeKeys[ pos ];
}



