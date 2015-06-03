
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
    
function ArdeHttpRequester() {
	
}

ArdeHttpRequester.prototype.request = function( url, params ) {
	this.url = url;
	this.params = params;
	
	
	
	try { 
		this.req = new XMLHttpRequest(); 
	}  catch ( error ) { 
		this.req = new ActiveXObject( "Microsoft.XMLHTTP" );
	}

	var self = this;

	this.req.onreadystatechange = function() { 
		if( self.req.readyState == 4 ) {
			
			if( typeof self.req.responseXML == 'undefined' || !self.req.responseXML ) {
				self.error( new ArdeException( 'ArdeHttpRequester', 'bad response received', 0, null, 'debug URL: '+self.url+'?'+self.params ) );
			} else {
				self.receive( self.req.responseXML.documentElement );
			}
			self.inProgress = false;
		}
	};
	
	this.req.open( "POST", url, true );
	this.req.setRequestHeader( "Content-Type", "application/x-www-form-urlencoded; charset=UTF-8" );
	this.req.setRequestHeader( "Accept", "application/xml, text/xml, */*" );
	this.inProgress = true;
	
	this.req.send( params );
};

ArdeHttpRequester.prototype.inProgress = false;

ArdeHttpRequester.prototype.isInProgress = function () {
	return this.inProgress;
};

ArdeHttpRequester.prototype.error = function( error ) {
	throw error;
};

ArdeHttpRequester.prototype.receive = function( xmlRoot ) {};


function ArdeStdRequester() {
	this.ArdeHttpRequester();
}

ArdeStdRequester.prototype.request = function( url, params, resultClass ) {
	this.resultClass = resultClass;
	this.ArdeHttpRequester_request( url, params );
};

ArdeStdRequester.prototype.somethingReceived = function () {};

ArdeStdRequester.prototype.receive = function( xmlRoot ) {
	
	this.somethingReceived();
	debugU = new ArdeUrlWriter( this.url );
	debugU.addParams( this.params );
	var debugUrl = 'debug url: '+debugU.getUrl();

	if( typeof xmlRoot != 'object' ) {
		return this.errorReceived( new Array( new ArdeException( 'ArdeStdRequest.receive', 'bad xml received', 0, null, debugUrl ) ) );
	}
	if( xmlRoot.tagName != 'response' ) {
		if( xmlRoot.tagName == 'parsererror' ) {
			return this.errorReceived( new Array( new ArdeException( 'ArdeStdRequest.receive', 'BAD XML received, really bad :(', 0, null, debugUrl ) ) );
		}
		return this.errorReceived( new Array( new ArdeException( 'ArdeStdRequest.receive', 'root element is not <response> it is <'+xmlRoot.tagName+'>', 0, null, debugUrl ) ) );
	}
	var errors = new Array();
	var errorEs = new ArdeXmlElemIter( xmlRoot, 'error' );

	while( errorEs.current ) {
		try {
			var exc = ArdeException.fromXml( errorEs.current );
			if( !exc.muted ) exc.extras.push( debugUrl );
			errors.push( exc );
			
		} catch( e ) {
			
			errors.push( new ArdeException( 'ArdeStdRequest.receive', 'error parsing <error>', 0, e, debugUrl ) );
		}
		errorEs.next();
	}
	
	if( this.resultClass != null ) {
		
		resultE = ArdeXml.element( xmlRoot, 'result', null );
		if( resultE == null ) {
			
			if( !errors.length ) errors.push( new ArdeException( 'ArdeStdRequest.receive', '<result> not received, no further error message', 0, null, debugUrl ) );
			
			return this.errorReceived( errors );
		}
		try {
			var result = this.resultClass.fromXml( resultE );
			
		} catch( e ) {
			errors.push( new ArdeException( 'ArdeStdRequest.receive', 'error interpreting <result>', 0, e, debugUrl ) );
			return this.errorReceived( errors );
		}
		
	} else {
		
		successE = ArdeXml.element( xmlRoot, 'successful', null );
		if( successE == null ) {

			if( !errors.length ) errors.push( new ArdeException( 'ArdeStdRequest.receive', '<successful /> not received, no further error message', 0, null, debugUrl ) );
			return this.errorReceived( errors );
		}
		var result = null;
	}	
 
	if( errors.length ) {
		this.errorReceived( errors );
	}

	return this.resultReceived( result );
};

ArdeStdRequester.prototype.resultReceived = function( result ) {};

ArdeStdRequester.prototype.error = function( error ) {
	
	this.somethingReceived();
	this.errorReceived( new Array( error ) );
};

ArdeStdRequester.prototype.errorReceived = function( errors ) {};

ArdeClass.extend( ArdeStdRequester, ArdeHttpRequester );

