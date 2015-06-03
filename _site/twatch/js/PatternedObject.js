
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
    
function PatternedObjectsHolder( newObject, testLabel ) {
	this.ArdeComponent( 'div' );
	
	this.restoreButton = new ArdeRequestButton( 'Restore Deleted Defaults' );
	this.restoreButton.setStandardCallbacks( this, 'restore' );
	this.testInput = new ArdeInput().attr( 'size', '90' );
	this.testButton = new ArdeRequestButton( 'test' );
	this.testButton.setStandardCallbacks( this, 'test' );
	this.testResultP = new ArdeComponent( 'p' ).setDisplay( false );
	this.append( ardeE( 'div' ).cls( 'group' ).append( ardeE( 'p' ).append( this.restoreButton ) ).append( ardeE( 'p' ).append( ardeT( testLabel+': ' ) ).append( this.testInput ).append( ardeT( ' ' ) ).append( this.testButton ) ).append( this.testResultP ) );
	
	
	
	this.newObject = newObject;
	this.newObject.ardeList = this;
	this.append( this.newObject );
	this.ArdeComponentList( new ArdeComponent( 'div' ).appendTo( this ) );
	
	
}

PatternedObjectsHolder.prototype.restoreClicked = function () {
	this.restoreButton.request( twatchFullUrl( this.constructor.rec ), 'a=restore_deleted', ardeXmlObjectListClass( this.constructor.objectClass, this.constructor.objectTagName, false, true ) );
};

PatternedObjectsHolder.prototype.restoreConfirmed = function ( objs ) {
	for( var i = 0 ; i < objs.a.length ; ++i ) {
		this.insertItemBeforeReversedPosition( objs.a[i], objs.p[i] );
	}
};

PatternedObjectsHolder.prototype.testClicked = function () {
	this.testButton.request( twatchFullUrl( this.constructor.rec ), 'a=test&s='+ardeEscape( this.testInput.element.value ), TestResult );
};

PatternedObjectsHolder.prototype.testConfirmed = function ( testResult ) {
	this.testResultP.clear();
	this.testResultP.append( ardeT( testResult.str ) );
	this.testResultP.setDisplay( true );
};

function TestResult( str ) {
	this.str = str;
}

TestResult.fromXml = function ( element ) {
	return new TestResult( ArdeXml.strContent( element ) );
};

PatternedObjectsHolder.rec = '';
ArdeClass.extend( PatternedObjectsHolder, ArdeComponent );
ArdeClass.extend( PatternedObjectsHolder, ArdeComponentList );

function PatternedObject( id, name, pattern, hasImage, dummy ) {
	this.id = id;
	this.name = name;
	this.pattern = pattern;
	this.hasImage = hasImage;
	
	if( typeof dummy == 'undefined' ) this.dummy = false;
	else this.dummy = dummy;
	
	this.ArdeComponent( 'div' );
	if ( ArdeClass.instanceOf( this, NewPatternedObject ) ) {
		this.cls( 'special_block' );
	} else {
		this.cls( 'block' );
	}
	
	
	
	if ( !ArdeClass.instanceOf( this, NewPatternedObject ) && this.id != 1 ) {
		var floater = new ArdeComponent( 'div' ).setFloat( 'right' ).appendTo( this );

		this.upButton = new ArdeRequestButton( 'Up' );
		this.downButton = new ArdeRequestButton( 'Down' );
		floater.append( ardeE( 'p' ).append( this.upButton ).append( this.downButton ) );
		
		this.upButton.setStandardCallbacks( this, 'up' );
		this.downButton.setStandardCallbacks( this, 'down' );
		
	}
	
	if ( ArdeClass.instanceOf( this, NewPatternedObject ) ) {
		this.titleText = ardeT( this.constructor.title ).appendTo( ardeE( 'h2' ).appendTo( this ) );
	} else {
		this.titleText = ardeT( this.name ).appendTo( ardeE( 'h2' ).appendTo( this ) );
	}
	
	this.topDiv = new ArdeComponent( 'div' ).appendTo( this );
	
	var tb = ardeE( 'tbody' ).appendTo( new ArdeTable().cls( 'form' ).appendTo( this ) );
	
	this.nameInput = new ArdeInput( this.name ).attr( 'size', '25' );
	
	this.patternInput = new ArdeInput( this.pattern ).attr( 'size', '90' ).setDisabled( this.dummy );
	
	this.append( ardeE( 'p' ).append( ardeT( 'name: ' ) ).append( this.nameInput ).append( ardeT( '\u00A0\u00A0\u00A0pattern: ' ) ).append( this.patternInput ) );
	
	if( typeof UserAgent != 'undefined' && ( this instanceof UserAgent || this instanceof NewUserAgent ) ) {
		this.hasImageCheck = new ArdeCheckBox( this.hasImage ).setDisabled( this.dummy );
		this.append( ardeE( 'p' ).append( ardeE( 'label' ).append( this.hasImageCheck ).append( ardeT( 'has image' ) ) ) );
	}
	
	if ( ArdeClass.instanceOf( this, NewPatternedObject ) ) {
		this.addButton = new ArdeRequestButton( this.constructor.addButtonName );
		this.addButton.setStandardCallbacks( this, 'add' );
		var p = ardeE( 'p' ).append( this.addButton ).appendTo( this );
	} else {
		this.applyButton = new ArdeRequestButton( 'Apply Changes' );
		this.restoreButton = new ArdeRequestButton( 'Restore Defaults' );
		this.deleteButton = new ArdeRequestButton( 'Delete' ).setCritical( true );
		
		this.applyButton.setStandardCallbacks( this, 'apply' );
		this.restoreButton.setStandardCallbacks( this, 'restore' );
		this.deleteButton.setStandardCallbacks( this, 'delete' );
		
		this.moreButton = new ArdeButton( 'More Info' ).cls( 'passive' );
		this.moreButton.setFloat( 'right' ).style( 'marginTop', '5px' );
		
		this.moreCloseButton = new ArdeButton( 'Less Info' ).cls( 'passive' );
		this.moreCloseButton.setFloat( 'right' ).setDisplay( false ).style( 'marginTop', '5px' );
		
		var p = ardeE( 'p' ).appendTo( this );
		
		p.append( this.moreButton ).append( this.moreCloseButton );	
		
		p.append( this.applyButton ).append( this.restoreButton );
		if ( !this.dummy ) {
			p.append( this.deleteButton );
		}
		
		this.morePane = new ArdeComponent( 'div' ).cls( 'group' ).setDisplay( false ).appendTo( this );
		this.morePane.append( ardeE( 'p' ).append( ardeT( 'Internal ID: ' ) ).append( ardeE( 'span' ).cls( 'fixed' ).append( ardeT( this.id ) ) ) );
		
		var self = this;
		
		this.moreButton.element.onclick = function () {
			self.morePane.setDisplay( true );
			self.moreButton.setDisplay( false );
			self.moreCloseButton.setDisplay( true );
		};
		this.moreCloseButton.element.onclick = function () {
			self.morePane.setDisplay( false );
			self.moreCloseButton.setDisplay( false );
			self.moreButton.setDisplay( true );
			
		};
	}

}
PatternedObject.rec = '';


PatternedObject.prototype.applyClicked = function () {
	this.applyButton.request( twatchFullUrl( this.constructor.rec ), 'a=change&'+this.getParams() );
};

PatternedObject.prototype.applyConfirmed = function ( result ) {
	this.titleText.n.nodeValue = this.nameInput.element.value;
};

PatternedObject.prototype.restoreClicked = function () {
	this.restoreButton.request( twatchFullUrl( this.constructor.rec ), 'a=restore&i='+this.id, this.constructor );
};

PatternedObject.prototype.restoreConfirmed = function ( obj ) {
	this.ardeList.replaceItem( this, obj );
	obj.restoreButton.ok();
};

PatternedObject.prototype.deleteClicked = function () {
	this.deleteButton.request( twatchFullUrl( this.constructor.rec ), 'a=delete&i='+this.id );
};

PatternedObject.prototype.deleteConfirmed = function ( result ) {
	this.ardeList.removeItem( this );
};

PatternedObject.prototype.upClicked = function () {
	if( this.ardeList.positionOf( this ) == 0 ) return;
	this.upButton.request( twatchFullUrl( this.constructor.rec ), 'a=down&i='+this.id );
};

PatternedObject.prototype.upConfirmed = function ( result ) {
	this.ardeList.moveItemUp( this );
};

PatternedObject.prototype.downClicked = function() {
	this.downButton.request( twatchFullUrl( this.constructor.rec ), 'a=up&i='+this.id );
};

PatternedObject.prototype.downConfirmed = function ( result ) {
	this.ardeList.moveItemDown( this );
};

PatternedObject.prototype.getParams = function () {
	if (ArdeClass.instanceOf(this, NewPatternedObject)) {
		var id = '';
	} else {
		var id = 'i='+this.id+'&';
	}
	var s = id+'n='+ardeEscape( this.nameInput.element.value )+'&p='+ardeEscape( this.patternInput.element.value );
	if( typeof this.hasImageCheck != 'undefined' ) {
		s += '&hi='+ardeEscape( this.hasImageCheck.element.checked?'t':'f' );
	} else {
		s += '&hi=f';
	}
	return s;
};

ArdeClass.extend( PatternedObject, ArdeComponent );

function NewPatternedObject() {
	this.PatternedObject( null, '', '' );
}

NewPatternedObject.prototype.clear = function () {
	this.nameInput.element.value = '';
	this.patternInput.element.value = '';
};

NewPatternedObject.prototype.addClicked = function () {
	this.addButton.request( twatchFullUrl( this.constructor.rec ), 'a=add&'+this.getParams(), this.constructor.objectClass );
};

NewPatternedObject.prototype.addConfirmed = function ( obj ) {
	this.ardeList.insertFirstItem( obj );
	this.clear();
};

NewPatternedObject.title = '';
NewPatternedObject.addButtonName = '';
NewPatternedObject.objectClass = null;
ArdeClass.extend( NewPatternedObject, PatternedObject );











