
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

function UserAgent( id, name, pattern, hasImage ) {
	this.PatternedObject( id, name, pattern, hasImage, id == 1 );
}
    
function UserAgentsHolder() {
	this.PatternedObjectsHolder( new NewUserAgent(), 'User Agent String' );
}
UserAgentsHolder.rec = 'rec/rec_user_agents.php';
UserAgentsHolder.objectClass = UserAgent;
UserAgentsHolder.objectTagName = 'user_agent';

ArdeClass.extend( UserAgentsHolder, PatternedObjectsHolder );



UserAgent.fromXml = function ( element ) {
	var id = ArdeXml.intAttribute( element, 'id' );
	var name = ArdeXml.attribute( element, 'name' );
	var pattern = ArdeXml.strElement( element, 'pattern' );
	var hasImage = ArdeXml.boolAttribute( element, 'has_image' );
	return new UserAgent( id, name, pattern, hasImage );
}

UserAgent.rec = 'rec/rec_user_agents.php';

UserAgent.prototype.downClicked = function () {
	if( this.ardeList.positionOf( this ) == this.ardeList.length() - 2 ) {
		return alert( "can't go lower than 'Unknown'" );
	}
	return this.PatternedObject_downClicked();
}
ArdeClass.extend( UserAgent, PatternedObject );

function NewUserAgent() {
	this.NewPatternedObject();
}
NewUserAgent.rec = 'rec/rec_user_agents.php';
NewUserAgent.title = 'New User Agent';
NewUserAgent.addButtonName = 'Add User Agent';
NewUserAgent.objectClass = UserAgent;
ArdeClass.extend( NewUserAgent, NewPatternedObject );
