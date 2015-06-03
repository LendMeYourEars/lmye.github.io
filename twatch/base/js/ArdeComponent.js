
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
    
function ArdeBrowser() {
	this.opera=false;
	this.ie=false;
	this.safari=false;
	this.gecko=false;
	this.khtml=false;
	
	if(navigator.userAgent.match(/khtml/i)) {
		this.khtml=true;
	}
	
	if(navigator.userAgent.match(/gecko/i)) {
		this.gecko=true;
	}
	
	if(navigator.userAgent.match(/applewebkit/i)) {	
		this.webkit=true;
	}
	
	var res=navigator.userAgent.match(/Opera(\s|\/)([0-9\.]+)/);
	if(res) {
		this.opera=parseFloat(res[2]);
		return;
	}
	var res=navigator.userAgent.match(/Safari\/([0-9]+\.?[0-9]*)/);
	if(res) {
		this.safari=parseFloat(res[1]);
		this.khtml=true;
		return;
	}

	
	if(document.all) {
		var res=navigator.userAgent.match(/MSIE\s([0-9\.]+)/);
		if(res) {
			this.ie=parseFloat(res[1]);
			return;
		}
	}
	
}




ardeBro=new ArdeBrowser();

function ardeScrollTop() {
	if( ardeBro.ie ) {
		return document.documentElement.scrollTop;
	} else {
		return window.scrollY;
	}
}
function ardeScrollLeft() {
	if( ardeBro.ie ) {
		var x = document.documentElement.scrollLeft;
	} else {
		var x = window.scrollX;
	}
	if( ardeLocale.rightToLeft ) {
		x = ardeDocumentWidth() - ardeClientWidth() + x;
	}
	
	return x;
}

function ardeScrollRight() {
	if( ardeBro.ie ) {
		var x = document.documentElement.scrollLeft;
	} else {
		var x = window.scrollX;
	}
	return -x;
}

function ardeClientWidth () {
		return document.documentElement.clientWidth;
	if( ardeBro.gecko ) {
		return document.documentElement.clientWidth;
	}
	return window.innerWidth;
}

function ardeClientHeight () {
		return document.documentElement.clientHeight;
	if( ardeBro.gecko ) {
		return document.documentElement.clientHeight;
	}
	return window.innerHeight;
}

function ardeDocumentWidth () {
	return document.documentElement.scrollWidth;
}

function ardeDocumentHeight () {
	return document.documentElement.scrollHeight;
}



function ArdeComponent( tagName ) {
	this.element = document.createElement( tagName );
	this.displayMode = ''; 
}

ArdeComponent.prototype.forceRedraw = function () {
	
	if( ardeBro.opera ) {
		setTimeout( function() {
			var tttt = new ArdeComponent( 'span' ).append( ardeT( 'asdf' ) ).appendTo( document.body );
			tttt.remove();
		}, 5 );
	}
}

ArdeComponent.prototype.element = null; 

ArdeComponent.prototype.clone = function() {
	return new ArdeComponent( this.element.tagName );
};

ArdeComponent.prototype.ardeGetWrappedNode = function() {
	return this.element;
};

function ardeGetWrappedNode( wrapper ) {
	if( wrapper != null && wrapper.ardeGetWrappedNode ) var res = wrapper.ardeGetWrappedNode();
	else var res = wrapper;
	return res;
}

ArdeComponent.prototype.setDisabled = function( disabled ) {
	this.element.disabled = disabled;
	return this;
};


ArdeComponent.prototype.setDisplay = function( display ) {
	if( display ) this.element.style.display = this.displayMode;
	else this.element.style.display = 'none';
	return this;
};

ArdeComponent.prototype.switchDisplay = function() {
	if( this.isDisplaying() ) this.element.style.display = 'none';
	else this.element.style.display = this.displayMode;
	return this;
};

ArdeComponent.prototype.isDisplaying = function() {
	if( this.element.style.display == 'none' ) return false;
	return true;
};

ArdeComponent.prototype.absLeft = function() {
	if( typeof this.element.getBoundingClientRect != 'undefined' ) {
		return this.element.getBoundingClientRect().left + ardeScrollLeft();
	}
	var x = 0;
	var element = this.element;
	do {
		x += element.offsetLeft;
	} while( element = element.offsetParent );
	return x;
};

ArdeComponent.prototype.absRight = function() {
	return ardeDocumentWidth() - this.absLeft() - this.getWidth();
};

ArdeComponent.prototype.absTop = function() {
	if( typeof this.element.getBoundingClientRect != 'undefined' ) {
		return this.element.getBoundingClientRect().top + ardeScrollTop();
	}
	var y = 0;
	var element = this.element;
	
	do {
		y += element.offsetLeft;
	} while( element = element.offsetParent );
	return y;
};

ArdeComponent.prototype.setDisplayMode = function( displayMode ) {
	this.displayMode = displayMode;
	if( this.isDisplaying() ) this.element.style.display = displayMode;
	return this;
};

ArdeComponent.prototype.getWidth = function() {
	return this.element.offsetWidth;
};

ArdeComponent.prototype.getHeight = function() {
	return this.element.offsetHeight;
};

ArdeComponent.prototype.setWidth = function( width ) {
	var compensate = this.horizontalPaddingWidth();
	compensate += this.horizontalBorderWidth();
	correctWidth = width - compensate;
	if( correctWidth < 0 ) correctWidth = 0;
	this.style( 'width', (correctWidth)+'px' );
};

ArdeComponent.prototype.setHeight = function( height ) {
	var compensate = this.verticalPaddingHeight();
	compensate += this.verticalBorderHeight();
	correctHeight = height - compensate;
	if( correctHeight < 0 ) correctHeight = 0;
	this.style( 'height', (correctHeight)+'px' );
};

ArdeComponent.prototype.verticalPaddingHeight = function () {
	return parseInt( this.computedStyle( 'paddingTop' ) ) + parseInt( this.computedStyle( 'paddingBottom' ) );
};

ArdeComponent.prototype.verticalBorderHeight = function () {
	return parseInt( this.computedStyle( 'borderBottomWidth' ) ) + parseInt( this.computedStyle( 'borderTopWidth' ) );
};

ArdeComponent.prototype.horizontalPaddingWidth = function () {
	return parseInt( this.computedStyle( 'paddingLeft' ) ) + parseInt( this.computedStyle( 'paddingRight' ) );
};

ArdeComponent.prototype.horizontalBorderWidth = function () {
	return parseInt( this.computedStyle( 'borderLeftWidth' ) ) + parseInt( this.computedStyle( 'borderRightWidth' ) );
};

ArdeComponent.prototype.startInvisibleRender = function() {
	this.setVisible( false );
	this.style( 'position', 'absolute' );
	this.insertInBody();
};

ArdeComponent.prototype.endInvisibleRender = function() {
	this.removeFromBody();
	this.style( 'position', '' );
	this.setVisible( true );
};

ArdeComponent.prototype.setClickable = function( clickable ) {
	if(clickable)
		this.setCursor('pointer');
	else
		this.setCursor();
	return this;
};

ArdeComponent.prototype.setCursor = function ( cursor ) {
	if( typeof cursor == 'undefined' ) cursor = 'auto';
	else if( cursor == 'pointer' && ( ardeBro.ie || ardeBro.opera ) ) cursor = 'hand';
	this.element.style.cursor = cursor;
	return this;
};

ArdeComponent.prototype.setOpacity = function( value ) {
	this.opacity = value;
	if( ardeBro.ie ) {
		this.style( 'filter', 'alpha(opacity='+( value*100 )+')' );
	} else {
		this.style( 'opacity', value );
	}
	return this;
};

ArdeComponent.prototype.getOpacity = function() {
	if( typeof this.opacity == 'undefined' ) {
		return 1;
	} else {
		return this.opacity;
	}
};

ArdeComponent.prototype.fadeout = function( duration, finishCallback ) {
	var frameDuration = 30;
	this.setOpacity( 1 );
	
	var self = this;
	var theFunction = function() {
		if( self.getOpacity() > 0 ) {
			var newOpacity = self.getOpacity() - frameDuration / duration;
			if( newOpacity < 0 ) newOpacity = 0;
			self.setOpacity( newOpacity );
			setTimeout( theFunction, frameDuration );
		} else {
			if( typeof finishCallback != 'undefined' ) finishCallback();
		}
	};
	setTimeout( theFunction, frameDuration );
};

ArdeComponent.prototype.shrink = function( duration, finishCallback ) {
	var frameDuration = 30;
	var origHeight = this.getHeight();
	this.style( 'overflow', 'hidden' );
	
	var self = this;
	var theFunction = function() {
		if( self.getHeight() > self.verticalBorderHeight() + self.verticalPaddingHeight() ) {
			var d = Math.round( origHeight * ( frameDuration / duration ) );
			var cHeight = self.getHeight();
			var newHeight = cHeight - d;
			if( newHeight < 0 ) newHeight = 0;
			self.setHeight( newHeight );
			setTimeout( theFunction, frameDuration );
		} else {
			if( typeof finishCallback != 'undefined' ) finishCallback();
			self.style( 'overflow', '' );
		}
	};
	setTimeout( theFunction, frameDuration );
};

ArdeComponent.prototype.fadeoutDisplay = function( duration, finishCallback ) {
	var self = this;
	var callback = function () {
		self.setDisplay( false );
		self.setOpacity( 1 );
		if( typeof finishCallback != 'undefined' ) finishCallback();
	};
	this.fadeout( duration, callback );
};

ArdeComponent.prototype.fadeoutVisibility = function( duration, finishCallback ) {
	var self = this;
	var callback = function () {
		self.setVisible( false );
		self.setOpacity( 1 );
		if( typeof finishCallback != 'undefined' ) finishCallback();
	};
	this.fadeout( duration, callback );
};

ArdeComponent.prototype.setVisible = function( visible ) {
	this.element.style.visibility = visible?'visible':'hidden';
	this.visible = visible;
};
ArdeComponent.prototype.switchVisibility = function() {
	if( this.visible == null ) this.visible = true;
	this.setVisible( !this.visible );
};

ArdeComponent.prototype.setPos = function( x, y ) {

	this.style( 'left', x+'px' );
	this.style( 'top', y+'px' );
};

ArdeComponent.prototype.clean = function() {
	ArdeXml.removeChildren( this.element );
};

ArdeComponent.prototype.clear = ArdeComponent.prototype.clean;

ArdeComponent.prototype.insertInBody = function() {
	document.body.insertBefore( this.element, document.body.firstChild );
};

ArdeComponent.prototype.removeFromBody = function() {
	document.body.removeChild( this.element );
};

ArdeComponent.currentScriptElement = function( element ) {
	if( !element ) element = document.body;
	if( element.lastChild && element.lastChild.nodeType == ArdeXml.ELEMENT_NODE ) return ArdeComponent.currentScriptElement( element.lastChild );
	return element;
};

ArdeComponent.prototype.append = function( child ) {
	child = ardeGetWrappedNode( child );
	this.element.appendChild( child );

	return this;
};

ArdeComponent.prototype.removeChild = function( child ) {
	child = ardeGetWrappedNode( child );
	this.element.removeChild( child );
	return this;
};

ArdeComponent.prototype.remove = function( fff ) {
	if( typeof fff != 'undefined' ) throw "remove doesn't accept parameters did you mean removeChild()";
	this.element.parentNode.removeChild( this.element );
};

ArdeComponent.prototype.appendTo = function( parent ) {
	parent = ardeGetWrappedNode( parent );
	parent.appendChild( this.element );
	
	return this;
};

ArdeComponent.prototype.insertBefore = function( child, reference ) {
	child = ardeGetWrappedNode( child );
	reference = ardeGetWrappedNode( reference );
	this.element.insertBefore( child, reference );
};

ArdeComponent.prototype.insertAfter = function( child, reference ) {
	if( reference == null ) return this.insertFirstChild( child );
	child = ardeGetWrappedNode( child );
	reference = ardeGetWrappedNode( reference );
	if( !reference.nextSibling ) this.append( child );
	this.element.insertBefore( child, reference.nextSibling );
};


ArdeComponent.prototype.insertFirstChild = function( child ) {
	child = ardeGetWrappedNode( child );
	this.insertBefore( child, this.element.firstChild );	
	return this;
};

ArdeComponent.prototype.nextSibling = function() {
	return this.element.nextSibling;
};

ArdeComponent.prototype.cls = function( cls ) {
	this.element.className = cls;
	return this;
};

ArdeComponent.prototype.addClass = function( cls ) {
	if( this.element.className == '' ) this.element.className = cls;
	else this.element.className += ' '+cls;
};

ArdeComponent.prototype.removeClass = function( cls ) {
	var classes = this.element.className.split( ' ' );
	var o = '';
	for( var i in classes ) {
		if( classes[i] != cls ) o += (i==0?'':' ')+classes[i];
	}
	this.element.className = o;
};

ArdeComponent.prototype.style = function( name, value ) {
	this.element.style[ name ] = value;
	return this;
};

ArdeComponent.prototype.setFloat = function( flt ) {
	if(ardeBro.ie) this.element.style.styleFloat = flt;
	else this.element.style.cssFloat = flt;
	return this;
};

ArdeComponent.prototype.setLocaleFloat = function( flt ) {
	if( ardeLocale.rightToLeft ) {
		if( flt == 'right' ) flt = 'left';
		else if( flt == 'left' ) flt = 'right';
	}
	return this.setFloat( flt );
};


ArdeComponent.prototype.attr = function( name, value ) {
	this.element.setAttribute( name, value );
	return this;
};

ArdeComponent.prototype.insert = function() {
	scriptElement = ArdeComponent.currentScriptElement();
	scriptElement.parentNode.insertBefore( this.element, scriptElement );
	return this;
};

ArdeComponent.prototype.replace = function( replacement ) {
	replacement = ardeGetWrappedNode( replacement );
	this.element.parentNode.replaceChild( replacement, this.element );
};

ArdeComponent.prototype.computedStyle = function( style ) {
	return ardeComputedStyle( this.element, style );
};

function ArdeExceptionComponent( e ) {
	this.ArdeComponent( 'div' );
	if( !(e instanceof ArdeException) ) {
		e = ArdeException.fromJsError( e );
	}
	ttttt = true;
	this.cls( 'arde_exception '+ArdeException.typeStrings[ e.type ] );
	var head = ardeE( 'div' ).cls( 'head' );
	if( e.trace.length ) {
		var headA = ardeE( 'a' ).cls( 'button' );
		var rnd = 999 + Math.floor( Math.random() * 99999999 );
		headA.attr( 'id', 'arde_exception_b'+rnd );
		headA.attr( 'href', '#' );
		headA.n.onclick = function () { ardeExceptionExpand( rnd ); return false; };
		headA.append( ardeT( '+' ) );
		head.append( headA );
	}
	head.append( ardeT( ' '+e.cls ) );
	this.append( head );
	
	if( e.sender != '' || e.message != null ) {
		var message = ardeE( 'div' );
		message.cls( 'message' );
		msg = e.message==null?'':e.message;
		if( e.sender != '' ) msg = e.sender+': '+msg;
		message.append( ardeT( msg ) );
		this.append( message );
	}
	
	if( e.file != '' || e.line != 0 ) {
		var underMessage = ardeE( 'div' );
		underMessage.cls( 'under_message' );
		if( e.file != '' ) {
			underMessage.append( ardeT( ' File: '+e.file ) );
		}
		if( e.line != 0 ) {
			underMessage.append( ardeT( ' Line: '+e.line ) );
		}
		this.append( underMessage );
	}
	for( var i in e.extras ) {
		var extraD = ardeE( 'div' );
		extraD.cls( 'extra' );
		extraD.append( ardeT( e.extras[i] ) );
		this.append( extraD );
	}
	if( e.trace.length ) {
		var tracesD = ardeE( 'div' );
		tracesD.cls( 'extended' );
		tracesD.attr( 'id', 'arde_exception_p'+rnd );
		for( var i = 0; i < e.trace.length; ++i ) {
			var traceD = ardeE( 'div' );
			traceD.cls( 'trace' );
			if( e.trace[i].func != null ) {
				var funcD = ardeE( 'div' );
				if( e.trace[i].cls != null ) {
					var clsS = ardeE( 'span' );
					clsS.cls( 'cls' );
					clsS.append( ardeT( e.trace[i].cls ) );
					funcD.append( clsS );
					if( e.trace[i].type != null ) {
						funcD.append( ardeT( e.trace[i].type) );
					}
				}
				funcD.append( ardeT( e.trace[i].func+'( ' ) );
				for( var j = 0; j < e.trace[i].args.length; ++j ) {
					var argS = ardeE( 'span' );
					argS.cls( 'arg' );
					argS.append( ardeT( e.trace[i].args[j] ) );
					if( j ) funcD.append( ardeT( ', ') );
					funcD.append( argS );
				}
				funcD.append( ardeT( ' )' ) );
				traceD.append( funcD );
			}
			tracesD.append( traceD );
		}
		this.append( tracesD );
	}
	if( e.child != null ) {
		var child = new ArdeExceptionComponent( e.child );
		this.append( child );
	}
}
ArdeClass.extend( ArdeExceptionComponent, ArdeComponent );


function ArdeTd() {
	this.ArdeComponent( 'td' );
}

ArdeTd.prototype.setColSpan = function ( colSpan ) {
	this.element.colSpan = colSpan;
	return this;
};

ArdeTd.prototype.setRowSpan = function ( rowSpan ) {
	this.element.rowSpan = rowSpan;
	return this;
};

ArdeClass.extend( ArdeTd, ArdeComponent );

function ArdeNodeHolder( node ) {
	this.n = node;
}



ArdeNodeHolder.prototype.n = null;

ArdeNodeHolder.prototype.ardeGetWrappedNode = function() {
	return this.n;
};

ArdeNodeHolder.prototype.append = function( node ) {
	node = ardeGetWrappedNode( node );
	this.n.appendChild( node );
	return this;
};

ArdeNodeHolder.prototype.appendTo = function( node ) {
	node = ardeGetWrappedNode( node );
	node.appendChild( this.n );
	return this;
};

ArdeNodeHolder.prototype.cls = function( className ) {
	this.n.className = className;
	return this;
};

ArdeNodeHolder.prototype.style = function( name, value ) {
	this.n.style[ name ] = value;
	return this;
};

ArdeNodeHolder.prototype.attr = function( name, value ) {
	this.n.setAttribute( name, value );
	return this;
};

ArdeNodeHolder.prototype.setText = function( text ) {
	this.n.nodeValue = text;
	return this;
};

function ardeE( tagName ) {
	return new ArdeNodeHolder( document.createElement( tagName ) );
}

function ardeT( str ) {
	return new ArdeNodeHolder( document.createTextNode( str ) );
}

function ArdeImg( src, altText, width, height ) {
	this.ArdeComponent( 'img' );
	this.attr( 'src', src );
	if( typeof altText != 'undefined' && altText !== null ) {
		this.attr( 'alt', altText );
		this.attr( 'title', altText );
	}
	if( typeof width != 'undefined' && width !== null ) {
		this.attr( 'width', width );
	}
	if( typeof height != 'undefined' && height !== null ) {
		this.attr( 'height', height );
	}
}
ArdeClass.extend( ArdeImg, ArdeComponent );

function ArdeImgButton( src, altText, width, height ) {
	this.oldOnclick = function () {};
	this.ArdeImg( src, altText, width, height );
	this.setClickable( true );
}

ArdeImgButton.prototype.setText = function ( text ) {};

ArdeImgButton.prototype.setDisabled = function ( disabled ) {
	
	if( disabled ) this.oldOnclick = this.element.onclick;
	else this.element.onclick = this.oldOnclick;

};

ArdeClass.extend( ArdeImgButton, ArdeImg );

function ArdeOption( text, value ) {
	if( typeof value == 'undefined' ) {
		value = text;
	}
	this.ArdeComponent( 'option' );
	this.attr( 'value', value );
	this.append( ardeT( text ) );
	
}

ArdeOption.prototype.setSelected = function( selected ) {
	this.element.selected = selected;
	return this;
};

ArdeClass.extend( ArdeOption, ArdeComponent );

function ArdeSelect( size ) {
	this.hasDummyOption = false;
	this.ArdeComponent( 'select' );
	if( typeof size != 'undefined' && size !== null ) {
		this.setSize( size );
	}
}
ArdeSelect.prototype.setSize = function ( size ) {
	this.element.size = size;
	return this;
}

ArdeSelect.prototype.selectedOption = function() {
	if( this.hasDummyOption && this.element.selectedIndex == 0 ) return null;
	if( this.element.selectedIndex < 0 ) return null;
	return this.element.options[ this.element.selectedIndex ];
};

ArdeSelect.prototype.addDummyOption = function( title ) {
	this.append( ardeE( 'option' ).append( ardeT( title ) ) );
	this.hasDummyOption = true;
	return this;
};

ArdeSelect.prototype.clear = function() {
	while( this.element.options.length ) this.element.options[0] = null;
	this.hasDummyOption = false;
};

ArdeSelect.prototype.clearItems = function() {
	if( this.hasDummyOption ) {
		while( this.element.options.length > 1 ) this.element.options[1] = null;
	} else {
		while( this.element.options.length ) this.element.options[0] = null;
	}
}

ArdeClass.extend( ArdeSelect, ArdeComponent );

function ArdeTable() {
	this.ArdeComponent( 'table' );
	this.setCellSpacing( '0' ).setCellPadding( '0' ).setBorder( '0' );
}

ArdeTable.prototype.setCellPadding = function ( cellPadding ) {
	this.element.cellPadding = cellPadding;
	return this;
};

ArdeTable.prototype.setCellSpacing = function ( cellSpacing ) {
	this.element.cellSpacing = cellSpacing;
	return this;
};

ArdeTable.prototype.setBorder = function ( border ) {
	this.element.border = border;
	return this;
};

ArdeClass.extend( ArdeTable, ArdeComponent );



function ArdeButton( buttonText ) {
	this.ArdeComponent( 'input' );
	this.element.type = 'button';
	this.element.value = buttonText;
}
ArdeButton.prototype.setText = function ( text ) {
	this.element.value = text;
}
ArdeClass.extend( ArdeButton, ArdeComponent );

function ArdeTextArea( text ) {
	if( typeof text == 'undefined' ) text = '';
	this.ArdeComponent( 'textarea' );
	this.element.value = text;
}
ArdeTextArea.prototype.setCols = function( cols ) {
	this.element.cols = cols;
	return this;
}
ArdeClass.extend( ArdeTextArea, ArdeComponent );

function ArdeInput( text ) {
	if( typeof text == 'undefined' ) text = '';
	this.ArdeComponent( 'input' );
	this.element.value = text;
}

ArdeInput.prototype.setValue = function( value ) {
	this.element.value = value;
};

ArdeInput.prototype.getValue = function() {
	return this.element.value;
};

ArdeClass.extend( ArdeInput, ArdeComponent );

function ArdeFlash( movie, width, height, align, scale, wMode, flashVars ) {
	
	this.ArdeComponent( 'object' );
	
	
	
	if( ardeBro.ie ) {
		
		var span = new ArdeComponent( 'span' );
		var s = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=10,0,0,0">';

			s += '<param name="movie" value="'+movie+'" />';
			s += '<param name="salign" value="'+align+'" />';
			s += '<param name="allowScriptAccess" value="always" />';
			if( scale == false ) {
				s += '<param name="scale" value="noscale" />';
			}
			if( typeof wMode != 'undefined' && wMode !== null ) {
				s += '<param name="wmode" value="'+wMode+'" />';
			}
		
			s += '<param name="flashVars" value="'+flashVars+'" />';
		s += '</object>';
		span.element.innerHTML = s;
		this.element = span.element.firstChild;
		this.append( ardeE( 'param' ).attr( 'name', 'movie' ).attr( 'value', movie ) );
		
	} else {
		
		this.append( ardeE( 'param' ).attr( 'name', 'allowScriptAccess' ).attr( 'value', 'always' ) );
		
		this.attr( 'type', 'application/x-shockwave-flash' );
		
		this.attr( 'data', movie );
		
	}
	
	if( !ardeBro.ie ) {
		if( typeof flashVars != 'undefined' && flashVars !== null ) {
			this.setFlashVars( flashVars );
		}
		
		if( typeof scale == 'undefined' ) scale = true;
		if( typeof align == 'undefined' ) align = 'tl';
		
		if( !ardeBro.gecko ) {
			
			if( scale == false ) {
				this.setScale( 'noscale' );
			}
			
			this.setSAlign( align );
			
		} else {
			
			this.setSAlign( align );
			
			if( scale == false ) {
				this.setScale( 'noscale' );
			}
	
		}
		
		if( typeof wMode != 'undefined' && wMode !== null ) {
			this.setWMode( wMode );
		}
	}
		
	if( typeof width != 'undefined' && width !== null ) { 
		this.style( 'width', width );
	}
	if( typeof height != 'undefined' && height !== null ) {
		this.style( 'height', height );
	}
	
}

ArdeFlash.prototype.setFlashVars = function( flashVars ) {
	if( !ardeBro.gecko ) {
		this.append( ardeE( 'param' ).attr( 'name', 'FlashVars' ).attr( 'value', flashVars ) );
	} else {
		this.attr( 'flashVars', flashVars );
	}
};

ArdeFlash.prototype.setWMode = function( wMode ) {
	if( !ardeBro.gecko ) {
		this.append( ardeE( 'param' ).attr( 'name', 'wmode' ).attr( 'value', wMode ) );
	} else {
		this.attr( 'wmode', wMode );
	}
};

ArdeFlash.prototype.setSAlign = function( sAlign ) {
	if( !ardeBro.gecko ) {
		this.append( ardeE( 'param' ).attr( 'name', 'salign' ).attr( 'value', sAlign ) );
	} else {
		this.attr( 'salign', sAlign );
	}
}

ArdeFlash.prototype.setScale = function( scale ) {
	if( !ardeBro.gecko ) {
		this.append( ardeE( 'param' ).attr( 'name', 'scale' ).attr( 'value', scale ) );
	} else {
		this.attr( 'scale', scale );
	}
}

ArdeFlash.prototype.resize = function( width, height ) {
	this.style( 'width', width );
	this.style( 'height', height );
	
	this.forceRedraw();
}



ArdeFlash.prototype.fixMouseEventsBug = function() {
	if( ardeBro.ie ) return;
	var self = this;
	this.style( 'border', '0px solid #000' );
	this.bugFixState = false;
	this.element.onmouseover = function () {
		self.style( 'borderColor', self.bugFixState?'#f00':'#ff0' );
		self.bugFixState = !self.bugFixState;
	}
}
ArdeClass.extend( ArdeFlash, ArdeComponent );

function ArdeCheckBox( checked ) {
	this.ArdeComponent( 'input' );
	this.element.type = 'checkbox';
	this.setChecked( checked );
}

ArdeCheckBox.prototype.setChecked = function ( checked ) {
	this.element.checked = checked;
	this.element.defaultChecked = checked;
};

ArdeClass.extend( ArdeCheckBox, ArdeComponent );

function ArdeClipboardCopyButton( buttonText, str ) {
	this.ArdeFlash( baseUrl+'fl/set_clipboard.swf', '150px', '25px', null, true, 'transparent', 'button_text='+ardeEscape( buttonText )+'&str='+ardeEscape( str ) );
}
ArdeClass.extend( ArdeClipboardCopyButton, ArdeFlash );

function ArdeRequestIcon() {
	this.ArdeComponent( 'span' );
	
	this.ArdeStdRequester();
	
	this.silent = false;
	this.showOk = true;
	
	this.dummyIcon = new ArdeImg( baseUrl+'img/dummy.gif' );
	this.dummyIcon.attr( 'width', '16' ).attr( 'height', '16' );
	this.waitIcon = new ArdeImg( baseUrl+'img/wait.gif' ).attr( 'width', '16' ).attr( 'height', '16' );
	this.errorIcon = new ArdeImg( baseUrl+'img/error.gif' ).attr( 'width', '16' ).attr( 'height', '16' );
	this.errorIcon.setClickable( true );
	this.errorIcon.element.onclick = ArdeNotice.switchDisplay;
	this.successIcon = new ArdeImg( baseUrl+'img/ok.gif' ).attr( 'width', '16' ).attr( 'height', '16' );
	
	this.waitIcon.style( 'position', 'absolute' ).setDisplay( false );
	this.errorIcon.style( 'position', 'absolute' ).setDisplay( false );
	this.successIcon.style( 'position', 'absolute' ).setDisplay( false );
	
	var span = ardeE( 'span' ).appendTo( this );
	span.style( 'verticalAlign', '-3px' );
	span.style( 'display', 'inline-block' );
	span.append( this.errorIcon ).append( this.successIcon ).append( this.waitIcon ).append( this.dummyIcon );
};

ArdeRequestIcon.prototype.setStandardCallbacks = function ( obj, name ) {
	if( typeof obj[ name+'Clicked' ] != 'undefined' ) {
		this.onclick = function () { obj[ name+'Clicked' ](); };
	}
	if( typeof obj[ name+'Confirmed' ] != 'undefined' ) {
		this.afterResultReceived = function ( result ) { obj[ name+'Confirmed' ]( result ); };
	}
};

ArdeRequestIcon.prototype.request = function( url, params, resultClass ) {
	this.waitIcon.setDisplay( true );
	this.errorIcon.setDisplay( false );
	this.ArdeStdRequester_request( url, params, resultClass );
};

ArdeRequestIcon.prototype.receive = function( xmlRoot ) {
	this.ArdeStdRequester_receive( xmlRoot );
};

ArdeRequestIcon.prototype.somethingReceived = function() {
	this.waitIcon.setDisplay( false );
};

ArdeRequestIcon.prototype.errorReceived = function( errors ) {
	this.errorIcon.setDisplay( true );
	notice = ArdeNotice.getCleanElement();
	
	for( var i in errors ) {
		try {
			errorC = new ArdeExceptionComponent( errors[i] );
		} catch( e ) {
			alert( 'error making error component: '+e );
		}
		
		notice.append( errorC );
	}
	if( !this.silent ) {
		ArdeNotice.show();
	}
};

ArdeRequestIcon.okFadeOutAfter = 1500;
ArdeRequestIcon.okFadeOutDuration = 1000;

ArdeRequestIcon.prototype.resultReceived = function( result ) {
	if( this.showOk ) {
		this.ok();
	}
	this.afterResultReceived( result );
};

ArdeRequestIcon.prototype.setShowOk = function( showOk ) {
	this.showOk = showOk;
	return this;
};

ArdeRequestIcon.prototype.ok = function () {
	this.successIcon.setDisplay( true );
	var self = this;
	setTimeout( function() { self.successIcon.fadeoutDisplay( ArdeRequestIcon.okFadeOutDuration ); }, ArdeRequestIcon.okFadeOutAfter );
};

ArdeRequestIcon.prototype.afterResultReceived = function( result ) {};

ArdeClass.extend( ArdeRequestIcon, ArdeComponent );
ArdeClass.extend( ArdeRequestIcon, ArdeStdRequester );



function ArdeRequestButton( buttonText, confirmationText, waitingText, iconsOnLeft, theButton ) {
	
	this.ArdeRequestIcon();
	
	if( typeof waitingText == 'undefined' || waitingText == null ) {
		this.waitingText = 'Please Wait...';
	} else {
		this.waitingText = waitingText;
	}
	
	
	
	if( typeof confirmationText == 'undefined' ) {
		this.confirmationText = null;
	} else {
		this.confirmationText = confirmationText;
	}
	if( this.confirmationText == null ) this.confirm = false;
	else this.confirm = true;
	
	if( typeof iconsOnLeft == 'undefined' ) {
		iconsOnLeft = false;
	}
	
	if( typeof theButton == 'undefined' ) {
		this.buttonText = buttonText;
	
		this.button = new ArdeButton( buttonText );
		
		this.button.startInvisibleRender();
		this.button.element.value = this.waitingText;
		w1 = this.button.getWidth();
		this.button.element.value = this.buttonText;
		w2 = this.button.getWidth();
		this.button.style( 'width', Math.max( w1, w2 )+'px' );
		this.button.endInvisibleRender();
		
		this.button.style( 'position', '' );
		this.button.setVisible( true );
	} else {
		this.button = theButton;
	}

	var self = this;
	this.button.element.onclick = function() {
		if( self.confirm ) {
			if( self.confirmationText != null ) {
				var t = self.confirmationText;
			} else { 
				var t = 'Are You Sure?';
			}
			if( confirm( t ) ) self.onclick();
		} else {
			self.onclick();
		}
	};
	
	if( iconsOnLeft ) {
		this.append( ardeE( 'span' ).append( this.button ) );
	} else {
		this.insertFirstChild( ardeE( 'span' ).append( this.button ) );
	}
	
}

ArdeRequestButton.prototype.onclick = function() {};

ArdeRequestButton.prototype.setCritical = function( critical ) {
	this.button.cls( critical?'critical':'' );
	this.confirm = critical;
	return this;
};

ArdeRequestButton.prototype.request = function( url, params, resultClass ) {
	this.button.setDisabled( true );
	this.button.setText( this.waitingText );
	this.ArdeRequestIcon_request( url, params, resultClass );
};

ArdeRequestButton.prototype.somethingReceived = function () {
	this.button.setDisabled( false );
	this.button.setText( this.buttonText );
	this.ArdeRequestIcon_somethingReceived();
};

ArdeClass.extend( ArdeRequestButton, ArdeRequestIcon );

function ArdeRequestImgButton( imageUrl, altText, confirmationText, iconsOnLeft ) {
	var button = new ArdeImgButton( imageUrl, altText );
	this.ArdeRequestButton( '', confirmationText, '', iconsOnLeft, button );
}
ArdeClass.extend( ArdeRequestImgButton, ArdeRequestButton );

function ArdeRequestLabel( text ) {
	
	this.ArdeRequestIcon();
	
	this.label = new ArdeComponent( 'span' ).append( ardeT( text ) );
	this.label.setVisible( false );
	
	this.insertFirstChild( this.label );
	
}

ArdeRequestLabel.prototype.request = function( url, params, resultClass ) {
	this.label.setVisible( true );
	this.ArdeRequestIcon_request( url, params, resultClass );
};

ArdeRequestLabel.prototype.resultReceived = function( result ) {
	var self = this;
	setTimeout( function() { self.label.fadeoutVisibility( ArdeRequestIcon.okFadeOutDuration ); }, ArdeRequestIcon.okFadeOutAfter );
	this.ArdeRequestIcon_resultReceived( result );
};

ArdeClass.extend( ArdeRequestLabel, ArdeRequestIcon );

function ArdePassiveList( ordered, allowDuplicates, withDummyOption, deleteButtonOnTop ) {
	
	if( typeof ordered == 'undefined' ) this.ordered = false;
	else this.ordered = true;
	
	if( typeof allowDuplicates == 'undefined' ) this.allowDuplicates = false;
	else this.allowDuplicates = allowDuplicates;
	
	if( typeof withDummyOption == 'undefined' ) this.withDummyOption = false;
	else this.withDummyOption = withDummyOption;
	
	if( typeof deleteButtonOnTop == 'undefined' ) this.deleteButtonOnTop = true;
	else this.deleteButtonOnTop = deleteButtonOnTop;
	
	this.ArdeComponent( 'span' );
	
	this.select = new ArdeComponent( 'select' );
	if( this.withDummyOption ) this.select.append( ardeE( 'option' ).append( ardeT( '       ' ) ).n );
	this.append( this.select );
	
	this.addButton = new ArdeButton( 'Add' ).cls( 'passive' );
	this.deleteButton = new ArdeButton( 'Remove' ).cls( 'passive' );
	
	this.append( this.addButton );
	if( this.deleteButtonOnTop ) this.append( this.deleteButton );
	this.append( ardeE( 'br' ) );
	
	this.list = new ArdeComponent( 'select' );
	this.list.element.size = 5;
	this.list.element.style.width = '270px';

	this.append( this.list ).append( ardeE( 'br' ) );

	
	
	this.upButton = new ArdeButton( 'Up' ).cls( 'passive' );
	this.downButton = new ArdeButton( 'Down' ).cls( 'passive' );
	
	this.upButton.setDisplay( this.ordered );
	this.downButton.setDisplay( this.ordered );
	
	if( !this.deleteButtonOnTop ) {
		this.append( this.deleteButton ).append( ardeT( ' ' ) );
	}
	this.append( this.upButton ).append( this.downButton );
	
	var self = this;
	this.addButton.element.onclick = function() {
		if( self.select.element.selectedIndex > 0 || (!this.withDummyOption && self.select.element.selectedIndex == 0 ) ) {
			if( this.allowDuplicates ) {
				id = self.select.element.options[ self.select.element.selectedIndex ].value;
				var option = ardeE( 'option' ).append( self.select.element.options[ self.select.element.selectedIndex ].firstChild.cloneNode( true ) ).n;
				option.value = id;
				
			} else {
				var option = self.select.element.options[ self.select.element.selectedIndex ];
			}
			self.list.append( option );
			self.select.element.selectedIndex = 0;
			
			self.addButton.element.value = 'adf';
			self.addButton.element.value = 'Add';
		}
	};
	
	this.deleteButton.element.onclick = function() {
		if( self.list.element.selectedIndex >= 0 ) {
			if( this.allowDuplicates ) {
				self.list.element.options[ self.list.element.selectedIndex ] = null;
			} else {
				option = self.list.element.options[ self.list.element.selectedIndex ];
				option.selected = true;
				self.select.append( option );
				
				self.addButton.element.value = 'adf';
				self.addButton.element.value = 'Add';
			}
		}
	};
	
	this.upButton.element.onclick = function() {
		si = self.list.element.selectedIndex;
		if( si > 0 ) {
			var tmp = self.list.element.options[ si - 1 ];
			self.list.element.options[ si - 1 ] =  self.list.element.options[ si ];
			self.list.element.options[ si ] = tmp;
		}
	};
	
	this.downButton.element.onclick = function() {
		si = self.list.element.selectedIndex;
		if( si >= 0 && si < self.list.element.options.length - 1 ) {
			var tmp = self.list.element.options[ si ];
			self.list.element.options[ si ] =  self.list.element.options[ si + 1 ];
			self.list.element.options[ si + 1 ] = tmp;
		}
	};
}
ArdeClass.extend( ArdePassiveList, ArdeComponent );


function ArdeActiveSelect( value, height, add, rightAligned ) {
	this.extras = [];
	
	if( typeof value == 'undefined' ) value = null;
	
	if( typeof height == 'undefined' ) this.height = 5;
	else this.height = height;
	
	if( typeof add == 'undefined' ) this.add = false;
	else this.add = add;
	
	if( typeof rightAligned == 'undefined' ) this.rightAligned = false;
	else this.rightAligned = rightAligned;
	
	
	
	this.inputPrevValue = '';
	
	this.ArdeComponent( 'span' );
	this.setDisplayMode( 'inline-block' );
	this.cls( 'active_select' );
	
	this.topCont = new ArdeComponent( 'div' ).appendTo( this );
	
	this.valueSpan = new ArdeComponent( 'span' ).attr( 'id', 'value' ).appendTo( this.topCont );
	this.valueSpan.setDisplayMode( 'inline-block' );
	this.valueSpan.setClickable( true );
	
	this.setValue( value );
	
	this.button = new ArdeImg( baseUrl+'img/down.gif' ).attr( 'id', 'button' ).setClickable( true ).appendTo( this.topCont );
	
	this.hanging = new ArdeComponent( 'div' ).attr( 'id', 'hanging' ).style( 'position', 'absolute' ).setDisplay( false ).cls( 'pad_std' );
	var d = ardeE( 'div' );
	if( this.rightAligned ) {
		if( !ardeBro.webkit ) {
			d.style( 'direction', 'rtl' );
			this.hanging.style( 'direction', 'ltr' );
		}
	}
	this.append( d.append( this.hanging ) );
	this.input = new ArdeInput().attr( 'id', 'input' );
	this.inputP = new ArdeComponent( 'p' ).append( this.input );

	this.addButton = new ArdeRequestButton( 'Add' ).setDisplay( false );
	this.inputP.append( this.addButton );

	this.hanging.append( this.inputP );
	var div = new ardeE( 'div' ).cls( 'margin_std' ).appendTo( this.hanging );
	
	this.requester = new ArdeRequestLabel( ardeLocale.text( 'loading' )+'...' );
	div.append( this.requester );
	
	this.elementsList = new ArdeComponent( 'div' ).appendTo( div );
	
	this.controlP = new ArdeComponent( 'p' ).appendTo( this.hanging );
	this.prevButton = new ArdeButton( ardeLocale.text( 'prev. page' ) ).appendTo( this.controlP );
	this.nextButton = new ArdeButton( ardeLocale.text( 'next page' ) ).appendTo( this.controlP );
	
	var self = this;
	
	this.addButton.onclick = function () { self.addClicked( self.input.element.value ); }
	this.addButton.afterResultReceived = function (result) { self.addReceived( result ); }
	
	this.button.element.onclick = function () { self.expand(); };
	this.valueSpan.element.onclick = function () { self.expand(); };
	this.input.element.onkeyup = function () { self.inputChange(); };
	this.input.element.oninput = function () { self.inputChange(); };
	
	this.requester.afterResultReceived = function ( result ) {
		self.processResult( result );
	};
	
	this.prevButton.element.onclick = function () {
		self.requester.setDisplay( true );
		self.prevButton.setDisabled( true );
		self.offset -= self.height;
		if( self.offset < 0 ) self.offset = 0;
		self.request( self.offset, self.height, self.input.element.value );
	};
	
	this.nextButton.element.onclick = function () {
		self.requester.setDisplay( true );
		self.nextButton.setDisabled( true );
		self.offset += self.height;
		self.request( self.offset, self.height, self.input.element.value );
	};
}

ArdeActiveSelect.prototype.onchange = function () {};

ArdeActiveSelect.prototype.request = function ( offset, count, beginWith ) {};

ArdeActiveSelect.prototype.processResult = function ( result ) {};

ArdeActiveSelect.prototype.addClicked = function ( str ) {};

ArdeActiveSelect.prototype.addReceived = function ( value ) {
	this.setValue( value );
	this.retract();
}

ArdeActiveSelect.prototype.setStandardCallbacks = function ( obj, name ) {
	if( typeof obj[ name+'Requested' ] != 'undefined' ) {
		this.request = function ( offset, count, beginWith ) { obj[ name+'Requested' ]( offset, count, beginWith ); };
	}
	if( typeof obj[ name+'Received' ] != 'undefined' ) {
		this.processResult = function ( result ) { obj[ name+'Received' ]( result ); };
	}
	if( typeof obj[ name+'AddClicked' ] != 'undefined' ) {
		this.addClicked = function ( str ) { obj[ name+'AddClicked' ]( str ); };
	}
};

ArdeActiveSelect.prototype.showAddButton = function ( showAddButton ) {
	this.addButton.setDisplay( showAddButton );
}

ArdeActiveSelect.prototype.expand = function () {
	this.elementsList.clean();
	this.inputPrevValue = '';
	this.offset = 0;
	this.controlP.setDisplay( false );
	this.input.element.value = '';
	this.hanging.switchDisplay();
	if( this.hanging.isDisplaying() ) {
		this.input.element.focus();
		this.requester.setDisplay( true );
		this.request( this.offset, this.height, '' );
		this.webkitShift();
	}
	
};

ArdeActiveSelect.prototype.inputChange = function () {
	if( this.inputPrevValue == this.input.element.value ) return;
	this.inputPrevValue = this.input.element.value;
	this.requester.setDisplay( true );
	this.offset = 0;
	this.request( this.offset, this.height, this.input.element.value );
};

ArdeActiveSelect.prototype.retract = function ( selected ) {
	this.hanging.setDisplay( false );
	if( typeof selected != 'undefined' ) {
		selected.remove();
		this.setValue( selected );
	}
};

ArdeActiveSelect.prototype.setValue = function ( value ) {
	var prevValue = this.value;
	this.value = value;
	this.valueSpan.clear();
	if( value == null ) {
		this.valueSpan.append( ardeT( ardeLocale.text( 'Select' )+'...' ) );
	} else {
		value.removeClass( 'arde_active_select_in_list' );
		value.removeClass( 'arde_active_select_last' );
		value.setMouseOverHighlight( false );
		value.highlightOnMouseOver( false );
		value.element.onclick = null;
		this.valueSpan.append( value );
	}
	
	if( prevValue !== this.getValue() ) {
		this.onchange();
	}
};

ArdeActiveSelect.prototype.setSelected = function ( value ) {
	return this.setValue( value );
};

ArdeActiveSelect.prototype.getValue = function () {
	return this.value;
};

ArdeActiveSelect.prototype.getSelected = function () {
	return this.value;
};

ArdeActiveSelect.prototype.addExtra = function( extra ) {
	extra.addClass( 'arde_active_select_in_list' );
	this.extras.push( extra );
}

ArdeActiveSelect.prototype.insertExtras = function() {
	for( var i in this.extras ) {
		this.extras[i].setClickable( true );
		this.extras[i].highlightOnMouseOver( true );
		var self = this; 
		this.extras[i].element.onclick = function ( i ) {
			return function () {
				self.retract( self.extras[i] );
			};
		}(i);
		this.elementsList.append( ardeE( 'div' ).append( this.extras[i] ) );
	}
}

ArdeActiveSelect.prototype.resultsReceived = function ( results, more ) {
	for( var i in results ) {
		results[i].addClass( 'arde_active_select_in_list' );
	}
	results[i].addClass( 'arde_active_select_last' );
	
	this.requester.setDisplay( false );
	this.elementsList.clean();
	
	if( this.offset == 0 && this.input.element.value == '' ) {
		this.insertExtras();
	}
	
	if( more || this.offset > 0 ) {
		this.nextButton.setDisabled( !more );
		this.prevButton.setDisabled( this.offset <= 0 );
		this.controlP.setDisplay( true );
		this.input.element.focus();
	} else {
		this.controlP.setDisplay( false );
	}
	
	
	for( var i in results ) {
		results[i].setClickable( true );
		results[i].highlightOnMouseOver( true );
		var self = this; 
		results[i].element.onclick = function ( i ) {
			return function () {
				self.retract( results[i] );
			};
		}(i);
		this.elementsList.append( ardeE( 'div' ).append( results[i] ) );
	}
	
	this.webkitShift();
};

ArdeActiveSelect.prototype.webkitShift = function () {
	if( ardeBro.webkit && this.rightAligned ) {
		this.hanging.style( 'marginLeft', '-'+( this.hanging.getWidth() - this.topCont.getWidth() )+'px' );
	}
};

ArdeClass.extend( ArdeActiveSelect, ArdeComponent );



function ArdeNotice() {
	this.ArdeComponent( 'div' );
	this.setDisplay( false );
	this.setVisible( false );
	this.cls( 'arde_notifier' );
	var headD = ardeE( 'div' ).cls( 'nhead' );
	var closeC = new ArdeComponent( 'span' ).setClickable( true ).cls( 'close' ).append( ardeT( 'x' ) );
	var self = this;
	closeC.element.onclick = function () {
		self.hide();
	};
	this.append( headD.append( closeC ) );
	
	this.body = new ArdeComponent( 'div' );
	this.body.cls( 'body' ).append( ardeT( ' ' ) );

	this.append( this.body );
	
}
ArdeClass.extend( ArdeNotice, ArdeComponent );

ArdeNotice.global = null;

ArdeNotice.getElement = function() {
	if( ArdeNotice.global == null ) {
		ArdeNotice.global = new ArdeNotice();
		ArdeNotice.global.insertInBody();
	}
	return ArdeNotice.global.body;
};

ArdeNotice.getCleanElement = function() {
	element = ArdeNotice.getElement();
	element.clean();
	return  element;
};

ArdeNotice.prototype.show = function() {
	this.setPos( 10, 10 );
	this.setDisplay( true );
	this.center();
	this.setVisible( true );
};

ArdeNotice.prototype.hide = function() {
	this.setDisplay( false );
	this.setVisible( false );
};

ArdeNotice.show = function() {
	if( typeof ArdeNotice.global == 'undefined' ) return;
	ArdeNotice.global.show();
};

ArdeNotice.hide = function() {
	if( typeof ArdeNotice.global == 'undefined' ) return;
	ArdeNotice.global.hide();
};

ArdeNotice.switchDisplay = function() {
	if( ArdeNotice.global.isDisplaying() ) ArdeNotice.global.hide();
	else ArdeNotice.global.show();
};

ArdeNotice.prototype.center = function() {
	if( this.element.offsetWidth > ardeClientWidth() - 20 ) {
		var x = 10;
	} else {
		var x = (ardeClientWidth() / 2) - (this.element.offsetWidth / 2);
	}
	
	if( this.element.offsetHeight > ardeClientHeight() - 20 ) {
		
		var y = 10 + ardeScrollTop();
		
	} else {
		var y = (ardeClientHeight() / 2) - (this.element.offsetHeight / 2) + ardeScrollTop();
	}
	this.setPos( x, y );
};

function ArdeDuration( seconds, showDays ) {
	if( showDays ) {
		var days = Math.floor( seconds / 86400 );
		seconds -= days * 86400;
	}
	var hours = Math.floor( seconds / 3600 );
	seconds -= hours * 3600;
	var minutes = Math.floor( seconds / 60 );
	seconds -= minutes * 60;
	
	this.ArdeComponent( 'span' );
	this.cls( 'duration' );
	
	if( showDays ) {
		this.append( ardeT( days ) ).append( ardeE( 'span' ).cls( 't' ).append( ardeT( 'd ' ) ) );
	}
	this.append( ardeT( hours ) ).append( ardeE( 'span' ).cls( 't' ).append( ardeT( 'h ' ) ) );
	this.append( ardeT( minutes ) ).append( ardeE( 'span' ).cls( 't' ).append( ardeT( 'm ' ) ) );
	this.append( ardeT( seconds ) ).append( ardeE( 'span' ).cls( 't' ).append( ardeT( 's' ) ) );
}
ArdeClass.extend( ArdeDuration, ArdeComponent );

ArdeDuration.DAYS = 1;
ArdeDuration.HOURS = 2;
ArdeDuration.MINUTES = 4;
ArdeDuration.SECONDS = 8;

function ArdeDurationInput( seconds, show ) {
	if( typeof show == 'undefined' ) show = 0xffff;
	
	this.show = show;
	
	this.ArdeComponent( 'span' );
	this.cls( 'duration' );
	
	if( show & ArdeDuration.DAYS ) {
		this.daysInput = new ArdeInput().attr( 'size', '2' );
		this.append( this.daysInput ).append( ardeE( 'span' ).cls( 't' ).append( ardeT( 'days ' ) ) );
	}
	if( show & ArdeDuration.HOURS ) {
		this.hoursInput = new ArdeInput().attr( 'size', '2' );
		this.append( this.hoursInput ).append( ardeE( 'span' ).cls( 't' ).append( ardeT( 'hours ' ) ) );
	}
	if( show & ArdeDuration.MINUTES ) {
		this.minutesInput = new ArdeInput().attr( 'size', '2' );
		this.append( this.minutesInput ).append( ardeE( 'span' ).cls( 't' ).append( ardeT( 'minutes ' ) ) );
	}
	
	if( show & ArdeDuration.SECONDS ) {
		this.secondsInput = new ArdeInput().attr( 'size', '2' );
		this.append( this.secondsInput ).append( ardeE( 'span' ).cls( 't' ).append( ardeT( 'seconds' ) ) );
	}
	
	this.setValue( seconds );
	
	
}

ArdeDurationInput.prototype.setValue = function( seconds ) {
	this.value = seconds;
	if( this.show & ArdeDuration.DAYS ) {
		var days = Math.floor( seconds / 86400 );
		seconds -= days * 86400;
		this.daysInput.setValue( days );
	}
	if( this.show & ArdeDuration.HOURS ) {
		var hours = Math.floor( seconds / 3600 );
		seconds -= hours * 3600;
		this.hoursInput.setValue( hours );
	}
	if( this.show & ArdeDuration.MINUTES ) {
		var minutes = Math.floor( seconds / 60 );
		seconds -= minutes * 60;
		this.minutesInput.setValue( minutes );
	}
	if( this.show & ArdeDuration.SECONDS ) {
		this.secondsInput.setValue( seconds );
	}
};

ArdeDurationInput.prototype.getValue = function() {
	res = 0;
	if( this.show & ArdeDuration.DAYS ) {
		res += parseInt( this.daysInput.getValue() ) * 86400;
	}
	if( this.show & ArdeDuration.HOURS ) {
		res += parseInt( this.hoursInput.getValue() ) * 3600;
	}
	if( this.show & ArdeDuration.MINUTES ) {
		res += parseInt( this.minutesInput.getValue() ) * 60;
	}
	if( this.show & ArdeDuration.SECONDS ) {
		res += parseInt( this.secondsInput.getValue() );
	}
	return res;
};

ArdeClass.extend( ArdeDurationInput, ArdeComponent );



function ArdeItem( tagName ) {
	this.ArdeComponent( tagName );
	this.ardeList = null;
	this.ardeSelected = false;
}

ArdeItem.prototype.highlightOnMouseOver = function( highlightOnMouseOver ) {
	var self = this;
	if( highlightOnMouseOver ) {
		this.element.onmouseover = function () {
			self.setMouseOverHighlight( true );
		};
		this.element.onmouseout = function () {
			self.setMouseOverHighlight( false );
		};
	} else {
		this.element.onmouseover = null;
		this.element.onmouseout = null;
	}
	return this;
};


ArdeItem.prototype.setMouseOverHighlight = function( mouseOverHighlight ) { return this; };

ArdeItem.prototype.setSelectedHighlight = function( selectedHighlight ) { return this; };

ArdeItem.prototype.setSelected = function ( selected ) {
	if( this.ardeList != null ) this.ardeList._itemSelectionChanged( this, selected );
	this._setSelected( selected );
}

ArdeItem.prototype._setSelected = function ( selected ) {
	this.ardeSelected = selected;
}

ArdeClass.extend( ArdeItem, ArdeComponent );

function ArdeComponentList( component, selectMode ) {
	if( typeof selectMode == 'undefined' ) this.selectMode = ArdeComponentList.SELECT_NONE;
	else this.selectMode = selectMode;
	this.items = [];
	this.listComponent = component;
	
	if( this.selectMode == ArdeComponentList.SELECT_SINGLE ) {
		this.ardeSelected = null;
	}
}

ArdeComponentList.SELECT_NONE = 0;
ArdeComponentList.SELECT_SINGLE = 1;
ArdeComponentList.SELECT_MULTI = 2;

ArdeComponentList.prototype._itemSelectionChanged = function( item, selected ) {

	if( this.selectMode == ArdeComponentList.SELECT_SINGLE ) {
		if( selected ) {
			if( this.ardeSelected != null ) this.ardeSelected._setSelected( false );
			this.ardeSelected = item;
		} else {
			this.ardeSelected = null;
		}
	}
};

ArdeComponentList.prototype.getSelected = function() {
	return this.ardeSelected;
};

ArdeComponentList.prototype.onchange = function () {};

ArdeComponentList.prototype.length = function () {
	return this.items.length;
}


ArdeComponentList.prototype.positionOf = function ( item ) {
	for( var i in this.items ) {
		if( this.items[i] == item ) return i;
	}
	return -1;
}

ArdeComponentList.prototype.addItem = function( item ) {
	item.ardeList = this;
	this.listComponent.append( item );
	this.items.push( item );
	this.onchange();
};

ArdeComponentList.prototype.insertIdSorted = function( item, descending ) {
	if( descending ) {
		for( var i in this.items ) {
			if( item.id > this.items[i].id ) return this.insertItemBefore( item, this.items[i] );
		}
	} else {
		for( var i in this.items ) {
			if( item.id < this.items[i].id ) return this.insertItemBefore( item, this.items[i] );
		}
	}
	this.addItem( item );
};

ArdeComponentList.prototype.insertFirstItem = function( item ) {
	item.ardeList = this;
	this.listComponent.insertFirstChild( item );
	var newItems = [ item ];
	for( var i in this.items ) {
		newItems.push( this.items[i] );
	}
	this.items = newItems;
	this.onchange();
};

ArdeComponentList.prototype.insertItemBefore = function( item, reference ) {
	if( reference == null ) return this.addItem( item ); 
	item.ardeList = this;
	this.listComponent.insertBefore( item, reference );
	var newItems = [];
	for( var i in this.items ) {
		if( this.items[i] == reference ) newItems.push( item );
		newItems.push( this.items[i] );
	}
	this.items = newItems;
	this.onchange();
};

ArdeComponentList.prototype.insertItemAfter = function ( item, reference ) {
	if( reference == null ) return this.insertFirstItem( item );
	item.ardeList = this;
	this.listComponent.insertAfter( item, reference );
	var newItems = [];
	for( var i in this.items ) {
		newItems.push( this.items[i] );
		if( this.items[i] == reference ) newItems.push( item );
	}
	this.items = newItems;
	this.onchange();
};

ArdeComponentList.prototype.insertItemBeforePosition = function( item, referencePosition ) {
	if( typeof this.items[ referencePosition ] == 'undefined' ) this.addItem( item );
	this.insertItemBefore( item, this.items[ referencePosition ] );
};

ArdeComponentList.prototype.insertItemBeforeReversedPosition = function( item, referencePosition ) {
	reversePosition = this.items.length - 1 - referencePosition;
	if( reversePosition < 0 ) return this.insertFirstItem( item );
	return this.insertItemAfter( item, this.items[ reversePosition ] );
};

ArdeComponentList.prototype.moveItemUp = function( item ) {
	for( i = 0; i < this.items.length; ++i ) {
		if( this.items[i] == item ) {
			if( i == 0 ) return;
			this.listComponent.insertBefore( item, this.items[ i - 1 ] );
			var temp = this.items[ i - 1 ];
			this.items[ i - 1 ] = this.items[ i ];
			this.items[i] = temp;
			return;
		}
	}
	this.onchange();
};

ArdeComponentList.prototype.moveItemDown = function( item ) {
	for( i = 0; i < this.items.length; ++i ) {
		if( this.items[i] == item ) {
			
			if( i == this.items.length - 1 ) return;
			
			this.listComponent.insertBefore( item, this.items[ i + 1 ].element.nextSibling );
			var temp = this.items[ i + 1 ];
			this.items[ i + 1 ] = this.items[ i ];
			this.items[i] = temp;
			return;
		}
	}
	this.onchange();
};

ArdeComponentList.prototype.replaceItem = function( item, replacement ) {
	item.ardeList = null;
	replacement.ardeList = this;
	for( var i in this.items ) {
		if( this.items[i] == item ) {
			this.items[i] = replacement;
		}
	}
	item.replace( replacement );
};

ArdeComponentList.prototype.clear = function() {
	return this.removeAllItems();
};

ArdeComponentList.prototype.removeAllItems = function() {
	for( var i in this.items ) {
		this.items[i].ardeList = null;
	}
	this.listComponent.clear();
	this.items = [];
	this.onchange();
};

ArdeComponentList.prototype.removeItem = function( item ) {
	item.ardeList = null;
	var newItems = [];
	for( var i in this.items ) {
		if( this.items[i] != item ) {
			newItems.push( this.items[i] );
		} else {
			this.items[i].remove();
			this.items[i].ardeList = null;
		}
	}
	this.items = newItems;
	this.onchange();
};

ArdeComponentList.prototype.removeItemById = function( id ) {
	var newItems = [];
	for( var i in this.items ) {
		if( this.items[i].id != id ) {
			newItems.push( this.items[i] );
		} else {
			this.items[i].remove();
			this.items[i].ardeList = null;
		}
	}

	this.items = newItems;
	this.onchange();
};

function ArdeCompListComp( tagName ) {
	this.ArdeComponent( tagName );
	this.ArdeComponentList( this );
}
ArdeClass.extend( ArdeCompListComp, ArdeComponent );
ArdeClass.extend( ArdeCompListComp, ArdeComponentList );


function ardeExceptionExpand( id ) {
	var pane = document.getElementById( 'arde_exception_p'+id );
	var display = pane.style.display;
	if( !display ) display = 'none';
	pane.style.display = ( display == 'none' ? 'block' : 'none' );
	var button = document.getElementById( 'arde_exception_b'+id );
	button.firstChild.nodeValue = ( display == 'none' ? '-' : '+' );
}

function ardeNbsp( count ) {
	if( typeof count == 'undefined' ) return '\u00A0';
	var s = '';
	for( var i = 0; i < count; ++i ) s += '\u00A0';
	return s;
}

function ardePreloadImage( url ) {
	var img = new ArdeImg( url );
	img.setVisible( false );
	img.style( 'position', 'absolute' );
	img.insertInBody();
}

function ardePreloadFlash( url ) {
	var fl = new ArdeFlash( url, "10px", "10px" );
	fl.setVisible( false );
	fl.style( 'position', 'absolute' );
	fl.insertInBody();
}

function ardeTextComp( text, replacements ) {

	var o = new ArdeComponent( 'span' );
	for( var key in replacements ) {
		index = text.indexOf( '{'+key+'}' );
		if( index >= 0 ) {
			o.append( ardeT( text.substr( 0, index ) ) );
			o.append( replacements[key] );
			text = text.substr( index + key.length + 2 );
		}
	}
	if( text != '' ) o.append( ardeT( text ) );
	return o;
}

function ardeComputedStyle( element, name ) {
	var computedStyle;
	if( typeof element.currentStyle != 'undefined' ) {
	  	return element.currentStyle[ name ];
	} else {
    	return document.defaultView.getComputedStyle( element, null )[name];
	}
}

function activateLinkSelect( id ) {
	var select = document.getElementById( id );
	var selectedIndex = select.selectedIndex;
	select.onchange = function () {
		if( select.selectedIndex == selectedIndex ) return;
		window.location = select.options[ select.selectedIndex ].value;
	};
}
