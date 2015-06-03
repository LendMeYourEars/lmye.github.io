
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
    
function Logger( when ) {
	this.ArdeComponent( 'div' );
	this.main = new ArdeComponent( 'div' ).cls( 'block' ).appendTo( this );
	this.whenInput = new TwatchExpressionInput( when );
	this.main.append( ardeE( 'p' ).append( ardeT( 'Log When: ' ) ).append( this.whenInput ) );
	this.applyButton = new ArdeRequestButton( 'Apply Change' );
	this.applyButton.setStandardCallbacks( this, 'apply' );
	this.main.append( ardeE( 'p' ).append( this.applyButton ) );
}

Logger.prototype.applyClicked = function () {
	this.applyButton.request( twatchFullUrl( 'rec/rec_general.php' ), 'a=change_log_when&w='+ardeEscape( this.whenInput.getParam() ) );
}

ArdeClass.extend( Logger, ArdeComponent );
