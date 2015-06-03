
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
    
function AdminErrors() {
	this.ArdeComponent( 'div' );
	this.clearButton = new ArdeRequestButton( 'Clear' );
	this.clearButton.setStandardCallbacks( this, 'clear' );
	this.append( ardeE( 'p' ).append( this.clearButton ) );
}

AdminErrors.prototype.clearClicked = function () {
	this.clearButton.request( twatchFullUrl( 'rec/rec_errors.php' ), 'a=clear' );
}

AdminErrors.prototype.clearConfirmed = function( result ) {
	var errorsPane = document.getElementById( 'errors_pane' );
	ArdeXml.removeChildren( errorsPane );
	ardeE( 'p' ).append( ardeT( 'No Errors' ) ).appendTo( errorsPane );
}

ArdeClass.extend( AdminErrors, ArdeComponent );
