
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

function ProfileVis( id, name, hidden ) {
	this.id = id;
	this.name = name;
	this.hidden = hidden;
}

function UserManager( viewReportId, viewReport, viewAdminId, viewAdmin, configId, config, adminId, admin,  viewErrorsId, viewErrors, profiles ) {
	this.profiles = profiles;
	
	this.ArdeComponent( 'div' );
	this.cls( 'block' );
	
	this.selectors = [];
	
	this.append( ardeE( 'p' ).append( selectedUser.getName() ).append( ardeE( 'b' ) ) );
	
	var topTr = ardeE( 'tr' ).appendTo( ardeE( 'tbody' ).appendTo( new ArdeTable().appendTo( this ) ) );
	
	this.tBody = ardeE( 'tbody' ).appendTo( new ArdeTable().cls('std').appendTo( ardeE( 'td' ).style( 'verticalAlign', 'top' ).appendTo( topTr ) ) );
	this.addPermission( 'can view reports', viewReportId, viewReport );
	this.addPermission( 'can view admin', viewAdminId, viewAdmin );
	this.addPermission( 'can configure', configId, config );
	if( twatchUser.isRoot() ) {
		this.addPermission( 'can administrate', adminId, admin );
		this.addPermission( 'view errors and server info', viewErrorsId, viewErrors );
	}
	
	if( this.profiles !== null ) {

		var profilesTable = new ArdeTable().cls('std').appendTo( ardeE( 'td' ).style( 'verticalAlign', 'top' ).appendTo( topTr ) );
		profilesTable.append( ardeE( 'thead' ).append( ardeE( 'tr' ).append( new ArdeTd().setColSpan( 2 ).append( ardeT( 'Profile Visibility' ) ) ) ) );
		var profilesTBody = ardeE( 'tbody' ).appendTo( profilesTable );
		
		this.profileSelects = [];
		for( var i in profiles ) {
			this.profileSelects[i] = new BinarySelector( new BinaryValue( !this.profiles[i].hidden ), 'Visible', 'Hidden' );
			if( selectedUser.type == User.USER && selectedUser.id == User.RootUserId ) {
				this.profileSelects[i].stateSelect.setDisabled( true );
			}
			profilesTBody.append( ardeE( 'tr' ).append( ardeE( 'td' ).append( ardeT( profiles[i].name ) ) ).append( ardeE( 'td' ).append( this.profileSelects[i] ) ) );
		}
		this.profilesRestoreButton = new ArdeRequestButton( 'Restore Defaults' );
		this.profilesRestoreButton.setStandardCallbacks( this, 'restoreProfiles' );
		profilesTBody.append( ardeE( 'tr' ).append( new ArdeTd().setColSpan( 2 ).style( 'textAlign', 'center' ).append( this.profilesRestoreButton ) ) );
		if( selectedUser.isRoot() ) {
			this.profilesRestoreButton.button.setDisabled( true );
		}
	}
	this.applyButton = new ArdeRequestButton( 'Apply Changes' );
	this.applyButton.setStandardCallbacks( this, 'apply' );
	if( selectedUser.isRoot() ) {
		this.applyButton.button.setDisabled( true );
	}
	this.append( ardeE( 'p' ).append( this.applyButton ) );

}

function ProfileList () {
}

ProfileList.fromXml = function ( element ) {
	var o = new ProfileList();
	profileEs = new ArdeXmlElemIter( element, 'profile' );
	while( profileEs.current ) {
		o[ ArdeXml.strContent( profileEs.current ) ] = true;
		profileEs.next();
	}
	return o;
};

UserManager.prototype.restoreProfilesClicked = function () {
	this.profilesRestoreButton.request( twatchFullUrl( 'rec/rec_general.php' ), 'a=restore_profiles', ProfileList );
};

UserManager.prototype.restoreProfilesConfirmed = function ( hiddenProfiles ) {
	for( var i in this.profiles ) {
		this.profiles[i].hidden = ( typeof hiddenProfiles[ this.profiles[i].id ] != 'undefined' );
		this.profileSelects[i].setSelected( !this.profiles[i].hidden );
	}
};

UserManager.prototype.addPermission = function ( str, id, perm ) {
	var sel = new UserPermissionSelector( perm );
	sel.id = id;
	this.selectors.push( sel );
	this.tBody.append( ardeE( 'tr' ).append( ardeE( 'td' ).style( 'textAlign', 'right' ).append( ardeT( str ) ) ).append( ardeE( 'td' ).style( 'paddingLeft', '0px' ).style( 'background', '#fff' ).append( this.selectors[ this.selectors.length - 1 ] ) ) );
};

UserManager.prototype.applyClicked = function () {
	var q = 'a=set_perms';
	for( var i in this.selectors ) {
		q += '&'+this.selectors[i].getParams( 'p'+this.selectors[i].id+'_' );
	}
	if( this.profiles !== null ) {
		var c = 0;
		for( var i in this.profiles ) {
			if( !this.profileSelects[i].selectedValue() ) {
				q += '&hpf_'+c+'='+ardeEscape( this.profiles[i].id );
				++c;
			}
		}
		q += '&hpfc='+c;
	}
	this.applyButton.request( twatchFullUrl( 'rec/rec_general.php' ), q );
};

ArdeClass.extend( UserManager, ArdeComponent );

function LangSelector( defLang, langs ) {
	this.defLang = defLang;
	this.langs = langs;
	this.ArdeComponent( 'div' );
	this.append( ardeE( 'h2' ).append( ardeT( 'Language' ) ) );
	this.mainBlock = new ArdeComponent( 'div' ).cls( 'block' ).appendTo( this );
	this.select = new ArdeSelect();
	for( var i in this.langs ) {
		this.select.append( new ArdeOption( this.langs[i], this.langs[i] ).setSelected( this.langs[i] == this.defLang ) );
	}
	this.applyButton = new ArdeRequestButton( 'Apply' );
	this.applyButton.setStandardCallbacks( this, 'apply' );
	
	this.useDefButton = new ArdeRequestButton( 'Use Default' );
	this.useDefButton.setStandardCallbacks( this, 'useDef' );
	
	var p = ardeE( 'p' ).appendTo( this.mainBlock );
	p.append( ardeT( 'Default Language of '+websiteName+( configMode ? '' : ' for ' ) ) );
	if( !configMode ) p.append( selectedUser.getName() );
	p.append( ardeT( ': ' ) ).append( this.select ).append( ardeT( ardeNbsp( 4 ) ) ).append( this.applyButton ).append( ardeT( ' ' ) ).append( this.useDefButton );
}

LangSelector.prototype.applyClicked = function () {
	this.applyButton.request( twatchFullUrl( 'rec/rec_general.php' ), 'a=update_def_lang&i='+this.select.selectedOption().value );
};

LangSelector.prototype.useDefClicked = function () {
	this.useDefButton.request( twatchFullUrl( 'rec/rec_general.php' ), 'a=restore_def_lang', ArdeXmlObjString );
};

LangSelector.prototype.useDefConfirmed = function ( id ) {
	var n = this.select.element.firstChild;
	while( n ) {
		if( n.value == id ) {
			n.selected = true;
			break;
		}
		n = n.nextSibling;
	}
};

ArdeClass.extend( LangSelector, ArdeComponent );



