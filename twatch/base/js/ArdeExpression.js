
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
    
function ArdeExpression( a ) {
	this.elements = a; 
}

ArdeExpression.INT = 0;
ArdeExpression.TRUE = 1;
ArdeExpression.FALSE = 2;

ArdeExpression.getVar = function ( a, i ) {
	return { 'i': i, 'element': null };
}

ArdeExpression.operators = {
		 '&': 'and'
		,'|': 'or'
		,'(': '('
		,')': ')'
		,'!': '!'
		,'>': '>'
		,'<': '<'
		,'=': 'is'
		,'!=': 'is not'
};
ArdeExpression.values = {};

ArdeExpression.fromXml = function ( element ) {
	var a = [];
	var elemEs = new ArdeXmlElemIter( element, 'elem' );
	while( elemEs.current ) {
		a.push( ArdeExpressionElem.fromXml( elemEs.current ) );
		elemEs.next();
	}
	return new ArdeExpression( a );
};

function ArdeExpressionElem( str, a ) {
	this.str = str;
	this.a = a;
}

ArdeExpressionElem.fromXml = function ( element ) {
	var str = ArdeXml.strAttribute( element, 'name' );
	itemEs = new ArdeXmlElemIter( element, 'item' );
	var a = [];
	while( itemEs.current ) {
		var type = ArdeXml.strAttribute( itemEs.current, 'type', null );
		if( type == 'str' ) {
			a.push( ArdeXml.strContent( itemEs.current ) );
		} else {
			a.push( ArdeXml.intContent( itemEs.current ) );
		}
		itemEs.next();
	}
	return new ArdeExpressionElem( str, a );
};

ArdeExpressionElem.prototype.getParam = function () {
	var s = new ArdeAppender( '.' );
	for( var i in this.a ) {
		if( typeof this.a[i] == 'number' ) s.append( 'i'+this.a[i] );
		else if( this.a[i] === true ) s.append( 't' );
		else if( this.a[i] === false ) s.append( 'f' );
		else s.append( 's'+this.a[i] );
	}
	return s.s;
};


function ArdeExpressionElemComponent( element ) {
	this.ArdeExpressionElem = element;
	text = element.str;
	this.ArdeComponent( 'span' );
	this.setClickable( true );
	var self = this;
	this.element.onclick = function () {
		self.ardeList.removeItem( self );
	};
	this.style( 'padding', '1px 3px' ).style( 'background', '#fff' ).style( 'marginRight', '5px' );
	this.append( ardeT( text ) );
}
ArdeClass.extend( ArdeExpressionElemComponent, ArdeComponent );

function ArdeExpressionInput( expression, emptyString ) {
	if( typeof emptyString == 'undefined' ) this.emptyString = 'always';
	else this.emptyString = emptyString;
	this.ArdeComponent( 'span' );
	this.alwaysSpan = new ArdeComponent( 'span' ).cls( 'unemph').append( ardeT( this.emptyString ) );
	this.list = new ArdeCompListComp( 'span' ).appendTo( this );
	this.append( ardeT( ' ' ) );
	
	this.select = new ArdeSelect().addDummyOption( ' ' ).style( 'marginLeft', '20px' ).appendTo( this );

	for( var i in this.constructor.elements ) {
		var option = ardeE( 'option' ).append( ardeT( this.constructor.elements[i].name ) );
		this.select.append( option );
	}
	
	this.extras = new ArdeComponent( 'span' ).appendTo( this );
	this.addButton = new ArdeButton( 'Add' ).cls( 'passive' );
	
	this.infoSpan = new ArdeComponent( 'span' ).cls( 'info' ).append( ardeT( 'click elements to remove' ) );
	this.append( ardeT( ' ' ) ).append( this.infoSpan ); 
	
	var self = this;
	this.addButton.element.onclick = function () { self.addClicked(); };
	this.select.element.onchange = function () { self.selectChanged(); };

	for( var i in expression.elements ) {
		this.list.addItem( new ArdeExpressionElemComponent( expression.elements[i] ) );
	}
	
	this.infoSpan.setDisplay( this.list.items.length > 0 );
	this.alwaysSpan.setDisplay( self.list.items.length <= 0 );
	
	this.insertFirstChild( this.alwaysSpan );
	
	this.list.onchange = function () {
		self.infoSpan.setDisplay( self.list.items.length > 0 );
		self.alwaysSpan.setDisplay( self.list.items.length <= 0 );
	};
}

function ArdeExpressionInputElem( name ) {
	this.name = name;
}
ArdeExpressionInputElem.prototype.addClicked = function( input ) {}
ArdeExpressionInputElem.prototype.selected = function( input ) {}

function ArdeExpressionInputOper( name, id ) {
	this.ArdeExpressionInputElem( name );
	this.id = id;
}

ArdeExpressionInputOper.prototype.selected = function( input ) {
	input.extras.clear();
	input.select.element.selectedIndex = 0;
	this.addClicked( input );
}

ArdeExpressionInputOper.prototype.addClicked = function( input ) {
	input.list.addItem( new ArdeExpressionElemComponent( new ArdeExpressionElem( this.name, [this.id] ) ) );
}

ArdeClass.extend( ArdeExpressionInputOper, ArdeExpressionInputElem );

function ArdeExpressionInputSVar( id, name ) {
	this.ArdeExpressionInputElem( name );
	this.id = id;
}

ArdeExpressionInputSVar.prototype.selected = function( input ) {
	input.extras.clear();
	input.select.element.selectedIndex = 0;
	this.addClicked( input );
}

ArdeExpressionInputSVar.prototype.addClicked = function( input ) {
	input.list.addItem( new ArdeExpressionElemComponent( new ArdeExpressionElem( this.name, [this.id] ) ) );
}

ArdeClass.extend( ArdeExpressionInputSVar, ArdeExpressionInputElem );

function ArdeExpressionInputBool( value ) {
	this.ArdeExpressionInputElem( value?'always':'never' );
	this.value = value;
}

ArdeExpressionInputBool.prototype.selected = function( input ) {
	input.extras.clear();
	input.select.element.selectedIndex = 0;
	this.addClicked( input );
}

ArdeExpressionInputBool.prototype.addClicked = function( input ) {
	input.list.addItem( new ArdeExpressionElemComponent( new ArdeExpressionElem( this.name, [this.value] ) ) );
}

ArdeClass.extend( ArdeExpressionInputBool, ArdeExpressionInputElem );

function ArdeExpressionInputSFVar( name, id, values ) {
	this.ArdeExpressionInputElem( name );
	this.id = id;
	this.values = values;
}

ArdeExpressionInputSFVar.prototype.addClicked = function( input ) {
	
	if( this.select.selectedOption() == null ) return;
	var id = parseInt( this.select.selectedOption().value );
	input.list.addItem( new ArdeExpressionElemComponent( new ArdeExpressionElem( this.values[ id ], [ this.id, id ] ) ) );
}

ArdeExpressionInputSFVar.prototype.selected = function( input ) {
	input.extras.clear();
	this.select = new ArdeSelect().addDummyOption( '' );
	var self = this;
	this.select.element.onchange = function () { self.selectChanged( input ); };
	for( var id in this.values ) {
		this.select.append( ardeE( 'option' ).attr( 'value', id ).append( ardeT( this.values[ id ] ) ) );
	}
	input.extras.append( this.select );
}

ArdeExpressionInputSFVar.prototype.selectChanged = function ( input ) {
	if( this.select.selectedOption() !== null ) {
		this.addClicked( input );
		input.select.element.selectedIndex = 0;
		input.selectChanged();
	}
}

ArdeClass.extend( ArdeExpressionInputSFVar, ArdeExpressionInputElem );



ArdeExpressionInput.elements = [
	 new ArdeExpressionInputOper( '+', '+' )
	,new ArdeExpressionInputOper( 'or', '|' )
	,new ArdeExpressionInputOper( 'and', '&' )
	,new ArdeExpressionInputOper( '-', '-' )
	,new ArdeExpressionInputOper( '*', '*' )
	,new ArdeExpressionInputOper( '/', '/' )
	,new ArdeExpressionInputOper( '!', '!' )
	,new ArdeExpressionInputOper( 'is', '=' )
	,new ArdeExpressionInputOper( 'is not', '!=' )
	,new ArdeExpressionInputOper( '<', '<' ) 
	,new ArdeExpressionInputOper( '>', '>' )
	,new ArdeExpressionInputOper( '<=', '<=' ) 
	,new ArdeExpressionInputOper( '>=', '>=' )
	,new ArdeExpressionInputOper( '(', '(' )
	,new ArdeExpressionInputOper( ')', ')' )
	,new ArdeExpressionInputBool( true )
	,new ArdeExpressionInputBool( false )
];


ArdeExpressionInput.prototype.getParam = function () {
	var w = '';
	for( var i in this.list.items ) {
		w += (i==0?'':'.')+this.list.items[i].ArdeExpressionElem.getParam();
	}
	return w;
};

ArdeExpressionInput.prototype.selectChanged = function () {
	var selectedIndex = this.select.element.selectedIndex - 1;
	if(  selectedIndex == -1 ) {
		this.extras.clear();
		return;
	}
	
	this.constructor.elements[ selectedIndex ].selected( this );
};

ArdeExpressionInput.prototype.addClicked = function () {
	var selectedIndex = this.select.element.selectedIndex - 1;
	if( selectedIndex == -1 ) return;
	this.constructor.elements[ selectedIndex ].addClicked( this );


};

ArdeClass.extend( ArdeExpressionInput, ArdeComponent );